<?php

use Backend\Utils\ValidationException;
use Backend\Routes\Router;

session_start();
const BASE_PATH = __DIR__ . "/";
require(BASE_PATH . "Backend/Utils/functions.php");
include('browser-check.php');

loadEnv(base_path("Backend/Core/.env"));

spl_autoload_register(function ($class) {
   $class =  str_replace('\\', '/', $class);
   require(base_path($class . ".php"));
});

require(base_path("Backend/Core/bootstrap.php"));
require(base_path("Backend/Utils/auth/generator.php"));

// Session expiry check - runs BEFORE routing
$currentTime = time();
// $sessionLifetime = 150 * 60; // 150 minutes in seconds
$sessionLifetime = 3 * 60; // 3 minutes for testing

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

$router = new Router();
require base_path("Backend/Routes/routes.php");
$uri = parse_url($_SERVER['REQUEST_URI'])['path'];
$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

try {
   $router->route($uri, $method);
} catch (ValidationException $exception) {
   return redirect($router->previousURL());
}
