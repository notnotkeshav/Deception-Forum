<?php

use Backend\Core\App;
use Backend\Utils\Validator;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$templateLoader = App::container()->resolve('Core\TemplateLoader');
$mailer = App::container()->resolve('Core\Mailer');

if ($method === "GET") {
   view("auth/password/forgot.view.php");
} elseif ($method === 'POST') {
   try {
      if (empty($_POST['email'])) {
         sendJsonResponse(false, "Email is required.", [], 400);
      }

      if (!Validator::email($_POST['email'])) {
         sendJsonResponse(false, "Invalid email address.", [], 400);
      }

      // Check if the email exists in the database
      $stmt = $db->query("SELECT id, email, name FROM users WHERE email = :email AND isDeleted = 0", [":email" => $_POST['email']]);
      $user = $db->getOne($stmt);

      if (!$user) {
         sendJsonResponse(false, "No user found with that email address.", [], 404);
      }

      // Generate a password reset token
      $resetToken = bin2hex(random_bytes(16));
      $resetTokenExpiry = time() + 300; // 5 min expiry

      $db->query("INSERT INTO passwordResets (userId, resetToken, expiry) VALUES (:userId, :resetToken, :expiry)", [
         ":userId" => $user['id'],
         ":resetToken" => $resetToken,
         ":expiry" => $resetTokenExpiry
      ]);

      $cache->set("password_reset:{$user['id']}:{$resetToken}", [
         'resetToken' => $resetToken,
         'expiry' => $resetTokenExpiry
      ], 400); // Cache expires in 5 min

      // Generate the reset URL
      $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
      $resetUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/reset-password?token={$resetToken}";

      $emailBody = $templateLoader->render('forgotPassword.html', [
         'name' => $user['name'],
         'resetUrl' => $resetUrl,
         'year' => date('Y')
      ]);

      queueEmail(
         $user['email'],
         "Password Reset Request",
         $emailBody
      );

      sendJsonResponse(true, "Password reset instructions have been sent to your email.", [], 200);
   } catch (Exception $e) {
      error_log($e->getMessage());
      sendJsonResponse(false, "An error occurred. Please try again later.", [], 500);
   }
}
