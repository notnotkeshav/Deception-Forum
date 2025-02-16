<?php

namespace Backend\Core;

class Container
{

   public $bindings = [];
   # add or bind
   public function bind($key, $resolver)
   {
      $this->bindings[$key] = $resolver;
   }

   # remove or resolve
   public function resolve($key)
   {
      if (!array_key_exists($key, $this->bindings)) {
         throw new \Exception("No matched bindings found for {$key}");
      }
      $resolver = $this->bindings[$key];
      return call_user_func($resolver);
   }
}
