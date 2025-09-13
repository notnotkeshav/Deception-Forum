<?php

namespace Backend\Middleware;

class AuthOrPartial
{
    public function handle()
    {
        $hasFullAuth = !empty($_SESSION['user']);
        $hasPartialAuth = isset($_SESSION['partial_auth']) && $_SESSION['partial_auth']['expires'] > time();

        if (!$hasFullAuth && !$hasPartialAuth) {
            header('Location: /signin');
            exit();
        }

        if ($hasPartialAuth) {
            // Allow access to TOTP setup and verification pages
            return;
        }

        if ($hasFullAuth) {
            $user = $_SESSION['user'];
            $hasTotpEnabled = $user['totp_enabled'] ?? false;
            $loginCount = $user['login_count'] ?? 0;

            if ($loginCount === 0 || !$hasTotpEnabled) {
                unset($_SESSION['user']);
                $_SESSION['partial_auth'] = [
                    'userId' => $user['id'],
                    'expires' => time() + 300,
                    'csrf_token' => bin2hex(random_bytes(32)),
                    'setup_required' => true
                ];
                header('Location: /totp-setup');
                exit();
            }
        }

        // Otherwise, allow access
        return;
    }
}
