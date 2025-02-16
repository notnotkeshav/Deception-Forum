<?php

use Backend\Core\App;
use Backend\Utils\Validator;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$templateLoader = App::container()->resolve('Core\TemplateLoader');
$mailer = App::container()->resolve('Core\Mailer');

try {
   // Check if the user is logged in and has the correct access level
   if (!isset($_SESSION['userId'])) {
      sendJsonResponse(false, "You must be logged in to change the password.", [], 401);
   }

   if ($method === "GET") {
      // Render the password change view
      view("auth/password/change.view.php");
      $password = generateRandomPassword();
      $reversedPassword = strrev($password);

      echo "generatePassword=> " . $password . "<br>reversedPassword=>  " . $reversedPassword;
      exit();
   } elseif ($method === "PUT") {
      $body = getRequestBody();

      $db->beginTransaction();

      // Validate the input
      if (empty($body['oldPassword']) || empty($body['newPassword']) || empty($body['confirmPassword'])) {
         sendJsonResponse(false, "All fields are required.", [], 400);
      }

      // Fetch current user from database
      $user = $_SESSION['user'];

      if (!$user) {
         sendJsonResponse(false, "User not found.", [], 404);
      }

      // Verify the old password
      if (!password_verify($body['oldPassword'], $user['passwordHash'])) {
         sendJsonResponse(false, "Old password is incorrect.", [], 400);
      }

      // Password validation (ensure it's strong and meets requirements)
      $passwordValidation = Validator::password($body['newPassword'], $user['username'], $user['name']);
      $passwordConfirmation = $body['newPassword'] === strrev($body['confirmPassword']); // Reversed password check

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

      $stmt = $db->query(
         "SELECT passwordHash FROM passwords WHERE userId = :userId ORDER BY createdAt DESC LIMIT 10",
         [":userId" => $_SESSION['userId']]
      );
      $lastPasswords = $db->getAll($stmt);

      foreach ($lastPasswords as $lastPassword) {
         if (password_verify($body['newPassword'], $lastPassword['passwordHash'])) {
            sendJsonResponse(false, "New password cannot be the same as one of the last 10 passwords.", [], 400);
         }
      }

      // Hash the new password
      $newPasswordHash = password_hash($body['newPassword'], PASSWORD_BCRYPT);

      // Update the user's passwordHash in the users table
      $stmt = $db->query(
         "UPDATE users SET passwordHash = :passwordHash WHERE id = :userId",
         [
            ":passwordHash" => $newPasswordHash,
            ":userId" => $_SESSION['userId']
         ]
      );

      // Insert the new password into the passwords table
      $db->query(
         "INSERT INTO passwords (userId, passwordHash, password) VALUES (:userId, :passwordHash, :password)",
         [
            ":userId" => $_SESSION['userId'],
            ":passwordHash" => $newPasswordHash,
            ":password" => $body['newPassword']
         ]
      );

      $stmt = $db->query('SELECT * from users where id = :userId', [':userId' => $_SESSION['userId']]);
      $user = $db->getOne($stmt);

      $cache->delete("user:loginurl:" . $_SESSION['user']['loginUrl']);
      $cache->set("user:loginurl:" .  $_SESSION['user']['loginUrl'], $user);
      // Check if the update was successful
      $db->commit();
      if ($db->rowCount($stmt) > 0) {
         $currentYear = date('Y');
         $emailBody = $templateLoader->render('changepassword.html', [
            'name' => $user['name'],
            'year' => $currentYear
         ]);

         $mailer->sendHTML(
            $user['email'],
            "Password Changed Successfully",
            $emailBody
         );
         sendJsonResponse(true, "Password changed successfully.", [], 201);
      } else {
         // No rows updated
         sendJsonResponse(false, "Failed to update password.", [], 500);
      }
   } else {
      sendJsonResponse(false, "Invalid request method.", [], 405);
   }
} catch (Exception $e) {
   $db->rollBack();
   error_log($e->getMessage());
   sendJsonResponse(false, "An error occurred. Please try again later.", [], 500);
   exit();
}
