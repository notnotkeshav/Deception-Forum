<?php

use Backend\Core\App;
use Backend\Utils\Validator;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

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
   $params = getQueryParams();
   $cache->clearExpired();
   $cachedInviteCode = $cache->get("invitecode:" . $params['invite']);

   if (!$cachedInviteCode || !is_array($cachedInviteCode)) {
      // Invite code is not found in cache or is not in the expected format
      $stmt = $db->query("SELECT * FROM invitecodes WHERE code = :code AND used = 0", [":code" => $params['invite']]);
      $inviteCode = $db->getOne($stmt);

      if ($inviteCode) {
         $cache->set("invitecode:" . $params['invite'], $inviteCode);
      } else {
         http_response_code(404); // Not Found: Invite code is invalid
         echo json_encode([
            "success" => false,
            "error" => "Invalid Invite Code: " . $params['invite']
         ]);
         exit();
      }
   } else {
      if ($cachedInviteCode['value']['used'] == 1) {
         http_response_code(410); // Gone: Invite code has already been used
         echo json_encode([
            "success" => false,
            "error" => "Invite Code has already been used: " . $params['invite']
         ]);
         exit();
      }
      // Use the cached invite code
      $inviteCode = $cachedInviteCode['value']['code'];
   }

   if (!Validator::email($_POST['email'])) {
      http_response_code(400); // Bad Request: Invalid email format
      echo json_encode(["success" => false, "error" => "Invalid Email"]);
      exit();
   }

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

      http_response_code(400); // Bad Request: Password validation failed
      echo json_encode([
         "success" => false,
         "error" => "Password does not meet security requirements",
         "messages" => $errorMessages
      ]);
      exit();
   }

   $cachedUser = $cache->get("user:email:" . $_POST['email']);
   if ($cachedUser) {
      http_response_code(409); // Conflict: User already exists
      echo json_encode(["success" => false, "error" => "User already exists"]);
      exit();
   }

   $stmt = $db->query("SELECT username FROM users WHERE email = :email and isDeleted = 0", [":email" => $_POST['email']]);
   $user = $db->getOne($stmt);
   if (!empty($user)) {
      $cache->set("user:email:" . $_POST['email'], $user);
      http_response_code(409); // Conflict: User already exists
      echo json_encode(["success" => false, "error" => "User already exists"]);
      exit();
   } else {
      $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
      $generatedCode = generateLoginUrl();
      $db->beginTransaction();

      $db->query("INSERT INTO users (email, username, passwordHash, name, loginUrl, accessLevel, timezone) VALUES (:email, :username, :password, :name, :loginUrl, :access_level, :timezone)", [
         ":email" => $_POST['email'],
         ":username" => $_POST['username'],
         ":password" => $hashedPassword,
         ":name" => $_POST['name'],
         ":loginUrl" => $generatedCode,
         ":access_level" => 1,
         ":timezone" => $_POST['timezone'] ?? "UTC"
      ]);
      $lastUserId = $db->lastInsertId();
      $db->query("INSERT INTO passwords (userId, passwordHash, password) VALUES (:userId, :passwordHash, :password)", [
         ":userId" => $lastUserId,
         ":passwordHash" => $hashedPassword,
         ":password" => $_POST['password']
      ]);

      $db->query("UPDATE invitecodes SET used = 1, usedBy = :userId WHERE code = :inviteCode", [
         ":userId" => $lastUserId,
         ":inviteCode" => $inviteCode ?? $inviteCode['code']
      ]);

      $updatedInviteCode = [
         'code' => $params['invite'],
         'used' => 1,
         'usedBy' => $lastUserId
      ];
      $cache->delete("invitecode:" . $params['invite']);
      $cache->set("invitecode:" . $params['invite'], $updatedInviteCode);
      $db->commit();

      $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
      $loginurl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/signin?code=" . $generatedCode;
      http_response_code(201); // Created: User registration successful
      echo json_encode([
         "success" => true,
         "loginurl" => $loginurl,
         $inviteCode
      ]);
      exit();
   }
}
