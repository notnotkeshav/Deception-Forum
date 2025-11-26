<?php

use Backend\Core\App;
use Backend\Utils\TOTP;
use Backend\Utils\Validator;

// Resolve database and cache instances from the container
$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$templateLoader = App::container()->resolve('Core\TemplateLoader');
$mailer = App::container()->resolve('Core\Mailer');

// Check if this is a session renewal after expiry
$isSessionRenewal = isset($_GET['action']) && $_GET['action'] === 'renew';
$expiryReason = $_GET['reason'] ?? null;
$returnTo = $_GET['returnTo'] ?? '/threads';

// Define TOTP security parameters
$maxFailedAttempts = 3;
$secondLockoutAttempts = 5;
$banAttempts = 7;
$maxSuspiciousAttempts = 5;

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
if ($ipAttempts > 15) {
    http_response_code(429);
    exit(json_encode(['success' => false, 'message' => 'Too many TOTP requests from this IP']));
}

// ========== SESSION RENEWAL HANDLING ==========
if ($isSessionRenewal && $expiryReason === 'expired') {
    // Check if we have expiry info
    $sessionExpired = $_SESSION['session_expired'] ?? false;
    $expiredUsername = $_SESSION['expired_username'] ?? null;

    if (!$sessionExpired || !$expiredUsername) {
        $_SESSION['flash']['error'] = 'Session expired. Please sign in again.';
        redirect('/signin');
        exit;
    }

    // Get user by username
    $statement = $db->query('SELECT * FROM users WHERE username = ? AND isDeleted = 0', [$expiredUsername]);
    $user = $db->getOne($statement);

    if (!$user) {
        $_SESSION['flash']['error'] = 'User not found. Please sign in again.';
        redirect('/signin');
        exit;
    }

    if (!$user['totp_enabled'] || empty($user['totp_secret'])) {
        $_SESSION['flash']['error'] = 'TOTP not enabled. Please sign in again.';
        redirect('/signin');
        exit;
    }

    // Store user info for renewal
    $_SESSION['renewal_user'] = $user;
    $_SESSION['renewal_username'] = $expiredUsername;
}

// ========== GET REQUEST HANDLING ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle session renewal display
    if ($isSessionRenewal && $expiryReason === 'expired') {
        $csrf_token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_renewal'] = $csrf_token;

        view("auth/totp-verify.view.php", [
            "heading" => "Session Renewal",
            "csrf_token" => $csrf_token,
            "is_session_renewal" => true,
            "expiry_reason" => $expiryReason,
            "return_to" => $returnTo,
            "username" => $_SESSION['renewal_username'] ?? null
        ]);
        exit;
    }

    // Display TOTP verification page for regular login
    if (empty($_SESSION['partial_auth'])) {
        redirect('/signin');
        exit;
    }

    // Validate partial auth hasn't expired
    if ($_SESSION['partial_auth']['expires'] < $currentTime) {
        unset($_SESSION['partial_auth']);
        unset($_SESSION['totp_user_info']);
        redirect('/signin');
        exit;
    }

    view("auth/totp-verify.view.php", [
        "heading" => "Two-Factor Verification",
        "csrf_token" => $_SESSION['partial_auth']['csrf_token'],
        "is_session_renewal" => false,
        "expiry_reason" => null,
        "return_to" => null,
        "username" => null
    ]);
    exit;
}

