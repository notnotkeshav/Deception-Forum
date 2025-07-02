<?php

use Backend\Core\App;
use Backend\Utils\Validator;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$templateLoader = App::container()->resolve('Core\TemplateLoader');
$mailer = App::container()->resolve('Core\Mailer');

if ($method === 'GET') {
    if (isset($_SESSION['token']) && $_SESSION['token_expiration'] > time()) {
        header("location: /");
        exit();
    } else {
        view("auth/signup.view.php", [
            "heading" => "SignUp",
            "username" => generateUsername(random_int(15, 25))
        ]);
    }
    // From Here
    $password = generateRandomPassword();
    $reversedPassword = strrev($password);

    echo "generatePassword=> " . $password . "<br>reversedPassword=>  " . $reversedPassword;
    exit();
    // To Here => this will lead to render the GET even after the check says token not found

} else {
    try {
        // Begin transaction to group all database operations
        $db->beginTransaction();

        // Get cached invite code
        $params = getQueryParams();
        $cache->clearExpired();
        $cachedInviteCode = $cache->get("invitecode:" . $params['invite']);

        if (!$cachedInviteCode || !is_array($cachedInviteCode)) {
            $stmt = $db->query("SELECT * FROM inviteCodes WHERE code = :code AND used = 0", [":code" => $params['invite']]);
            $inviteCode = $db->getOne($stmt);

            if (!$inviteCode) {
                sendJsonResponse(false, "Invalid Invite Code", ["inviteCode" => $params['invite']], 404);
            }

            $cache->set("invitecode:" . $params['invite'], $inviteCode);
        } else {
            if ($cachedInviteCode['value']['used'] == 1) {
                sendJsonResponse(false, "Invite Code has already been used", ["inviteCode" => $params['invite']], 410);
            }
            $inviteCode = $cachedInviteCode['value']['code'];
        }

        // Validate email
        if (!Validator::email($_POST['email'])) {
            sendJsonResponse(false, "Invalid Email", ["details" => "The provided email is not valid."], 400);
        }

        // Validate password
        $passwordValidation = Validator::password($_POST['password'], $_POST['username'], $_POST['name']);
        $passwordConfirmation = $_POST['password'] === strrev($_POST['confirmPassword']);

        if (!$passwordValidation[0] || !$passwordConfirmation) {
            $errorMessages = [];

            if (!$passwordValidation[0]) {
                $errorMessages = array_merge($errorMessages, $passwordValidation[1]);
            }

            if (!$passwordConfirmation) {
                $errorMessages[] = "Password and confirmation do not match (or are not reversed).";
            }

            sendJsonResponse(false, "Password does not meet security requirements", $errorMessages, 400);
        }

        // Check for existing user in cache
        $cachedUser = $cache->get("user:email:" . $_POST['email']);
        if ($cachedUser) {
            sendJsonResponse(false, "User already exists", ["email" => $_POST['email']], 409);
        }

        // Check for existing user in the database
        $stmt = $db->query("SELECT username FROM users WHERE email = :email AND isDeleted = 0", [":email" => $_POST['email']]);
        $user = $db->getOne($stmt);
        if (!empty($user)) {
            $cache->set("user:email:" . $_POST['email'], $user);
            sendJsonResponse(false, "User already exists", ["email" => $_POST['email']], 409);
        }

        // Hash the password
        $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $generatedCode = generateLoginUrl();

        // Insert new user
        $db->query("INSERT INTO users (email, username, passwordHash, name, loginUrl, accessLevel, timezone, profilePic) VALUES (:email, :username, :password, :name, :loginUrl, :access_level, :timezone, :profilePic)", [
            ":email" => $_POST['email'],
            ":username" => $_POST['username'],
            ":password" => $hashedPassword,
            ":name" => $_POST['name'],
            ":loginUrl" => $generatedCode,
            ":access_level" => 1,
            ":timezone" => $_POST['timezone'] ?? "UTC",
            ":profilePic" => $_POST['profilePic'] ?? null
        ]);
        $stmt = $db->query("SELECT id FROM users WHERE email = :email", [":email" => $_POST['email']]);
        $lastUserId = $db->getOne($stmt)['id'];

        // Insert the password into the passwords table
        $db->query("INSERT INTO passwords (userId, passwordHash, password) VALUES (:userId, :passwordHash, :password)", [
            ":userId" => $lastUserId,
            ":passwordHash" => $hashedPassword,
            ":password" => $_POST['password']
        ]);

        // Update the invite code as used
        $db->query("UPDATE inviteCodes SET used = 1, usedBy = :userId WHERE code = :inviteCode", [
            ":userId" => $lastUserId,
            ":inviteCode" => $params['invite']
        ]);

        // Update the invite code in the cache
        $updatedInviteCode = [
            'code' => $params['invite'],
            'used' => 1,
            'usedBy' => $lastUserId
        ];
        // $cache->delete("invitecode:" . $params['invite']);
        $cache->set("invitecode:" . $params['invite'], $updatedInviteCode);

        // Generate the login URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $loginurl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/signin?code=" . $generatedCode;

        $currentYear = date('Y');
        $emailBody = $templateLoader->render('signupSuccess.html', [
            'name' => $_POST['name'],
            'loginUrl' => $loginurl,
            'year' => $currentYear
        ]);

        // Send the email using the mailer
        queueEmail($_POST['email'], "Signup Successful", $emailBody);

        // Commit the transaction
        $db->commit();

        // Respond with success
        sendJsonResponse(true, "User registration successful", 201);
    } catch (Exception $e) {
        // Rollback transaction and respond with error
        $db->rollBack();
        error_log($e->getMessage());
        sendJsonResponse(false, $e->getMessage(), [], 500);
    }
}
