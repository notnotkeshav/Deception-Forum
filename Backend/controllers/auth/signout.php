<?php

use Backend\Core\App;

$cache = App::container()->resolve('Core\Cache');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   // Check if user is authenticated via session
   if (isset($_SESSION['token']) && isset($_SESSION['userId'])) {

      $userId = $_SESSION['userId'];
      $loginUrl = $_SESSION['user']['loginUrl'] ?? null;

      // Clear cache entries
      if ($loginUrl) {
         $cache->delete("loginurl:$loginUrl");
         $cache->delete("user:loginurl:$loginUrl");
      }

      // Clear all session variables
      $_SESSION = array();

      // Delete the session cookie
      if (ini_get("session.use_cookies")) {
         $params = session_get_cookie_params();
         setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
         );
      }

      // Destroy the session
      session_destroy();

      // 200 OK: Successfully logged out
      http_response_code(200);
      echo json_encode(['success' => true, 'message' => 'Successfully logged out.']);
   } else {
      // 401 Unauthorized: No valid session
      http_response_code(401);
      echo json_encode(['success' => false, 'error' => 'Not authenticated or session expired.']);
   }
   exit();
} else {
   // 405 Method Not Allowed: Invalid request method
   http_response_code(405);
   echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
   exit();
}
