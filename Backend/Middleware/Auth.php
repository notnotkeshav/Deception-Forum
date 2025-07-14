<?php

namespace Backend\Middleware;

class Auth
{
   public function handle()
   {
      if (!empty($_SESSION['partial_auth'])) {
         header('location: /verify-totp');
         exit();
      }

      // Standard auth check
      if (empty($_SESSION['user'])) {
         header('location: /signin');
         exit();
      }
      
      if (!$_SESSION['token'] ?? false) {
         header('location: /signin');
         exit();
      }

      // Check if TOTP is required but not verified
      if ($_SESSION['user']['totp_enabled'] && empty($_SESSION['totp_verified'])) {
         // Store partial auth and redirect to TOTP verification
         $_SESSION['partial_auth'] = [
               'userId' => $_SESSION['user']['id'],
               'expires' => time() + 300 // 5 minutes
         ];
         unset($_SESSION['user']);
         header('location: /verify-totp');
         exit();
      }
   }
}
