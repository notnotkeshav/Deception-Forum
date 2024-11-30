<?php

namespace Backend\Middleware;

class Admin
{
   public function handle()
   {
      if (!isset($_SESSION['user']) || !isset($_SESSION['user']['accessLevel'])) {
         http_response_code(401);
         header('Location: /signin');
         exit();
      }

      $accessLevel = $_SESSION['user']['accessLevel'];
      if ($accessLevel < 10) {
         http_response_code(403);
         echo json_encode([
            'success' => false,
            'error' => 'You do not have the required permissions to access this resource.'
         ]);
         exit();
      }
   }
}
