<?php

namespace Backend\Middleware;

class Admin
{
   public function handle()
   {
      if (!isset($_SESSION['token'])) {
         http_response_code(401);
         header('Location: /signin');
         exit();
      }

      if (!$_SESSION['moderator']) {
         sendJsonResponse(false, "You do not have moderators permissions to access this resource", [], 403);
      }
   }
}
