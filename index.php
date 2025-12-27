<?php

use Backend\Utils\ValidationException;
use Backend\Routes\Router;

ini_set('session.gc_maxlifetime', 9000);
session_set_cookie_params(9000);
session_start();
const BASE_PATH = __DIR__ . "/";
require(BASE_PATH . "Backend/Utils/functions.php");
include('browser-check.php');

// Load environment variables (commented out as per your previous setup)
// loadEnv(base_path(".env"));

spl_autoload_register(function ($class) {
   $class =  str_replace('\\', '/', $class);
   require(base_path($class . ".php"));
});

require(base_path("Backend/Core/bootstrap.php"));
require(base_path("Backend/Utils/auth/generator.php"));

// ============================================
// SECURITY LOGGING FUNCTION
// ============================================

/**
 * Log security-related request data to hourly log files
 */
function logSecurityEvent() {
    // Create logs directory if it doesn't exist
    $logDir = BASE_PATH . 'logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Generate hourly log filename: security_YYYY-MM-DD_HH.log
    $logFile = $logDir . '/security_' . date('Y-m-d_H') . '.log';
    
    // Gather request data
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'none';
    $realIp = $_SERVER['HTTP_X_REAL_IP'] ?? 'none';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'none';
    $host = $_SERVER['HTTP_HOST'] ?? 'unknown';
    $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    $timestamp = date('Y-m-d H:i:s');
    
    // Detect suspicious patterns
    $suspiciousPatterns = [
        'sql injection' => preg_match('/(union|select|insert|update|delete|drop|create|alter|exec|script)/i', $requestUri),
        'path traversal' => preg_match('/(\.\.|\/etc\/|\/proc\/|\/var\/)/i', $requestUri),
        'xss attempt' => preg_match('/(<script|javascript:|onerror=|onload=)/i', $requestUri),
        'command injection' => preg_match('/(;|\||&&|`|\$\()/i', $requestUri),
        'suspicious user-agent' => preg_match('/(sqlmap|nikto|nmap|masscan|burp|metasploit)/i', $userAgent),
        'bot detected' => preg_match('/(bot|crawler|spider|scraper|curl|wget|python|scanner)/i', $userAgent),
    ];
    
    $threats = array_filter($suspiciousPatterns);
    $threatLevel = count($threats) > 0 ? 'SUSPICIOUS' : 'NORMAL';
    $threatDetails = count($threats) > 0 ? implode(', ', array_keys($threats)) : 'none';
    
    // Build log entry
    $logEntry = sprintf(
        "[%s] [%s] IP=%s | Forwarded=%s | Real=%s | Method=%s | URI=%s | Host=%s | Protocol=%s | Referer=%s | UserAgent=%s | Threats=%s | Session=%s\n",
        $timestamp,
        $threatLevel,
        $ip,
        $forwardedFor,
        $realIp,
        $requestMethod,
        $requestUri,
        $host,
        $protocol,
        $referer,
        substr($userAgent, 0, 200), // Limit UA length
        $threatDetails,
        session_id()
    );
    
    // Write to log file (append mode)
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // If threats detected, also log to separate threat file
    if ($threatLevel === 'SUSPICIOUS') {
        $threatFile = $logDir . '/threats_' . date('Y-m-d') . '.log';
        file_put_contents($threatFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Optional: Log to system error log
        error_log("SECURITY THREAT DETECTED: {$threatDetails} from IP {$ip} - URI: {$requestUri}");
    }
}

// Execute security logging
logSecurityEvent();

// ============================================
// SESSION EXPIRY CHECK
// ============================================

$currentTime = time();
$sessionLifetime = 150 * 60; // 150 minutes in seconds
// $sessionLifetime = 3 * 60; // 3 minutes for testing

// Check if user is authenticated
if (!empty($_SESSION['userId']) && !empty($_SESSION['token'])) {
   // Initialize session tracking for existing sessions
   if (!isset($_SESSION['session_started'])) {
      $_SESSION['session_started'] = $currentTime;
      $_SESSION['last_activity'] = $currentTime;
   }

   // Get current URI
   $uri = parse_url($_SERVER['REQUEST_URI'])['path'];

   // Routes that should skip expiry check
   $skipRoutes = [
      '/verify-totp',
      '/signout',
      '/session/check',
      '/session/renew',
      '/signin',
      '/signup'
   ];

   // Check if session has expired (only for non-skip routes)
   if (!in_array($uri, $skipRoutes)) {
      $timeElapsed = $currentTime - $_SESSION['session_started'];

      if ($timeElapsed > $sessionLifetime) {
         // Session expired - store info and redirect
         error_log("Session expired for user: " . ($_SESSION['user']['username'] ?? 'unknown') . " after {$timeElapsed} seconds");

         $expiredUsername = $_SESSION['user']['username'] ?? null;
         $expiredAt = $_SESSION['session_started'] + $sessionLifetime;

         // Destroy current session
         session_destroy();
         session_start();

         // Set expiry info for renewal page
         $_SESSION['session_expired'] = true;
         $_SESSION['session_expired_at'] = $expiredAt;
         $_SESSION['expired_username'] = $expiredUsername;

         // Redirect to TOTP verification with renewal flag
         header('Location: /verify-totp?action=renew&reason=expired&returnTo=' . urlencode($uri));
         exit();
      }

      // Update last activity
      $_SESSION['last_activity'] = $currentTime;
   }
}

// ============================================
// ROUTING
// ============================================

$router = new Router();
require base_path("Backend/Routes/routes.php");
$uri = parse_url($_SERVER['REQUEST_URI'])['path'];
$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

try {
   $router->route($uri, $method);
} catch (ValidationException $exception) {
   return redirect($router->previousURL());
}
