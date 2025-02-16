<?php

use Backend\Core\App;
use Backend\Utils\Validator;

// Resolve database and cache instances from the container
$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$templateLoader = App::container()->resolve('Core\TemplateLoader');
$mailer = App::container()->resolve('Core\Mailer');

// Define login security parameters
$maxFailedAttempts = 3; // Maximum failed login attempts before locking the account
$maxSuspiciousAttempts = 5; // Maximum number of suspicious IPs allowed
$maxLockouts = 5; // Maximum number of lockouts before permanent account lock

$ipAddress = $_SERVER['REMOTE_ADDR'];
$currentTime = time();

if ($method === 'GET') {
   view("auth/signin.view.php", [
      "heading" => "SignIn",
   ]);
} else {
   try {
      // Start a database transaction
      $db->beginTransaction();

      $params = getQueryParams();
      $cache->clearExpired();

      // Retrieve the login code from the cache
      $loginCode = $cache->get("loginurl:" . $params['code']);

      // If the login code is not cached, fetch it from the database
      if (!$loginCode) {
         $stmt = $db->query("SELECT * FROM users WHERE loginurl = :code AND isDeleted = 0", [":code" => $params['code']]);
         $user = $db->getOne($stmt);


         // Cache the retrieved login code and user information
         if ($user && isset($user['loginUrl'])) {
            $cache->set("loginurl:" . $params['code'], $loginCode);
            $cache->set("user:loginurl:" . $params['code'], $user);
         } else {
            throw new Exception("Invalid Login Code: {$params['code']}", 404);
         }
      }

      $user = $cache->get("user:loginurl:" . $params['code'])['value'];
      // dumpAndDie($user);

      // Define keys for tracking lockouts, failed attempts, and suspicious IPs
      $lockoutKey = "lockout:" . $user['id'];
      $failedAttemptsKey = "failed_attempts:" . $user['id'];
      $suspiciousIPKey = "suspicious_ip:" . $user['id'];
      $lockoutsKey = "lockouts:" . $user['id'];

      // Check if the account is locked
      $lockoutTime = $cache->get($lockoutKey)['value'] ?? null;
      if ($lockoutTime && $lockoutTime > $currentTime) {
         $remainingLockout = ceil(($lockoutTime - $currentTime) / 60);
         throw new Exception("Account locked. Try again after $remainingLockout minutes.", 429);
      }

      if (!Validator::email($_POST['email'])) {
         throw new Exception("Invalid Email", 400);
      }

      // Track suspicious IPs and flag the account if the threshold is exceeded
      $suspiciousIPs = $cache->get($suspiciousIPKey)['value'] ?? [];
      if (!in_array($ipAddress, $suspiciousIPs)) {
         $suspiciousIPs[] = $ipAddress;
         $cache->set($suspiciousIPKey, $suspiciousIPs);

         if (count($suspiciousIPs) >= $maxSuspiciousAttempts) {
            throw new Exception("Account flagged for suspicious activity. Contact support.", 403);
         }
      }

      if ($user['email'] === $_POST['email']) {
         if (password_verify($_POST['password'], $user['passwordHash'])) {
            // Clear failed attempts, lockouts, and suspicious IPs
            $cache->delete($failedAttemptsKey);
            $cache->delete($lockoutKey);
            $cache->delete($suspiciousIPKey);

            $token = generateToken();
            $expiresAt = $currentTime + (24 * 60 * 60);

            $_SESSION['token'] = $token;
            $_SESSION['token_expiration'] = $expiresAt;
            $_SESSION['userId'] = $user['id'];
            $_SESSION['user'] = $user;

            $stmt = $db->query("SELECT id, role FROM MODERATORS WHERE userId = :userId", [":userId" => $_SESSION['userId']]);
            $response = $db->getOne($stmt);
            if ($response) {
               $_SESSION['moderator'] = true;
            } else {
               $_SESSION['moderator'] = false;
            }

            sendJsonResponse(true, "Signin Successful", ['session' => $_SESSION]);
            $db->commit();
         }

         // Increment the failed attempts counter
         $failedAttempts = $cache->get($failedAttemptsKey)['value'] ?? 0;
         $failedAttempts++;
         $cache->set($failedAttemptsKey, $failedAttempts);

         // Lock the account if failed attempts exceed the threshold
         if ($failedAttempts >= $maxFailedAttempts) {
            $lockoutDuration = match (true) {
               $failedAttempts < 6 => 15 * 60,
               $failedAttempts < 9 => 30 * 60,
               $failedAttempts < 12 => 45 * 60,
               default => 3 * 24 * 60 * 60,
            };

            $cache->set($lockoutKey, $currentTime + $lockoutDuration);
            $lockouts = $cache->get($lockoutsKey)['value'] ?? 0;
            $cache->set($lockoutsKey, $lockouts + 1);

            // Permanently lock the account if lockout attempts exceed the limit
            if ($lockouts > $maxLockouts) {
               $emailBody = $templateLoader->render('accountBan.html', [
                  'name' => $user['name']
               ]);

               $mailer->sendHTML(
                  $user['email'],
                  "Account Permanently Locked",
                  $emailBody
               );
               throw new Exception("Account permanently locked. Check your mailbox for further instructions.", 423);
            }

            throw new Exception("Account locked. Try again in 15 minutes.", 429);
         }

         throw new Exception("Invalid email or password. Attempt: $failedAttempts", 401);
      } else {
         throw new Exception("Login URL does not match the provided email.", 401);
      }
   } catch (Exception $e) {
      $db->rollBack();
      error_log($e->getMessage());
      sendJsonResponse(false, $e->getMessage(), [], ($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500);
   }
}
