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
$maxFailedAttempts = 3; // Maximum failed TOTP attempts before locking
$maxSuspiciousAttempts = 5; // Maximum number of suspicious IPs allowed
$maxLockouts = 5; // Maximum number of lockouts before permanent account lock

$ipAddress = $_SERVER['REMOTE_ADDR'];
$currentTime = time();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Display TOTP verification page
    if (empty($_SESSION['partial_auth'])) {
        sendJsonResponse(false, 'No pending authentication', [], 401);
    }

    view("auth/totp-verify.view.php", [
        "heading" => "Two-Factor Verification"
    ]);
} else {
    try {
        // Start a database transaction
        $db->beginTransaction();

        if (empty($_SESSION['partial_auth'])) {
            throw new Exception('No pending authentication', 401);
        }

        // Check if partial auth has expired
        if ($_SESSION['partial_auth']['expires'] < $currentTime) {
            unset($_SESSION['partial_auth']);
            unset($_SESSION['totp_user_info']);
            throw new Exception('Authentication session expired. Please login again.', 401);
        }

        $code = $_POST['code'] ?? '';
        if (!Validator::digits($code, 6)) {
            throw new Exception('Invalid verification code format', 400);
        }

        $userId = $_SESSION['partial_auth']['userId'];

        // Get user's TOTP secret using proper Database methods
        $statement = $db->query('SELECT * FROM users WHERE id = ? AND isDeleted = 0', [$userId]);
        $user = $db->getOne($statement);

        if (!$user || empty($user['totp_secret'])) {
            throw new Exception('TOTP not configured for this account', 400);
        }

        // Clear expired cache entries
        $cache->clearExpired();

        // Define keys for tracking lockouts, failed attempts, and suspicious IPs for TOTP
        $lockoutKey = "totp_lockout:" . $user['id'];
        $failedAttemptsKey = "totp_failed_attempts:" . $user['id'];
        $suspiciousIPKey = "totp_suspicious_ip:" . $user['id'];
        $lockoutsKey = "totp_lockouts:" . $user['id'];

        // Check if the account is locked for TOTP
        $lockoutTime = $cache->get($lockoutKey)['value'] ?? null;
        if ($lockoutTime && $lockoutTime > $currentTime) {
            $remainingLockout = ceil(($lockoutTime - $currentTime) / 60);
            throw new Exception("TOTP verification locked. Try again after $remainingLockout minutes.", 429);
        }

        // Track suspicious IPs and flag the account if the threshold is exceeded
        $suspiciousIPs = $cache->get($suspiciousIPKey)['value'] ?? [];
        if (!in_array($ipAddress, $suspiciousIPs)) {
            $suspiciousIPs[] = $ipAddress;
            $cache->set($suspiciousIPKey, $suspiciousIPs);

            if (count($suspiciousIPs) >= $maxSuspiciousAttempts) {
                throw new Exception("Account flagged for suspicious TOTP activity. Contact support.", 403);
            }
        }

        if (TOTP::verify($user['totp_secret'], $code, 2)) {
            // Clear failed attempts, lockouts, and suspicious IPs for TOTP
            $cache->delete($failedAttemptsKey);
            $cache->delete($lockoutKey);
            $cache->delete($suspiciousIPKey);

            // Clear partial auth sessions
            unset($_SESSION['partial_auth']);
            unset($_SESSION['totp_user_info']);

            // Update last login time using proper Database methods
            $updateStatement = $db->query('UPDATE users SET lastLogin = CURRENT_TIMESTAMP WHERE id = ?', [$userId]);

            // Complete authentication
            $token = generateToken();
            $expiresAt = time() + (24 * 60 * 60);

            $_SESSION['totp_verified'] = true;
            $_SESSION['token'] = $token;
            $_SESSION['token_expiration'] = $expiresAt;
            $_SESSION['userId'] = $user['id'];
            $_SESSION['user'] = $user;

            $stmt = $db->query("SELECT id, role FROM moderators WHERE userId = :userId", [":userId" => $_SESSION['userId']]);
            $response = $db->getOne($stmt);
            if ($response) {
                $_SESSION['moderator'] = true;
            } else {
                $_SESSION['moderator'] = false;
            }

            $db->commit();
            sendJsonResponse(true, 'Authentication successful', [
                'session' => $_SESSION
            ]);
        } else {
            // Increment the failed TOTP attempts counter
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
                $lockouts++;
                $cache->set($lockoutsKey, $lockouts);

                // Permanently lock the account if lockout attempts exceed the limit
                if ($lockouts > $maxLockouts) {
                    $emailBody = $templateLoader->render('accountBan.html', [
                        'name' => $user['name']
                    ]);

                    queueEmail(
                        $user['email'],
                        "Account Permanently Locked - TOTP Security Violation",
                        $emailBody
                    );
                    throw new Exception("Account permanently locked due to repeated TOTP failures. Check your mailbox for further instructions.", 423);
                }
                
                throw new Exception("TOTP verification locked due to repeated failures. Try again in 15 minutes.", 429);
            }
            
            $db->rollback();
            throw new Exception("Invalid verification code. Attempt: $failedAttempts", 401);
        }
    } catch (Exception $e) {
        // $db->rollBack();
        error_log($e->getMessage());
        sendJsonResponse(false, $e->getMessage(), [], (int)($e->getCode() > 100 && $e->getCode() < 600) ? $e->getCode() : 500);
    }
}
