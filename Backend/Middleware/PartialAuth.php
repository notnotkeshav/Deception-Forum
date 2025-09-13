<?php

namespace Backend\Middleware;

class PartialAuth
{
    public function handle()
    {
        // Only allow users who are in partial authentication state
        if (empty($_SESSION['partial_auth'])) {
            header('Location: /signin');
            exit();
        }

        // Check if partial auth has expired
        if ($_SESSION['partial_auth']['expires'] < time()) {
            unset($_SESSION['partial_auth']);
            unset($_SESSION['totp_user_info']);
            header('Location: /signin');
            exit();
        }

        // If user somehow has full auth, redirect them away from TOTP verification
        if (isset($_SESSION['userId']) && isset($_SESSION['token']) && $_SESSION['token_expiration'] > time()) {
            header('Location: /');
            exit();
        }
    }
}
