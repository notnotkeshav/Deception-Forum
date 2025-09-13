<?php

namespace Backend\Middleware;

use Backend\Core\App;

class Middleware
{
    public static $MAP = [
        'guest' => GuestMiddleware::class,
        'auth' => AuthMiddleware::class,
        'partial_auth' => PartialAuthMiddleware::class,
        'username_rate_limit' => UsernameGenerationMiddleware::class,
    ];

    public static function resolve($key)
    {
        if (!$key) {
            return;
        }

        $middleware = self::$MAP[$key] ?? false;
        if (!$middleware) {
            throw new \Exception("No matching middleware found for key {$key}.");
        }

        (new $middleware)->handle();
    }
}

// Username Generation Middleware
class UsernameGenerationMiddleware
{
    public function handle()
    {
        $cache = App::container()->resolve('Core\Cache');
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        
        // Check rate limiting
        $ipRateLimitKey = "username_gen_ip:" . hash('sha256', $ipAddress);
        $requestLog = $cache->get($ipRateLimitKey)['value'] ?? [];
        $now = time();
        $windowSeconds = 3600; // 1 hour
        $maxRequests = 7;
        
        // Clean old requests
        $requestLog = array_filter($requestLog, fn($timestamp) => $now - $timestamp < $windowSeconds);
        
        // Check if rate limited
        if (count($requestLog) >= $maxRequests) {
            http_response_code(429);
            header('Content-Type: application/json');
            
            $cachedUsernames = $cache->get("username_pool_ip:" . hash('sha256', $ipAddress))['value'] ?? [];
            
            if (empty($cachedUsernames)) {
                // Generate final pool
                $finalUsernames = [];
                for ($i = 0; $i < 7; $i++) {
                    $finalUsernames[] = generateUsername(random_int(15, 25));
                }
                $cache->set("username_pool_ip:" . hash('sha256', $ipAddress), $finalUsernames, 3600);
                $cachedUsernames = $finalUsernames;
            }
            
            echo json_encode([
                'success' => false,
                'message' => 'Rate limit exceeded. Choose from provided usernames.',
                'details' => [
                    'usernames' => $cachedUsernames,
                    'rate_limited' => true
                ]
            ]);
            exit();
        }
    }
}

// Base Middleware Interface
interface MiddlewareInterface
{
    public function handle();
}

// Guest Middleware - Only allows unauthenticated users (for signup, signin pages)
class GuestMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is already fully authenticated
        if ($this->isFullyAuthenticated()) {
            // Redirect to main page (threads based on your signin.php)
            header('Location: /threads');
            exit();
        }

        // Check if user has partial auth and should be redirected to TOTP setup/verification
        if ($this->hasPartialAuth()) {
            $setupRequired = $_SESSION['partial_auth']['setup_required'] ?? false;
            $redirectUrl = $setupRequired ? '/totp-setup' : '/verify-totp';
            header("Location: {$redirectUrl}");
            exit();
        }
    }

    private function isFullyAuthenticated()
    {
        // Based on signin.php: user is fully authenticated if they have user session and token
        return !empty($_SESSION['user']) && 
               !empty($_SESSION['token']) && 
               !empty($_SESSION['token_expiration']) && 
               $_SESSION['token_expiration'] > time();
    }

    private function hasPartialAuth()
    {
        // Based on signin.php and totp_setup.php: partial auth exists and not expired
        return !empty($_SESSION['partial_auth']) && 
               $_SESSION['partial_auth']['expires'] > time();
    }
}

// Partial Auth Middleware - For users who logged in but need TOTP verification
class PartialAuthMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is already fully authenticated
        if ($this->isFullyAuthenticated()) {
            // Redirect to main application
            header('Location: /threads');
            exit();
        }

        // Check if user has valid partial authentication
        if (!$this->hasValidPartialAuth()) {
            // No valid partial auth, redirect to signin
            header('Location: /signin');
            exit();
        }
    }

    private function isFullyAuthenticated()
    {
        // User has complete authentication with valid token
        return !empty($_SESSION['user']) && 
               !empty($_SESSION['token']) && 
               !empty($_SESSION['token_expiration']) && 
               $_SESSION['token_expiration'] > time() &&
               !empty($_SESSION['userId']);
    }

    private function hasValidPartialAuth()
    {
        // Based on your signin.php and verify_totp.php implementation
        if (empty($_SESSION['partial_auth'])) {
            return false;
        }

        // Check if partial auth has expired
        if ($_SESSION['partial_auth']['expires'] <= time()) {
            // Clean up expired partial auth
            unset($_SESSION['partial_auth']);
            unset($_SESSION['totp_user_info']);
            return false;
        }

        // Must have userId in partial auth
        return !empty($_SESSION['partial_auth']['userId']);
    }
}

