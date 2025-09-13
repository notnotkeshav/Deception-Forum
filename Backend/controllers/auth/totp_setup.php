<?php

use Backend\Core\App;
use Backend\Utils\TOTP;
use Backend\Utils\Validator;
use Backend\Core\Cache;

// Resolve database instance from the container
$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$templateLoader = App::container()->resolve('Core\TemplateLoader');

$ipAddress = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$currentTime = time();

// Enhanced security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Rate limiting for IP address
$ipRateLimitKey = "totp_setup_ip:" . hash('sha256', $ipAddress);
$ipAttempts = $cache->get($ipRateLimitKey)['value'] ?? 0;
if ($ipAttempts > 10) { // Max 10 setup attempts per IP per hour
    http_response_code(429);
    exit(json_encode(['success' => false, 'message' => 'Too many TOTP setup requests from this IP']));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if user has proper authentication
    if (empty($_SESSION['partial_auth']) && empty($_SESSION['userId'])) {
        header('Location: /signin');
        exit();
    }

    // For setup during login flow
    if (!empty($_SESSION['partial_auth'])) {
        if ($_SESSION['partial_auth']['expires'] < $currentTime) {
            unset($_SESSION['partial_auth']);
            unset($_SESSION['totp_user_info']);
            header('Location: /signin');
            exit();
        }

        $userId = $_SESSION['partial_auth']['userId'];
        // FIX: Get CSRF token from partial_auth
        $csrfToken = $_SESSION['partial_auth']['csrf_token'];
    } else {
        // For setup from authenticated session
        $userId = $_SESSION['userId'];
        // FIX: Generate CSRF token if not exists for authenticated users
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];
    }

    $userIdHash = hash('sha256', $userId);
    $userCacheKey = "user_totp:" . $userIdHash;

    // Check cache first
    $cachedUser = $cache->get($userCacheKey);
    if (!$cachedUser || $cachedUser['expiration'] < $currentTime) {
        $statement = $db->query('SELECT id, username, email, totp_enabled, isDeleted FROM users WHERE id = ? AND isDeleted = 0', [$userId]);
        $user = $db->getOne($statement);

        if (!$user) {
            header('Location: /signin');
            exit();
        }

        $cache->set($userCacheKey, $user, 1800); // 30 minutes
    } else {
        $user = $cachedUser['value'];
    }

    view("auth/totp-setup.view.php", [
        "heading" => "Two-Factor Authentication",
        "totpEnabled" => $user['totp_enabled'] ?? false,
        "csrf_token" => $csrfToken // FIX: Pass the correct CSRF token
    ]);
} else {
    // Set proper JSON header
    header('Content-Type: application/json');

    try {
        // Increment IP rate limiting
        $cache->set($ipRateLimitKey, $ipAttempts + 1, 3600); // 1 hour expiry
        $cache->clearExpired();

        $action = $_POST['action'] ?? '';

        // FIX: Determine user ID and CSRF token based on authentication state
        if (!empty($_SESSION['partial_auth'])) {
            if ($_SESSION['partial_auth']['expires'] < $currentTime) {
                unset($_SESSION['partial_auth']);
                unset($_SESSION['totp_user_info']);
                throw new Exception('Authentication session expired. Please login again.', 401);
            }
            $userId = $_SESSION['partial_auth']['userId'];
            $csrfToken = $_SESSION['partial_auth']['csrf_token'];
        } else if (!empty($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
            // FIX: Generate CSRF token if not exists
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            $csrfToken = $_SESSION['csrf_token'];
        } else {
            throw new Exception('No valid authentication session', 401);
        }

        $userIdHash = hash('sha256', $userId);

        // Enhanced rate limiting per user
        $userRateLimitKey = "totp_setup_user:" . $userIdHash;
        if (isRateLimited($userRateLimitKey, 5, 300, $cache)) { // 5 attempts per 5 minutes
            throw new Exception('Too many attempts. Please try again later.', 429);
        }

        // Get user data from cache or database
        $userCacheKey = "user_totp:" . $userIdHash;
        $cachedUser = $cache->get($userCacheKey);

        if (!$cachedUser || $cachedUser['expiration'] < $currentTime) {
            $statement = $db->query('SELECT id, username, email, name, passwordHash, totp_enabled, totp_secret, isDeleted FROM users WHERE id = ? AND isDeleted = 0', [$userId]);
            $user = $db->getOne($statement);

            if (!$user) {
                throw new Exception('User account not found', 404);
            }

            $cache->set($userCacheKey, $user, 1800); // 30 minutes
        } else {
            $user = $cachedUser['value'];
        }

        switch ($action) {
            case 'enable':
                // FIX: CSRF protection with correct token
                $submittedToken = $_POST['csrf_token'] ?? '';
                if (!hash_equals($csrfToken, $submittedToken)) {
                    throw new Exception('Invalid request token', 400);
                }

                // Check if TOTP is already enabled
                if ($user['totp_enabled']) {
                    throw new Exception('TOTP is already enabled for this account', 400);
                }

                // Generate new TOTP secret with enhanced entropy
                $secret = TOTP::generateSecret(32); // Longer secret for better security
                $qrCodeUrl = TOTP::getQRCodeUrl($user['username'], $secret, 'Red Skull');

                // Store temporarily in session with enhanced security
                $_SESSION['totp_setup'] = [
                    'secret' => $secret,
                    'expires' => time() + 600, // 10 minutes to complete setup
                    'csrf_token' => bin2hex(random_bytes(32)), // New CSRF token for verification
                    'user_id' => $userId,
                    'ip_hash' => hash('sha256', $ipAddress),
                    'attempts' => 0
                ];

                sendJsonResponse(true, 'TOTP setup initiated', [
                    'secret' => $secret,
                    'qrCodeUrl' => $qrCodeUrl,
                    'csrf_token' => $_SESSION['totp_setup']['csrf_token']
                ]);
                break;

            case 'verify-setup':
                if (empty($_SESSION['totp_setup'])) {
                    throw new Exception('TOTP setup not initiated or session expired', 400);
                }

                // Check if setup session expired
                if (time() > $_SESSION['totp_setup']['expires']) {
                    unset($_SESSION['totp_setup']);
                    throw new Exception('Setup session expired. Please start over.', 400);
                }

                // Enhanced CSRF protection
                $setupCsrfToken = $_POST['csrf_token'] ?? '';
                if (!hash_equals($_SESSION['totp_setup']['csrf_token'], $setupCsrfToken)) {
                    throw new Exception('Invalid request token', 400);
                }

                // Verify user ID matches
                if ($_SESSION['totp_setup']['user_id'] !== $userId) {
                    throw new Exception('Session mismatch error', 400);
                }

                // Enhanced input validation
                $code = trim($_POST['code'] ?? '');
                if (!Validator::digits($code, 6)) {
                    incrementRateLimit($userRateLimitKey, $cache);
                    throw new Exception('Invalid verification code format', 400);
                }

                // Check for suspicious patterns
                if (preg_match('/^(.)\1{5}$/', $code) || preg_match('/^123456|654321|012345/', $code)) {
                    error_log("Suspicious TOTP setup code pattern detected from IP: $ipAddress for user ID: $userId");
                    incrementRateLimit($userRateLimitKey, $cache);
                    throw new Exception('Invalid verification code', 400);
                }

                // Track setup attempts
                $_SESSION['totp_setup']['attempts']++;
                if ($_SESSION['totp_setup']['attempts'] > 5) {
                    unset($_SESSION['totp_setup']);
                    throw new Exception('Too many verification attempts. Please start over.', 429);
                }

                $secret = $_SESSION['totp_setup']['secret'];

                // Verify TOTP code with time window tolerance
                if (TOTP::verify($secret, $code, 2)) { // Allow 2 time windows (±60 seconds)
                    // Begin database transaction
                    $db->beginTransaction();

                    try {
                        // Store the secret in database
                        $updateStatement = $db->query('UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?', [
                            $secret,
                            $userId
                        ]);

                        // Log the security event
                        error_log("TOTP enabled for user ID: $userId from IP: $ipAddress");

                        $db->commit();

                        // Update cache
                        $user['totp_enabled'] = true;
                        $user['totp_secret'] = $secret;
                        $cache->set($userCacheKey, $user, 1800);
                        $cache->delete($ipRateLimitKey); // Clear IP rate limit on success

                        // Clear setup session
                        unset($_SESSION['totp_setup']);
                        clearRateLimit($userRateLimitKey, $cache);

                        // If this was during login flow, redirect to verification
                        if (!empty($_SESSION['partial_auth'])) {
                            sendJsonResponse(true, 'TOTP enabled successfully. Please verify to continue.', [
                                'redirect' => '/verify-totp',
                                'totp_required' => true
                            ]);
                        } else {
                            // Update session user data if already logged in
                            if (!empty($_SESSION['user'])) {
                                $_SESSION['user']['totp_enabled'] = true;
                            }
                            sendJsonResponse(true, 'TOTP enabled successfully');
                        }
                    } catch (Exception $e) {
                        $db->rollback();
                        throw $e;
                    }
                } else {
                    incrementRateLimit($userRateLimitKey, $cache);
                    throw new Exception('Invalid verification code. Please try again.', 400);
                }
                break;

            case 'disable':
                // Only allow disabling if fully authenticated
                if (!empty($_SESSION['partial_auth'])) {
                    throw new Exception('Please complete authentication first', 401);
                }

                // CSRF protection
                $submittedToken = $_POST['csrf_token'] ?? '';
                if (!hash_equals($csrfToken, $submittedToken)) {
                    throw new Exception('Invalid request token', 400);
                }

                // Verify password before disabling TOTP
                $password = $_POST['password'] ?? '';
                if (empty($password)) {
                    throw new Exception('Password is required', 400);
                }

                if (!$user['totp_enabled']) {
                    throw new Exception('TOTP is not enabled for this account', 400);
                }

                if (!password_verify($password, $user['passwordHash'])) {
                    incrementRateLimit($userRateLimitKey, $cache);
                    error_log("Failed password verification for TOTP disable attempt by user ID: $userId from IP: $ipAddress");
                    throw new Exception('Invalid password', 401);
                }

                // Begin database transaction
                $db->beginTransaction();

                try {
                    // Disable TOTP
                    $disableStatement = $db->query('UPDATE users SET totp_secret = NULL, totp_enabled = 0, totp_disabled_at = CURRENT_TIMESTAMP WHERE id = ?', [$userId]);

                    // Log the security event
                    error_log("TOTP disabled for user ID: $userId from IP: $ipAddress");

                    $db->commit();

                    // Update cache
                    $user['totp_enabled'] = false;
                    $user['totp_secret'] = null;
                    $cache->set($userCacheKey, $user, 1800);
                    $cache->delete($ipRateLimitKey); // Clear IP rate limit on success

                    // Update session
                    if (!empty($_SESSION['user'])) {
                        $_SESSION['user']['totp_enabled'] = false;
                    }
                    unset($_SESSION['totp_verified']);

                    clearRateLimit($userRateLimitKey, $cache);
                    sendJsonResponse(true, 'TOTP disabled successfully');
                } catch (Exception $e) {
                    $db->rollback();
                    throw $e;
                }
                break;

            default:
                throw new Exception('Invalid action', 400);
        }
    } catch (Exception $e) {
       error_log("TOTP setup error: " . $e->getMessage() . " | IP: " . $ipAddress . " | User Agent: " . $userAgent);

        $httpCode = $e->getCode();
        // Fix: Ensure httpCode is always a valid integer
        if (!is_int($httpCode) || $httpCode < 100 || $httpCode >= 600) {
            $httpCode = 500; // Default to internal server error
        }

        sendJsonResponse(false, $e->getMessage(), [], $httpCode);
    }
}

// Enhanced rate limiting helper functions with cache integration
function isRateLimited(string $key, int $maxAttempts, int $windowSeconds, Cache $cache): bool
{
    $attempts = $cache->get($key)['value'] ?? [];
    $now = time();

    // Clean old attempts
    $attempts = array_filter($attempts, fn($timestamp) => $now - $timestamp < $windowSeconds);

    return count($attempts) >= $maxAttempts;
}

function incrementRateLimit(string $key, Cache $cache): void
{
    $attempts = $cache->get($key)['value'] ?? [];
    $attempts[] = time();
    $cache->set($key, $attempts, 3600); // Store for 1 hour
}

function clearRateLimit(string $key, Cache $cache): void
{
    $cache->delete($key);
}