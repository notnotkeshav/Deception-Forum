<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $token = getBearerToken();

   if (isset($token)) {
      if (isset($_SESSION['token']) && $_SESSION['token'] === $token) {
         unset($_SESSION['token']);
         unset($_SESSION['token_expiration']);

         // 200 OK: Successfully logged out
         http_response_code(200);
         echo json_encode(['message' => 'Successfully logged out.']);
      } else {
         // 401 Unauthorized: Invalid token or session expired
         http_response_code(401);
         echo json_encode(['error' => 'Invalid token or session expired.']);
      }
   } else {
      // 400 Bad Request: Authorization token missing
      http_response_code(400);
      echo json_encode(['error' => 'Authorization token missing.']);
   }
   exit();
} else {
   // 405 Method Not Allowed: Invalid request method
   http_response_code(405);
   echo json_encode(['error' => 'Invalid request method.']);
   exit();
}
