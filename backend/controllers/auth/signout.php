<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $token = getBearerToken();

   if (isset($token)) {
      if (isset($_SESSION['token']) && $_SESSION['token'] === $token) {
         unset($_SESSION['token']);
         unset($_SESSION['token_expiration']);

         echo json_encode(['message' => 'Successfully logged out.', 'server' => $_SERVER]);
      } else {
         echo json_encode(['error' => 'Invalid token or session expired.', 'server' => $_SERVER]);
      }
   } else {
      echo json_encode(['error' => 'Authorization token missing.', 'server' => $_SERVER]);
   }
   exit();
} else {
   echo json_encode(['error' => 'Invalid request method.', 'server' => $_SERVER]);
   exit();
}
