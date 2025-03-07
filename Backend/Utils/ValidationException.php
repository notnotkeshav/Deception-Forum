<?php

namespace Backend\Utils;

class ValidationException extends \Exception
{
   // public readonly array $errors;
   protected $errors = [];
   public readonly array $old;
   public static function throw($errors, $old)
   {
      $instance = new static;
      $instance->errors = $errors;
      $instance->old = $old;
      throw $instance;
   }

   public function errors(){
      return $this->errors;
   }
}