// ========== POST REQUEST HANDLING ==========
try {
    // Start a database transaction
    $db->beginTransaction();

    // Increment IP rate limiting
    $cache->set($ipRateLimitKey, $ipAttempts + 1, 3600);
    $cache->clearExpired();

    // Get the submitted code and action
    $code = trim($_POST['code'] ?? '');
    $action = $_POST['action'] ?? 'verify-login';

    // Enhanced input validation
    if (!Validator::digits($code, 6)) {
        throw new Exception('Invalid verification code format', 400);
    }

    // Additional security: check for sequential or repeated patterns
    if (preg_match('/^(.)\1{5}$/', $code) || preg_match('/^123456|654321|012345/', $code)) {
        error_log("Suspicious TOTP code pattern detected from IP: $ipAddress");
        throw new Exception('Invalid verification code', 400);
    }

    // ========== SESSION RENEWAL POST HANDLING ==========
    if ($action === 'renew-session' && !empty($_SESSION['renewal_user'])) {
        // CSRF protection for renewal
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token_renewal'] ?? '', $csrfToken)) {
            throw new Exception('Invalid request token', 400);
        }

        $user = $_SESSION['renewal_user'];
        $userId = $user['id'];
        $userIdHash = hash('sha256', $userId);

        // Rate limiting for session renewal
        $renewalRateLimitKey = "session_renewal_ip:" . hash('sha256', $ipAddress);
        $renewalAttempts = $cache->get($renewalRateLimitKey)['value'] ?? 0;

        if ($renewalAttempts > 10) {
            throw new Exception('Too many renewal attempts. Please wait 15 minutes.', 429);
        }

        // Check for lockout
        $lockoutKey = "totp_renewal_lockout:" . $userIdHash;
        $lockoutData = $cache->get($lockoutKey);
        $lockoutTime = $lockoutData['value'] ?? null;

        if ($lockoutTime && $lockoutTime > $currentTime) {
            $remainingLockout = ceil(($lockoutTime - $currentTime) / 60);
            throw new Exception("Renewal locked. Try again after $remainingLockout minutes.", 429);
        }

        // Prevent replay attacks for renewal
        $recentCodesKey = "totp_renewal_recent:" . $userIdHash;
        $recentCodes = $cache->get($recentCodesKey)['value'] ?? [];

        if (in_array($code, $recentCodes)) {
            throw new Exception("Code already used. Please wait for a new code.", 400);
        }

        // Verify TOTP code
        if (!TOTP::verify($user['totp_secret'], $code, 2)) {
            // Increment failed attempts
            $cache->set($renewalRateLimitKey, $renewalAttempts + 1, 900);

            $failedAttemptsKey = "totp_renewal_failed:" . $userIdHash;
            $failedAttempts = $cache->get($failedAttemptsKey)['value'] ?? 0;
            $failedAttempts++;
            $cache->set($failedAttemptsKey, $failedAttempts, 3600);

            if ($failedAttempts >= 3) {
                $lockoutDuration = 3600;
                $cache->set($lockoutKey, $currentTime + $lockoutDuration, $lockoutDuration);
                error_log("Session renewal locked for user: {$user['username']} after 3 failed attempts from IP: {$ipAddress}");
                throw new Exception("Too many failed attempts. Locked for 1 hour.", 429);
            }

            error_log("Failed session renewal attempt #{$failedAttempts} for user: {$user['username']} from IP: {$ipAddress}");
            throw new Exception("Invalid TOTP code. Attempt: {$failedAttempts}/3", 401);
        }

        // Success - Create new session
        // Store used code to prevent replay
        $recentCodes[] = $code;
        if (count($recentCodes) > 5) {
            array_shift($recentCodes);
        }
        $cache->set($recentCodesKey, $recentCodes, 180);

        // Clear failed attempts and rate limits
        $cache->delete($renewalRateLimitKey);
        $cache->delete($failedAttemptsKey);
        $cache->delete($lockoutKey);
        $cache->delete($ipRateLimitKey);

        // Clear old session data
        session_destroy();
        session_start();

        // Set new session with fresh timestamps
        $token = bin2hex(random_bytes(32));
        $_SESSION['userId'] = $userId;
        $_SESSION['user'] = $user;
        $_SESSION['token'] = $token;
        $_SESSION['session_started'] = $currentTime;
        $_SESSION['last_activity'] = $currentTime;
        $_SESSION['token_expiration'] = $currentTime + (24 * 60 * 60);
        $_SESSION['totp_verified'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Check if user is moderator
        $moderatorKey = "moderator:" . $userIdHash;
        $moderatorData = $cache->get($moderatorKey);

        if (!$moderatorData || $moderatorData['expiration'] < $currentTime) {
            $stmt = $db->query("SELECT id, role FROM moderators WHERE userId = :userId", [":userId" => $userId]);
            $response = $db->getOne($stmt);
            $isModerator = (bool)$response;
            $cache->set($moderatorKey, $isModerator, 3600);
        } else {
            $isModerator = $moderatorData['value'];
        }

        $_SESSION['moderator'] = $isModerator;

        // Update last login
        $db->query('UPDATE users SET lastLogin = CURRENT_TIMESTAMP WHERE id = ?', [$userId]);

        // Clear expiry flags
        unset($_SESSION['session_expired']);
        unset($_SESSION['expired_username']);
        unset($_SESSION['renewal_user']);
        unset($_SESSION['csrf_token_renewal']);
        unset($_SESSION['renewal_username']);

        $db->commit();

        error_log("Session successfully renewed for user: {$user['username']}");

        sendJsonResponse(true, 'Session renewed successfully', [
            'session' => [
                'token' => $token,
                'userId' => $userId,
                'user' => $user,
                'moderator' => $isModerator
            ],
            'redirect' => $returnTo
        ]);
        exit;
    }

    // ========== REGULAR TOTP VERIFICATION ==========
    if (empty($_SESSION['partial_auth'])) {
        throw new Exception('No pending authentication', 401);
    }

    // Check if partial auth has expired
    if ($_SESSION['partial_auth']['expires'] < $currentTime) {
        unset($_SESSION['partial_auth']);
        unset($_SESSION['totp_user_info']);
        throw new Exception('Authentication session expired. Please login again.', 401);
    }

    // CSRF protection for regular login
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['partial_auth']['csrf_token'], $csrfToken)) {
        throw new Exception('Invalid request token', 400);
    }

    $userId = $_SESSION['partial_auth']['userId'];
    $userIdHash = hash('sha256', $userId);

    // Get user's TOTP secret from cache first
    $userCacheKey = "user_totp:" . $userIdHash;
    $cachedUser = $cache->get($userCacheKey);

    if (!$cachedUser || $cachedUser['expiration'] < $currentTime) {
        $statement = $db->query('SELECT * FROM users WHERE id = ? AND isDeleted = 0', [$userId]);
        $user = $db->getOne($statement);

        if (!$user) {
            throw new Exception('User account not found', 404);
        }

        $cache->set($userCacheKey, $user, 1800);
    } else {
        $user = $cachedUser['value'];
    }

    if (!$user || empty($user['totp_secret']) || !$user['totp_enabled']) {
        throw new Exception('TOTP not properly configured for this account', 400);
    }

    // Define keys for tracking lockouts, failed attempts, and suspicious IPs
    $lockoutKey = "totp_lockout:" . $userIdHash;
    $failedAttemptsKey = "totp_failed_attempts:" . $userIdHash;
    $suspiciousIPKey = "totp_suspicious_ip:" . $userIdHash;
    $deviceFingerprintKey = "totp_device:" . $userIdHash . ":" . hash('sha256', $userAgent);

    // Check if the account is locked
    $lockoutData = $cache->get($lockoutKey);
    $lockoutTime = $lockoutData['value'] ?? null;
    if ($lockoutTime && $lockoutTime > $currentTime) {
        $remainingLockout = ceil(($lockoutTime - $currentTime) / 60);
        throw new Exception("TOTP verification locked. Try again after $remainingLockout minutes.", 429);
    }

    // Enhanced suspicious IP tracking
    $suspiciousIPs = $cache->get($suspiciousIPKey)['value'] ?? [];
    $ipHash = hash('sha256', $ipAddress);
    if (!in_array($ipHash, $suspiciousIPs)) {
        $suspiciousIPs[] = $ipHash;
        $cache->set($suspiciousIPKey, $suspiciousIPs, 86400);

        if (count($suspiciousIPs) >= $maxSuspiciousAttempts) {
            error_log("Multiple IP TOTP access attempt for user ID: " . $user['id'] . " from IP: " . $ipAddress);
            throw new Exception("Account flagged for suspicious TOTP activity. Contact support.", 403);
        }
    }

    // Device fingerprinting
    $knownDevice = $cache->get($deviceFingerprintKey);
    if (!$knownDevice) {
        $cache->set($deviceFingerprintKey, ['first_seen' => $currentTime, 'trusted' => false], 86400);
    }

    // Prevent replay attacks
    $recentCodesKey = "totp_recent:" . $userIdHash;
    $recentCodes = $cache->get($recentCodesKey)['value'] ?? [];
    if (in_array($code, $recentCodes)) {
        throw new Exception("Code already used. Please wait for a new code.", 400);
    }

    if (TOTP::verify($user['totp_secret'], $code, 2)) {
        // Clear failed attempts, lockouts, and suspicious IPs
        $cache->delete($failedAttemptsKey);
        $cache->delete($lockoutKey);
        $cache->delete($suspiciousIPKey);
        $cache->delete($ipRateLimitKey);

        // Store used code to prevent replay
        $recentCodes[] = $code;
        if (count($recentCodes) > 5) {
            array_shift($recentCodes);
        }
        $cache->set($recentCodesKey, $recentCodes, 180);

        // Clear partial auth sessions
        unset($_SESSION['partial_auth']);
        unset($_SESSION['totp_user_info']);

        // Update last login time and refresh user cache
        $db->query('UPDATE users SET lastLogin = CURRENT_TIMESTAMP WHERE id = ?', [$userId]);
        $cache->delete($userCacheKey);

        // Create complete authentication session
        $token = generateToken();
        $expiresAt = time() + (24 * 60 * 60);

        $_SESSION['totp_verified'] = true;
        $_SESSION['token'] = $token;
        $_SESSION['token_expiration'] = $expiresAt;
        $_SESSION['session_started'] = $currentTime;
        $_SESSION['last_activity'] = $currentTime;
        $_SESSION['userId'] = $user['id'];
        $_SESSION['user'] = $user;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Check moderator status with caching
        $moderatorKey = "moderator:" . $userIdHash;
        $moderatorData = $cache->get($moderatorKey);

        if (!$moderatorData || $moderatorData['expiration'] < $currentTime) {
            $stmt = $db->query("SELECT id, role FROM moderators WHERE userId = :userId", [":userId" => $userId]);
            $response = $db->getOne($stmt);
            $isModerator = (bool)$response;
            $cache->set($moderatorKey, $isModerator, 3600);
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
        // TOTP verification failure - 3 strikes and 1 day ban
        $failedAttempts = $cache->get($failedAttemptsKey)['value'] ?? 0;
        $failedAttempts++;
        $cache->set($failedAttemptsKey, $failedAttempts, 86400);

        if ($failedAttempts >= 3) {
            $lockoutDuration = 86400;
            $cache->set($lockoutKey, $currentTime + $lockoutDuration, $lockoutDuration);
            error_log("TOTP account locked for user ID: " . $user['id'] . " after 3 failed attempts from IP: " . $ipAddress);
            throw new Exception("Account locked for 24 hours due to repeated TOTP failures.", 429);
        }

        error_log("Failed TOTP attempt #$failedAttempts for user: " . $user['username'] . " from IP: " . $ipAddress);
        $db->rollback();
        throw new Exception("Invalid verification code. Attempt: $failedAttempts", 401);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("TOTP verification error: " . $e->getMessage() . " | IP: " . $ipAddress . " | User Agent: " . $userAgent);

    $httpCode = $e->getCode();
    if ($httpCode < 100 || $httpCode >= 600) {
        $httpCode = 500;
    }

    sendJsonResponse(false, $e->getMessage(), [], $httpCode);
}