// Full Auth Middleware - Only allows fully authenticated users
class AuthMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is fully authenticated
        if (!$this->isFullyAuthenticated()) {
            $this->handleUnauthenticated();
        }

        // Additional security: check if user account is deleted/banned
        if ($this->isAccountInvalid()) {
            $this->clearSessionAndRedirect();
        }
    }

    private function isFullyAuthenticated()
    {
        // Based on your authentication flow:
        // 1. Must have user session data
        // 2. Must have valid token
        // 3. Token must not be expired
        // 4. Must have userId
        return !empty($_SESSION['user']) && 
               !empty($_SESSION['token']) && 
               !empty($_SESSION['token_expiration']) && 
               $_SESSION['token_expiration'] > time() &&
               !empty($_SESSION['userId']);
    }

    private function isAccountInvalid()
    {
        // Check if user account is deleted/banned based on your user data structure
        if (!empty($_SESSION['user'])) {
            return isset($_SESSION['user']['isDeleted']) && $_SESSION['user']['isDeleted'] != 0;
        }
        return false;
    }

    private function handleUnauthenticated()
    {
        // Check if user has partial authentication (needs TOTP)
        if (!empty($_SESSION['partial_auth']) && $_SESSION['partial_auth']['expires'] > time()) {
            $setupRequired = $_SESSION['partial_auth']['setup_required'] ?? false;
            $redirectUrl = $setupRequired ? '/totp-setup' : '/verify-totp';
            header("Location: {$redirectUrl}");
            exit();
        }

        // No authentication at all, redirect to signin
        $this->clearSessionAndRedirect();
    }

    private function clearSessionAndRedirect()
    {
        // Clear all authentication-related session data
        unset($_SESSION['user']);
        unset($_SESSION['userId']);
        unset($_SESSION['token']);
        unset($_SESSION['token_expiration']);
        unset($_SESSION['partial_auth']);
        unset($_SESSION['totp_user_info']);
        unset($_SESSION['moderator']);
        unset($_SESSION['totp_verified']);

        header('Location: /signin');
        exit();
    }
}

// Helper class for session management (based on your actual session structure)
class SessionHelper
{
    public static function setPartialAuth($userId, $setupRequired = false)
    {
        $_SESSION['partial_auth'] = [
            'userId' => $userId,
            'expires' => time() + 300, // 5 minutes as per your signin.php
            'csrf_token' => bin2hex(random_bytes(32)),
            'setup_required' => $setupRequired
        ];
    }

    public static function setFullAuth($user, $token, $isModerator = false)
    {
        // Clear partial auth
        unset($_SESSION['partial_auth']);
        unset($_SESSION['totp_user_info']);

        // Set full auth based on your verify_totp.php
        $_SESSION['user'] = $user;
        $_SESSION['userId'] = $user['id'];
        $_SESSION['token'] = $token;
        $_SESSION['token_expiration'] = time() + (24 * 60 * 60); // 24 hours
        $_SESSION['moderator'] = $isModerator;
        $_SESSION['totp_verified'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    public static function clearAuth()
    {
        $keysToUnset = [
            'user', 'userId', 'token', 'token_expiration',
            'partial_auth', 'totp_user_info', 'moderator',
            'totp_verified', 'csrf_token', 'totp_setup'
        ];

        foreach ($keysToUnset as $key) {
            unset($_SESSION[$key]);
        }
    }

    public static function isFullyAuthenticated()
    {
        return !empty($_SESSION['user']) && 
               !empty($_SESSION['token']) && 
               !empty($_SESSION['token_expiration']) && 
               $_SESSION['token_expiration'] > time() &&
               !empty($_SESSION['userId']);
    }

    public static function hasPartialAuth()
    {
        return !empty($_SESSION['partial_auth']) && 
               $_SESSION['partial_auth']['expires'] > time() &&
               !empty($_SESSION['partial_auth']['userId']);
    }
}

?>