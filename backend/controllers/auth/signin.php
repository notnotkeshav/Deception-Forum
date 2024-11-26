<?php

use Backend\Core\App;
use Backend\Utils\Validator;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$maxFailedAttempts = 3;
$maxSuspiciousAttempts = 5;
$maxLockouts = 5;

$ipAddress = $_SERVER['REMOTE_ADDR'];
$currentTime = time();

if ($method === 'GET') {
   view("auth/signin.view.php", [
      "heading" => "SignIn",
   ]);
} else {
   $params = getQueryParams();
   $cache->clearExpired();
   $loginCode = $cache->get("loginurl:" . $params['code']);

   if (!$loginCode) {
      $stmt = $db->query("SELECT * FROM users WHERE loginurl = :code ", [":code" => $params['code']]);
      $user = $db->getOne($stmt);
      if ($user && isset($user['loginURL'])) {
         $cache->set("loginurl:" . $params['code'], $loginCode);
         $cache->set("user:loginurl:" . $params['code'], $user);
      } else {
         echo json_encode([
            'error' => "Invalid Login Code:- {$params['code']}"
         ]);
         exit();
      }
   }

   $user = $cache->get("user:loginurl:" . $params['code']);

   $lockoutKey = "lockout:" . $user['value']['ID'];
   $failedAttemptsKey = "failed_attempts:" . $user['value']['ID'];
   $suspiciousIPKey = "suspicious_ip:" . $user['value']['ID'];
   $lockoutsKey = "lockouts:" . $user['value']['ID'];

   $lockoutTime = $cache->get($lockoutKey)['value'] ?? null;
   if ($lockoutTime && $lockoutTime > $currentTime) {
      $remainingLockout = ceil(($lockoutTime - $currentTime) / 60);
      echo json_encode(['error' => "Account locked due to multiple failed login attempts. Please try again after $remainingLockout minutes."]);
      exit();
   }

   if (!Validator::email($_POST['email'])) {
      echo json_encode(['error' => "Invalid Email"]);
      exit();
   }

   $suspiciousIPs = $cache->get($suspiciousIPKey)['value'] ?? [];
   if (!is_array($suspiciousIPs)) {
      $suspiciousIPs = [];
   }
   if (!in_array($ipAddress, $suspiciousIPs)) {
      $suspiciousIPs[] = $ipAddress;
      $cache->set($suspiciousIPKey, $suspiciousIPs);

      if (count($suspiciousIPs) >= $maxSuspiciousAttempts) {
         echo json_encode(['error' => "Your account has been flagged for suspicious activity. Please contact support."]);
         // Logic for suspending account. sending mail for regenerate new password and strike.
         exit();
      }
   }

   if ($user['value']['email'] === $_POST['email']) {
      if (password_verify($_POST['password'], $user['value']['passwordHash'])) {
         $cache->delete($failedAttemptsKey);
         $cache->delete($lockoutKey);
         $cache->delete($suspiciousIPKey);

         $token = generateToken();
         $expiresAt = $currentTime + (24 * 60 * 60);

         $_SESSION['token'] = $token;
         $_SESSION['token_expiration'] = $expiresAt;
         $_SESSION['userId'] = $user['value']['ID'];

         echo json_encode([
            'session' => $_SESSION,
         ]);
         exit();
      }

      $failedAttempts = $cache->get($failedAttemptsKey)['value'] ?? 0;
      if (!is_int($failedAttempts)) {
         $failedAttempts = 0;
      }
      $failedAttempts++;
      $cache->set($failedAttemptsKey, $failedAttempts);

      if ($failedAttempts >= $maxFailedAttempts) {
         $lockoutDuration = 0;

         if ($failedAttempts < 6) {
            $lockoutDuration = 15 * 60;
         } elseif ($failedAttempts < 9) {
            $lockoutDuration = 30 * 60;
         } elseif ($failedAttempts < 12) {
            $lockoutDuration = 45 * 60;
         } else {
            $lockoutDuration = 3 * 24 * 60 * 60;
         }

         $cache->set($lockoutKey, $currentTime + $lockoutDuration);
         $lockouts = $cache->get($lockoutsKey) ?? 0;
         if (!is_int($lockouts)) {
            $lockouts = 0;
         }
         $lockouts++;
         $cache->set($lockoutsKey, $lockouts);

         if ($lockouts > $maxLockouts) {
            echo json_encode(['error' => "Your account has been locked due to repeated login failures. You will need to reset your password."]);
            // send mail for password reset and reason for this lockout.
            exit();
         }

         echo json_encode(['error' => "Account locked due to multiple failed login attempts. Please try again in 15 minutes."]);
         exit();
      }

      echo json_encode(['error' => "Either email or password is incorrect. Attempt: $failedAttempts"]);
      exit();
   } else {
      echo json_encode(['error' => "Either email or password is incorrect."]);
      exit();
   }
}
