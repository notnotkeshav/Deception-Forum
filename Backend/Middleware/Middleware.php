<?php

namespace Backend\Middleware;

class Middleware
{
   public const MAP = [
      'guest' => Guest::class,
      'auth' => Auth::class,
      'admin' => Admin::class
   ];

   public static function resolve($key)
   {
      if (!$key) {
         return;
      }
      $middleware = Middleware::MAP[$key] ?? false;

      if (!$middleware) {
         throw new \Exception("No matching middleware founr for key {$key}.");
      }
      (new $middleware)->handle();
   }
}
