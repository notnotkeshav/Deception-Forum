<?php

namespace Backend\Middleware;

class PartialAuth
{
    public function handle()
    {
        // Check if user has partial authentication (after password but before TOTP)
        if (empty($_SESSION['partial_auth'])) {
            header('location: /signin');
            exit();
        }

        // Check if partial auth has expired
        if ($_SESSION['partial_auth']['expires'] < time()) {
            unset($_SESSION['partial_auth']);
            header('location: /signin');
            exit();
        }
    }
}