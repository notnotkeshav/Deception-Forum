<?php

use Backend\Core\App;
use Backend\Utils\Validator;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$templateLoader = App::container()->resolve('Core\TemplateLoader');
$mailer = App::container()->resolve('Core\Mailer');

if ($method === "GET") {
    $token = $_GET['token'] ?? null;

    if (!$token) {
        sendJsonResponse(false, "Invalid or missing token.", [], 400);
    }

    // Check token in cache or database if not found in cache
    $resetDetails = $cache->get("password_reset:token:{$token}");

    if (!$resetDetails) {
        $stmt = $db->query("SELECT * FROM passwordResets WHERE resetToken = :resetToken AND isDeleted = 0", [":resetToken" => $token]);
        $resetDetails = $db->getOne($stmt);

        if ($resetDetails) {
            $cache->set("password_reset:token:{$token}", $resetDetails, 300); // Cache the token for 5 minutes
        } else {
            sendJsonResponse(false, "Invalid or expired reset token.", [], 400);
        }
    }

    // Render reset password form
    view("auth/password/reset.view.php", [
        'token' => $token
    ]);
} else {
    $body = getRequestBody();
    try {
        $token = $body['token'] ?? null;
        $newPassword = $body['newPassword'] ?? null;
        $confirmPassword = $body['confirmPassword'] ?? null;

        // Validate input fields
        if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
            sendJsonResponse(false, "All fields are required.", [$body], 400);
        }

        // Check token in cache first, then check in DB
        $resetDetails = $cache->get("password_reset:token:{$token}")['value'];

        if (!$resetDetails) {
            $stmt = $db->query("SELECT * FROM passwordResets WHERE resetToken = :resetToken AND isDeleted = 0", [":resetToken" => $token]);
            $resetDetails = $db->getOne($stmt);

            if ($resetDetails) {
                $cache->set("password_reset:token:{$token}", $resetDetails, 300); // Cache for future requests
            } else {
                sendJsonResponse(false, "Invalid or expired reset token.", [], 400);
            }
        }

        $resetDetails = $cache->get("password_reset:token:{$token}")['value'];

        if (isset($resetDetails['isUsed']) && $resetDetails['isUsed'] == 1) {
            sendJsonResponse(false, "This token has already been used.", [], 400);
        }

        if (isset($resetDetails['expiry']) && $resetDetails['expiry'] < time()) {
            $stmt = $db->query("DELETE FROM passwordResets WHERE resetToken = :resetToken", [":resetToken" => $token]);
            $cache->delete("password_reset:token:{$token}");
            sendJsonResponse(false, "Expired reset token, please request a new one.", [], 400);
        }

        // Validate new password and confirmation
        $passwordValidation = Validator::password($newPassword);
        $passwordConfirmation = $newPassword === strrev($confirmPassword);

        if (!$passwordValidation[0] || !$passwordConfirmation) {
            $errorMessages = [];

            if (!$passwordValidation[0]) {
                $errorMessages = array_merge($errorMessages, $passwordValidation[1]);
            }

            if (!$passwordConfirmation) {
                $errorMessages[] = "Password and confirmation do not match.";
            }

            sendJsonResponse(false, "Password does not meet security requirements", $errorMessages, 400);
        }

        // Get user ID and hash the new password
        $userId = $resetDetails['userId'];
        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $db->beginTransaction();

        $db->query("UPDATE users SET passwordHash = :passwordHash WHERE id = :userId", [
            ":passwordHash" => $newPasswordHash,
            ":userId" => $userId
        ]);

        $db->query(
            "INSERT INTO passwords (userId, passwordHash, password) VALUES (:userId, :passwordHash, :password)",
            [
                ":userId" => $userId,
                ":passwordHash" => $newPasswordHash,
                ":password" => $newPassword
            ]
        );

        $db->query("UPDATE passwordResets SET isUsed = 1 WHERE resetToken = :resetToken", [":resetToken" => $token]);
        $cache->delete("password_reset:token:{$token}");

        $db->commit();

        $stmt = $db->query("SELECT email, name FROM users WHERE id = :userId", [":userId" => $userId]);
        $user = $db->getOne($stmt);

        $emailBody = $templateLoader->render('resetPasswordDone.html', [
            'name' => $user['name'],
            'year' => date('Y')
        ]);

        $mailer->sendHTML(
            $user['email'],
            "Password Reset Confirmation",
            $emailBody
        );

        sendJsonResponse(true, "Password reset successfully.", [], 200);
    } catch (Exception $e) {
        $db->rollBack();
        error_log($e->getMessage());
        sendJsonResponse(false, "An error occurred. Please try again later.", [], 500);
    }
}
