<?php

use Backend\Core\App;
use Backend\Utils\Validator;

// Resolve database and cache instances from the container
$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$templateLoader = App::container()->resolve('Core\TemplateLoader');
$mailer = App::container()->resolve('Core\Mailer');

// Check if user is already fully authenticated
if (!empty($_SESSION['user'])) {
   $user = $_SESSION['user'];
   $hasTotpEnabled = $user['totp_enabled'] ?? false;
   $loginCount = $user['login_count'] ?? 0;

   // If TOTP is disabled or first login, redirect to setup
   if ($loginCount === 0 || !$hasTotpEnabled) {
      header('Location: /totp-setup');
      exit();
   } else {
      // User is fully authenticated with TOTP enabled
      header('Location: /threads');
      exit();
   }
}

// Check if user has partial auth
if (!empty($_SESSION['partial_auth']) && $_SESSION['partial_auth']['expires'] > time()) {
   header('Location: /totp-setup');
   exit();
}

// Define login security parameters
$maxFailedAttempts = 3; // First lockout after 3 attempts
$secondLockoutAttempts = 5; // Second lockout after 5 total attempts
$banAttempts = 7; // Ban after 7 total attempts
$maxSuspiciousAttempts = 5; // Maximum number of suspicious IPs allowed
$maxLockouts = 5; // Maximum number of lockouts before permanent account lock

$ipAddress = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$currentTime = time();

// Enhanced security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Rate limiting for IP address
$ipRateLimitKey = "ip_attempts:" . hash('sha256', $ipAddress);
$ipAttempts = $cache->get($ipRateLimitKey)['value'] ?? 0;
if ($ipAttempts > 10) { // Max 10 attempts per IP per hour
   http_response_code(429);
   exit(json_encode(['success' => false, 'message' => 'Too many requests from this IP']));
}

