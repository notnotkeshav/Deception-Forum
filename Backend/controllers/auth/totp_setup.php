<?php

use Backend\Core\App;
use Backend\Utils\TOTP;
use Backend\Utils\Validator;

// Resolve database instance from the container
$db = App::container()->resolve('Core\Database');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Display TOTP setup page

    // Check if TOTP is already enabled using proper Database methods
    $statement = $db->query('SELECT totp_enabled FROM users WHERE id = ?', [$_SESSION['user']['id']]);
    $user = $db->getOne($statement);

    view("auth/totp-setup.view.php", [
        "heading" => "Two-Factor Authentication",
        "totpEnabled" => $user['totp_enabled'] ?? false
    ]);
} else {
    // Set proper JSON header
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? '';
        $userId = $_SESSION['user']['id'];

        // Rate limiting check
        $rateLimitKey = "totp_setup_{$userId}";
        if (isRateLimited($rateLimitKey, 5, 300)) { // 5 attempts per 5 minutes
            throw new Exception('Too many attempts. Please try again later.', 429);
        }

        switch ($action) {
            case 'enable':
                // Check if TOTP is already enabled
                $statement = $db->query('SELECT totp_enabled FROM users WHERE id = ?', [$userId]);
                $user = $db->getOne($statement);

                if ($user['totp_enabled']) {
                    throw new Exception('TOTP is already enabled for this account', 400);
                }

                // Generate new TOTP secret
                $secret = TOTP::generateSecret();
                $qrCodeUrl = TOTP::getQRCodeUrl($_SESSION['user']['username'], $secret, 'YourForumName');

                // Store temporarily in session until verified with additional security
                $_SESSION['totp_setup'] = [
                    'secret' => $secret,
                    'expires' => time() + 600, // 10 minutes to complete setup
                    'csrf_token' => bin2hex(random_bytes(32)) // CSRF protection
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

                // CSRF protection
                $csrfToken = $_POST['csrf_token'] ?? '';
                if (!hash_equals($_SESSION['totp_setup']['csrf_token'], $csrfToken)) {
                    throw new Exception('Invalid request. Please try again.', 400);
                }

                $code = trim($_POST['code'] ?? '');
                if (!Validator::digits($code, 6)) {
                    incrementRateLimit($rateLimitKey);
                    throw new Exception('Invalid verification code format', 400);
                }

                $secret = $_SESSION['totp_setup']['secret'];

                // Verify TOTP code with time window tolerance
                if (TOTP::verify($secret, $code, 2)) { // Allow 2 time windows (±60 seconds)
                    // Begin database transaction
                    $db->beginTransaction();

                    try {
                        // Store the secret in database using proper Database methods
                        $updateStatement = $db->query('UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?', [
                            $secret,
                            $userId
                        ]);

                        // Log the security event
                        // $logStatement = $db->query(
                        //     'INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())',
                        //     [
                        //         $userId,
                        //         'totp_enabled',
                        //         $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        //         $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        //     ]
                        // );

                        $db->commit();

                        // Update session
                        unset($_SESSION['totp_setup']);

                        // Clear rate limiting on success
                        clearRateLimit($rateLimitKey);

                        sendJsonResponse(true, 'TOTP enabled successfully');
                    } catch (Exception $e) {
                        $db->rollback();
                        throw $e;
                    }
                } else {
                    incrementRateLimit($rateLimitKey);
                    throw new Exception('Invalid verification code. Please try again.', 400);
                }
                break;

            case 'disable':
                // Verify password before disabling TOTP using proper Database methods
                $password = $_POST['password'] ?? '';
                $csrfToken = $_POST['csrf_token'] ?? '';

                if (empty($password)) {
                    throw new Exception('Password is required', 400);
                }

                // CSRF protection for disable action
                if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
                    throw new Exception('Invalid request. Please try again.', 400);
                }

                $statement = $db->query('SELECT passwordHash, totp_enabled FROM users WHERE id = ?', [$userId]);
                $user = $db->getOne($statement);

                if (!$user['totp_enabled']) {
                    throw new Exception('TOTP is not enabled for this account', 400);
                }

                if (!password_verify($password, $user['passwordHash'])) {
                    incrementRateLimit($rateLimitKey);
                    throw new Exception('Invalid password', 401);
                }

                // Begin database transaction
                $db->beginTransaction();

                try {
                    // Disable TOTP using proper Database methods
                    $disableStatement = $db->query('UPDATE users SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?', [$userId]);

                    // Log the security event
                    // $logStatement = $db->query(
                    //     'INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())',
                    //     [
                    //         $userId,
                    //         'totp_disabled',
                    //         $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    //         $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    //     ]
                    // );

                    $db->commit();

                    // Update session
                    $_SESSION['user']['totp_enabled'] = false;
                    unset($_SESSION['totp_verified']);

                    // Clear rate limiting on success
                    clearRateLimit($rateLimitKey);

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
        $httpCode = (int)$e->getCode() ?? 500;
        sendJsonResponse(false, $e->getMessage(), [], $httpCode);
    }
}

// Rate limiting helper functions
function isRateLimited(string $key, int $maxAttempts, int $windowSeconds): bool
{
    $attempts = $_SESSION["rate_limit_{$key}"] ?? [];
    $now = time();

    // Clean old attempts
    $attempts = array_filter($attempts, fn($timestamp) => $now - $timestamp < $windowSeconds);

    return count($attempts) >= $maxAttempts;
}

function incrementRateLimit(string $key): void
{
    $attempts = $_SESSION["rate_limit_{$key}"] ?? [];
    $attempts[] = time();
    $_SESSION["rate_limit_{$key}"] = $attempts;
}

function clearRateLimit(string $key): void
{
    unset($_SESSION["rate_limit_{$key}"]);
}
