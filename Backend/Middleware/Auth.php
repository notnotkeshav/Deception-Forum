<?php

namespace Backend\Middleware;

class Auth
{
   public function handle()
   {
      if (!$_SESSION['token'] ?? false) {
         header('location: /signin');
         exit();
      }
   }
}