if ($method === 'GET') {
   view("auth/signin.view.php", [
      "heading" => "SignIn",
   ]);
} else {
   try {
      // Start a database transaction
      $db->beginTransaction();

      // Increment IP rate limiting
      $cache->set($ipRateLimitKey, $ipAttempts + 1, 3600); // 1 hour expiry

      $params = getQueryParams();
      $cache->clearExpired();

      // Enhanced input validation
      if (!isset($params['code'])) {
         throw new Exception("Invalid login code format", 400);
      }

      if (!isset($_POST['username']) || !isset($_POST['password'])) {
         throw new Exception("Username and password are required", 400);
      }

      // Validate username format
      if (!Validator::string($_POST['username'], 3, 50)) {
         throw new Exception("Invalid username format", 400);
      }

      // Check cache first for login code
      $cacheKey = "loginurl:" . hash('sha256', $params['code']);
      $loginCode = $cache->get($cacheKey);

      $user = null;
      if (!$loginCode || $loginCode['expiration'] < $currentTime) {
         // Cache miss or expired - fetch from database
         $stmt = $db->query(
            "SELECT * FROM users WHERE loginurl = :code AND isDeleted = 0",
            [":code" => $params['code']]
         );
         $user = $db->getOne($stmt);

         if (!$user) {
            throw new Exception("Invalid Login Code", 404);
         }

         // Cache the user data with shorter expiry for security
         $cache->set($cacheKey, $user, 1800); // 30 minutes
         $cache->set("user:loginurl:" . hash('sha256', $params['code']), $user, 1800);
      } else {
         $user = $cache->get("user:loginurl:" . hash('sha256', $params['code']))['value'];
      }

      // Additional security check
      if (!$user || $user['isDeleted'] == 1) {
         throw new Exception("Account not found or deactivated or banned", 404);
      }

      // Define keys for tracking lockouts, failed attempts, and suspicious IPs
      $userIdHash = hash('sha256', $user['id']);
      $lockoutKey = "lockout:" . $userIdHash;
      $failedAttemptsKey = "failed_attempts:" . $userIdHash;
      $suspiciousIPKey = "suspicious_ip:" . $userIdHash;
      $lockoutsKey = "lockouts:" . $userIdHash;
      $deviceFingerprintKey = "device:" . $userIdHash . ":" . hash('sha256', $userAgent);

      // Check if the account is locked
      $lockoutData = $cache->get($lockoutKey);
      $lockoutTime = $lockoutData['value'] ?? null;
      if ($lockoutTime && $lockoutTime > $currentTime) {
         $remainingLockout = ceil(($lockoutTime - $currentTime) / 60);
         throw new Exception("Account locked. Try again after $remainingLockout minutes.", 429);
      }

      // Enhanced suspicious IP tracking
      $suspiciousIPs = $cache->get($suspiciousIPKey)['value'] ?? [];
      $ipHash = hash('sha256', $ipAddress);
      if (!in_array($ipHash, $suspiciousIPs)) {
         $suspiciousIPs[] = $ipHash;
         $cache->set($suspiciousIPKey, $suspiciousIPs, 86400); // 24 hours

         if (count($suspiciousIPs) >= $maxSuspiciousAttempts) {
            // Log security event
            error_log("Multiple IP access attempt for user ID: " . $user['id'] . " from IP: " . $ipAddress);
            throw new Exception("Account flagged for suspicious activity. Contact support.", 403);
         }
      }

      // Device fingerprinting for additional security
      $knownDevice = $cache->get($deviceFingerprintKey);
      if (!$knownDevice) {
         $cache->set($deviceFingerprintKey, ['first_seen' => $currentTime, 'trusted' => false], 86400);
      }

      // Secure password verification
      if (hash_equals($user['username'], $_POST['username'])) {
         if (password_verify($_POST['password'], $user['passwordHash'])) {
            // Clear failed attempts and IP rate limiting on successful login
            $cache->delete($failedAttemptsKey);
            $cache->delete($lockoutKey);
            $cache->delete($suspiciousIPKey);
            $cache->delete($ipRateLimitKey);

            // Update login count
            $loginCountKey = "login_count:" . $userIdHash;
            $loginCountRecord = $cache->get($loginCountKey);

            if (!$loginCountRecord) {
               // Fetch from database
               $stmt = $db->query("SELECT loginCount FROM loginCounts WHERE userId = :userId", [':userId' => $user['id']]);
               $dbLoginCount = $db->getOne($stmt);
               $loginCount = $dbLoginCount ? $dbLoginCount['loginCount'] : 0;
               $cache->set($loginCountKey, $loginCount, 3600);
            } else {
               $loginCount = $loginCountRecord['value'];
            }

            $loginCount++;

            // Update database and cache
            if ($loginCount === 1) {
               $stmt = $db->query("INSERT INTO loginCounts (userId, loginCount) VALUES (:userId, 1)", [':userId' => $user['id']]);
            } else {
               $stmt = $db->query("UPDATE loginCounts SET loginCount = :count WHERE userId = :userId", [
                  ':count' => $loginCount,
                  ':userId' => $user['id']
               ]);
            }
            $cache->set($loginCountKey, $loginCount, 3600);

            // Check if first login or TOTP not enabled - redirect to TOTP setup
            if ($loginCount === 1 || !$user['totp_enabled']) {
               $_SESSION['partial_auth'] = [
                  'userId' => $user['id'],
                  'expires' => time() + 300, // 5 minutes
                  'csrf_token' => bin2hex(random_bytes(32)),
                  'setup_required' => true
               ];
               $_SESSION['totp_user_info'] = [
                  'username' => $user['username'],
                  'email' => $user['email']
               ];

               $db->commit();
               sendJsonResponse(true, "TOTP setup required", [
                  'redirect' => '/totp-setup',
                  'totp_setup_required' => true
               ]);
               exit();
            }

            // If TOTP is enabled, redirect to verification (no session created yet)
            if ($user['totp_enabled']) {
               $_SESSION['partial_auth'] = [
                  'userId' => $user['id'],
                  'expires' => time() + 300, // 5 minutes
                  'csrf_token' => bin2hex(random_bytes(32)),
                  'setup_required' => false
               ];

               $_SESSION['totp_user_info'] = [
                  'username' => $user['username'],
                  'email' => $user['email']
               ];

               $db->commit();
               sendJsonResponse(true, "TOTP verification required", [
                  'redirect' => '/verify-totp',
                  'totp_required' => true
               ]);
               exit();
            }

            // This should never happen with current logic, but keeping for safety
            throw new Exception("Authentication flow error", 500);
         }

         // Enhanced failed attempt handling
         $failedAttempts = $cache->get($failedAttemptsKey)['value'] ?? 0;
         $failedAttempts++;
         $cache->set($failedAttemptsKey, $failedAttempts, 86400); // 24 hours

         // Implement the new lockout logic
         if ($failedAttempts >= $banAttempts) {
            // Send ban email
            $emailBody = $templateLoader->render('accountBan.html', [
               'name' => $user['name'],
               'reason' => 'Multiple failed login attempts'
            ]);

            queueEmail(
               $user['email'],
               "Account Permanently Banned - Security Violation",
               $emailBody
            );

            // Mark account as banned in database
            $stmt = $db->query("UPDATE users SET isDeleted = 2, bannedAt = CURRENT_TIMESTAMP WHERE id = :userId", [':userId' => $user['id']]);

            // Clear all cache entries for this user
            $cache->delete($failedAttemptsKey);
            $cache->delete($lockoutKey);
            $cache->delete($suspiciousIPKey);
            $cache->delete($loginCountKey);

            throw new Exception("Account permanently banned due to security violations. Check your email.", 423);
         } elseif ($failedAttempts >= $secondLockoutAttempts) {
            // Check random condition for reduced lockout
            $randomCheck = random_int(1, 105); // LCM of 3,5,7 is 105
            $lockoutDuration = 86400; // 1 day default

            if ($randomCheck % 3 === 0 && $randomCheck % 5 === 0 && $randomCheck % 7 === 0) {
               $lockoutDuration = 45 * 60; // 45 minutes
               error_log("Reduced lockout applied for user ID: " . $user['id'] . " due to random condition");
            }

            $cache->set($lockoutKey, $currentTime + $lockoutDuration, $lockoutDuration);
            $lockoutMinutes = $lockoutDuration / 60;
            throw new Exception("Account locked for $lockoutMinutes minutes due to repeated failed attempts.", 429);
         } elseif ($failedAttempts >= $maxFailedAttempts) {
            // First lockout - 15 minutes
            $lockoutDuration = 15 * 60;
            $cache->set($lockoutKey, $currentTime + $lockoutDuration, $lockoutDuration);
            throw new Exception("Account locked for 15 minutes due to failed login attempts.", 429);
         }

         // Log failed attempt
         error_log("Failed login attempt #$failedAttempts for user: " . $user['username'] . " from IP: " . $ipAddress);
         throw new Exception("Invalid email or password. Attempt: $failedAttempts", 401);
      } else {
         throw new Exception("Login URL does not match the provided email.", 401);
      }
   } catch (Exception $e) {
      $db->rollBack();
      error_log("Login error: " . $e->getMessage() . " | IP: " . $ipAddress . " | User Agent: " . $userAgent);

      $httpCode = $e->getCode();
      if ($httpCode < 100 || $httpCode >= 600) {
         $httpCode = 500;
      }

      sendJsonResponse(false, $e->getMessage(), [], $httpCode);
   }
}
