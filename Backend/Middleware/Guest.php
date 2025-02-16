<?php

namespace Backend\Middleware;

class Guest
{
   public function handle()
   {
      if (isset($_SESSION['token']) && $_SESSION['token_expiration'] > time()) {
         header('location: /');
         exit();
      }
   }
}
