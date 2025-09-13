<?php

namespace Backend\Middleware;

class Auth
{
   public function handle()
   {
      if (empty($_SESSION['user'])) {
         header('Location: /signin');
         exit();
      }

      $user = $_SESSION['user'];
      $hasTotpEnabled = $user['totp_enabled'] ?? false;
      $loginCount = $user['login_count'] ?? 0;

      // Redirect based on TOTP setup
      if ($loginCount === 0 || !$hasTotpEnabled) {
         // First time login or TOTP not enabled yet
         unset($_SESSION['user']);
         $_SESSION['partial_auth'] = [
            'userId' => $user['id'],
            'expires' => time() + 300, // 5 minutes
            'csrf_token' => bin2hex(random_bytes(32)),
            'setup_required' => true
         ];
         header('Location: /totp-setup');
         exit();
      }

      // User fully authenticated with TOTP enabled
   }
}
