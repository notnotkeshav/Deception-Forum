<?php

namespace Backend\Utils;

class Validator
{
   public static function string($value, $min = 1, $max = 1000)
   {
      $value = trim($value);
      return strlen($value) >= $min && strlen($value) <= $max;
   }

   public static function email($value)
   {
      return filter_var($value, FILTER_VALIDATE_EMAIL);
   }

   public static function password($value, $username, $name)
   {
      if (!is_string($value)) {
         return false;
      }

      $password = trim($value);
      $length = strlen($password);
      if ($length < 25 || $length > 255) {
         return false;
      }

      $uppercaseCount = 0;
      $lowercaseCount = 0;
      $digitCount = 0;
      $specialCharCount = 0;
      $specialChars = '!@#$%^&*()_-+=<>?{}[]|~:;",\'./\\';
      $previousChar = null;
      $consecutiveCount = 1;
      for ($i = 0; $i < $length; $i++) {
         $char = $password[$i];
         if ($char === $previousChar) {
            $consecutiveCount++;
            if ($consecutiveCount > 2) {
               return false;
            }
         } else {
            $consecutiveCount = 1;
         }
         if (ctype_upper($char)) {
            $uppercaseCount++;
         } elseif (ctype_lower($char)) {
            $lowercaseCount++;
         } elseif (ctype_digit($char)) {
            if (isset($password[$i - 1]) && ctype_digit($password[$i - 1]) && $char === $password[$i - 1]) {
               return false;
            }
            $digitCount++;
         } elseif (strpos($specialChars, $char) !== false) {
            $specialCharCount++;
         }

         $previousChar = $char;
      }

      if ($uppercaseCount < 2 || $lowercaseCount < 2 || $digitCount < 3 || $specialCharCount < 5) {
         return false;
      }

      for ($i = 0; $i < $length - 2; $i++) {
         $substring = substr($password, $i, 3);
         if (substr_count($password, $substring) > 1) {
            return false;
         }
      }

      $lowerPassword = strtolower($password);
      if (strpos($lowerPassword, strtolower($username)) !== false || strpos($lowerPassword, strtolower($name)) !== false) {
         return false;
      }

      $filename = 'commompasswords.txt';
      if (!file_exists($filename)) {
         echo "passwords not found using default list";
         $commonPatterns = ['password', '123456', 'qwerty', 'abc123', 'password1'];
      } else {
         $commonPatterns = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      }

      foreach ($commonPatterns as $pattern) {
         if (strpos($lowerPassword, strtolower($pattern)) !== false) {
            return false;
         }
      }
      return true;
   }
}
