<?php

use Backend\Core\App;
use Backend\Utils\TOTP;
use Backend\Utils\Validator;

// Resolve database and cache instances from the container
$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$templateLoader = App::container()->resolve('Core\TemplateLoader');
$mailer = App::container()->resolve('Core\Mailer');

// Define TOTP security parameters
$maxFailedAttempts = 3; // First lockout after 3 attempts
$secondLockoutAttempts = 5; // Second lockout after 5 total attempts  
$banAttempts = 7; // Ban after 7 total attempts
$maxSuspiciousAttempts = 5; // Maximum number of suspicious IPs allowed

$ipAddress = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$currentTime = time();

// Enhanced security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Rate limiting for IP address
$ipRateLimitKey = "totp_ip_attempts:" . hash('sha256', $ipAddress);
$ipAttempts = $cache->get($ipRateLimitKey)['value'] ?? 0;
if ($ipAttempts > 15) { // Max 15 TOTP attempts per IP per hour
    http_response_code(429);
    exit(json_encode(['success' => false, 'message' => 'Too many TOTP requests from this IP']));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Display TOTP verification page
    if (empty($_SESSION['partial_auth'])) {
        sendJsonResponse(false, 'No pending authentication', [], 401);
        exit();
    }

    // Validate partial auth hasn't expired
    if ($_SESSION['partial_auth']['expires'] < $currentTime) {
        unset($_SESSION['partial_auth']);
        unset($_SESSION['totp_user_info']);
        sendJsonResponse(false, 'Authentication session expired', [], 401);
        exit();
    }

    view("auth/totp-verify.view.php", [
        "heading" => "Two-Factor Verification",
        "csrf_token" => $_SESSION['partial_auth']['csrf_token']
    ]);
} else {
    try {
        // Start a database transaction
        $db->beginTransaction();

        // Increment IP rate limiting
        $cache->set($ipRateLimitKey, $ipAttempts + 1, 3600); // 1 hour expiry

        $cache->clearExpired();

        if (empty($_SESSION['partial_auth'])) {
            throw new Exception('No pending authentication', 401);
        }

        // Check if partial auth has expired
        if ($_SESSION['partial_auth']['expires'] < $currentTime) {
            unset($_SESSION['partial_auth']);
            unset($_SESSION['totp_user_info']);
            throw new Exception('Authentication session expired. Please login again.', 401);
        }

        // CSRF protection
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['partial_auth']['csrf_token'], $csrfToken)) {
            throw new Exception('Invalid request token', 400);
        }

        // Enhanced input validation
        $code = trim($_POST['code'] ?? '');
        if (!Validator::digits($code, 6)) {
            throw new Exception('Invalid verification code format', 400);
        }

        // Additional security: check for sequential or repeated patterns
        if (preg_match('/^(.)\1{5}$/', $code) || preg_match('/^123456|654321|012345/', $code)) {
            error_log("Suspicious TOTP code pattern detected from IP: $ipAddress");
            throw new Exception('Invalid verification code', 400);
        }

        $userId = $_SESSION['partial_auth']['userId'];
        $userIdHash = hash('sha256', $userId);

        // Get user's TOTP secret from cache first
        $userCacheKey = "user_totp:" . $userIdHash;
        $cachedUser = $cache->get($userCacheKey);
        
        if (!$cachedUser || $cachedUser['expiration'] < $currentTime) {
            // Cache miss - fetch from database
            $statement = $db->query('SELECT id, username, email, name, totp_secret, totp_enabled, isDeleted FROM users WHERE id = ? AND isDeleted = 0', [$userId]);
            $user = $db->getOne($statement);
            
            if (!$user) {
                throw new Exception('User account not found', 404);
            }
            
            // Cache user data
            $cache->set($userCacheKey, $user, 1800); // 30 minutes
        } else {
            $user = $cachedUser['value'];
        }

        if (!$user || empty($user['totp_secret']) || !$user['totp_enabled']) {
            throw new Exception('TOTP not properly configured for this account', 400);
        }

        // Define keys for tracking lockouts, failed attempts, and suspicious IPs for TOTP
        $lockoutKey = "totp_lockout:" . $userIdHash;
        $failedAttemptsKey = "totp_failed_attempts:" . $userIdHash;
        $suspiciousIPKey = "totp_suspicious_ip:" . $userIdHash;
        $deviceFingerprintKey = "totp_device:" . $userIdHash . ":" . hash('sha256', $userAgent);

        // Check if the account is locked for TOTP
        $lockoutData = $cache->get($lockoutKey);
        $lockoutTime = $lockoutData['value'] ?? null;
        if ($lockoutTime && $lockoutTime > $currentTime) {
            $remainingLockout = ceil(($lockoutTime - $currentTime) / 60);
            throw new Exception("TOTP verification locked. Try again after $remainingLockout minutes.", 429);
        }

        // Enhanced suspicious IP tracking for TOTP
        $suspiciousIPs = $cache->get($suspiciousIPKey)['value'] ?? [];
        $ipHash = hash('sha256', $ipAddress);
        if (!in_array($ipHash, $suspiciousIPs)) {
            $suspiciousIPs[] = $ipHash;
            $cache->set($suspiciousIPKey, $suspiciousIPs, 86400); // 24 hours

            if (count($suspiciousIPs) >= $maxSuspiciousAttempts) {
                error_log("Multiple IP TOTP access attempt for user ID: " . $user['id'] . " from IP: " . $ipAddress);
                throw new Exception("Account flagged for suspicious TOTP activity. Contact support.", 403);
            }
        }

        // Device fingerprinting for TOTP
        $knownDevice = $cache->get($deviceFingerprintKey);
        if (!$knownDevice) {
            $cache->set($deviceFingerprintKey, ['first_seen' => $currentTime, 'trusted' => false], 86400);
        }

        // Prevent replay attacks - store recent codes
        $recentCodesKey = "totp_recent:" . $userIdHash;
        $recentCodes = $cache->get($recentCodesKey)['value'] ?? [];
        if (in_array($code, $recentCodes)) {
            throw new Exception("Code already used. Please wait for a new code.", 400);
        }

        if (TOTP::verify($user['totp_secret'], $code, 2)) {
            // Clear failed attempts, lockouts, and suspicious IPs for TOTP
            $cache->delete($failedAttemptsKey);
            $cache->delete($lockoutKey);
            $cache->delete($suspiciousIPKey);
            $cache->delete($ipRateLimitKey);

            // Store used code to prevent replay
            $recentCodes[] = $code;
            if (count($recentCodes) > 5) {
                array_shift($recentCodes); // Keep only last 5 codes
            }
            $cache->set($recentCodesKey, $recentCodes, 180); // 3 minutes

            // Clear partial auth sessions
            unset($_SESSION['partial_auth']);
            unset($_SESSION['totp_user_info']);

            // Update last login time and refresh user cache
            $updateStatement = $db->query('UPDATE users SET lastLogin = CURRENT_TIMESTAMP WHERE id = ?', [$userId]);
            $cache->delete($userCacheKey); // Refresh cache on next access

            // Create complete authentication session ONLY after TOTP verification
            $token = generateToken();
            $expiresAt = time() + (24 * 60 * 60);

            $_SESSION['totp_verified'] = true;
            $_SESSION['token'] = $token;
            $_SESSION['token_expiration'] = $expiresAt;
            $_SESSION['userId'] = $user['id'];
            $_SESSION['user'] = $user;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate CSRF token for app use

            // Check moderator status with caching
            $moderatorKey = "moderator:" . $userIdHash;
            $moderatorData = $cache->get($moderatorKey);
            
            if (!$moderatorData || $moderatorData['expiration'] < $currentTime) {
                $stmt = $db->query("SELECT id, role FROM moderators WHERE userId = :userId", [":userId" => $userId]);
                $response = $db->getOne($stmt);
                $isModerator = (bool)$response;
                $cache->set($moderatorKey, $isModerator, 3600); // 1 hour
            } else {
                $isModerator = $moderatorData['value'];
            }
            
            $_SESSION['moderator'] = $isModerator;

            $db->commit();
            sendJsonResponse(true, 'Authentication successful', [
                'session' => [
                    'userId' => $_SESSION['userId'],
                    'username' => $user['username'],
                    'moderator' => $_SESSION['moderator'],
                    'totp_verified' => true
                ]
            ]);
        } else {
            // Simple TOTP verification failure handling - 3 strikes and 1 day ban
            $failedAttempts = $cache->get($failedAttemptsKey)['value'] ?? 0;
            $failedAttempts++;
            $cache->set($failedAttemptsKey, $failedAttempts, 86400); // 24 hours

            if ($failedAttempts >= 3) {
                // Lock for 1 day after 3 failed attempts
                $lockoutDuration = 86400; // 1 day
                $cache->set($lockoutKey, $currentTime + $lockoutDuration, $lockoutDuration);
                
                // Log security event
                error_log("TOTP account locked for user ID: " . $user['id'] . " after 3 failed attempts from IP: " . $ipAddress);
                
                throw new Exception("Account locked for 24 hours due to repeated TOTP failures.", 429);
            }
            
            // Log failed attempt
            error_log("Failed TOTP attempt #$failedAttempts for user: " . $user['username'] . " from IP: " . $ipAddress);
            
            $db->rollback();
            throw new Exception("Invalid verification code. Attempt: $failedAttempts", 401);
        }
    } catch (Exception $e) {
        $db->rollBack();
        error_log("TOTP verification error: " . $e->getMessage() . " | IP: " . $ipAddress . " | User Agent: " . $userAgent);
        
        $httpCode = $e->getCode();
        if ($httpCode < 100 || $httpCode >= 600) {
            $httpCode = 500;
        }
        
        sendJsonResponse(false, $e->getMessage(), [], $httpCode);
    }
}