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
   
   public static function password($value, $username = '', $name = '')
   {
      if (!is_string($value)) {
         return [false, "Invalid type"]; // Return with a failure message
      }

      $password = trim($value);
      $length = strlen($password);
      $checks = 0; // Count of checks performed
      $failedChecks = []; // Array to hold failed checks

      // Check password length
      $checks++;
      if ($length < 25 || $length > 255) {
         $failedChecks[] = "Length must be between 25 and 255 characters.";
      }

      $uppercaseCount = 0;
      $lowercaseCount = 0;
      $digitCount = 0;
      $specialCharCount = 0;
      $specialChars = '!@#$%^&*()_-+=<>?{}[]|~:;",\'./\\';
      $previousChar = null;
      $consecutiveCount = 0;

      for ($i = 0; $i < $length; $i++) {
         $char = $password[$i];
         if ($char === $previousChar) {
            $consecutiveCount++;
            if ($consecutiveCount > 2) {
               $failedChecks[] = "No more than two consecutive identical characters allowed.";
               break;
            }
         } else {
            $consecutiveCount = 0;
         }
         if (ctype_upper($char)) {
            $uppercaseCount++;
         } elseif (ctype_lower($char)) {
            $lowercaseCount++;
         } elseif (ctype_digit($char)) {
            if (isset($password[$i - 1]) && ctype_digit($password[$i - 1]) && $char === $password[$i - 1]) {
               if (isset($password[$i - 2]) && $password[$i - 2] === $char) {
                  $failedChecks[] = "No more than two consecutive identical digits allowed.";
                  break;
               }
            }
            $digitCount++;
         } elseif (strpos($specialChars, $char) !== false) {
            $specialCharCount++;
         }
         $previousChar = $char;
      }

      // Check counts of character types
      $checks += 4; // 4 character type checks
      if ($uppercaseCount < 2) {
         $failedChecks[] = "At least 2 uppercase letters required.";
      }
      if ($lowercaseCount < 2) {
         $failedChecks[] = "At least 2 lowercase letters required.";
      }
      if ($digitCount < 3) {
         $failedChecks[] = "At least 3 digits required.";
      }
      if ($specialCharCount < 5) {
         $failedChecks[] = "At least 5 special characters required.";
      }

      // Check for repeating substrings
      for ($i = 0; $i < $length - 2; $i++) {
         $substring = substr($password, $i, 3);
         if (substr_count($password, $substring) > 1) {
            $failedChecks[] = "No repeating substrings of length 3 or more allowed.";
            break;
         }
      }
      $checks++;

      // Check against username and name only if they are provided
      $lowerPassword = strtolower($password);
      if ($username && strpos($lowerPassword, strtolower($username)) !== false) {
         $failedChecks[] = "Password must not contain the username.";
      }
      if ($name && strpos($lowerPassword, strtolower($name)) !== false) {
         $failedChecks[] = "Password must not contain the name.";
      }
      $checks += 2; // 2 checks for username and name (if provided)

      // Check against common passwords
      $filename = './backend/utils/auth/commonpasswords.txt';
      if (!file_exists($filename)) {
         // echo "Common passwords file not found, using default list.";
         $commonPatterns = ['password', '123456', 'qwerty', 'abc123', 'password1'];
      } else {
         $commonPatterns = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      }

      foreach ($commonPatterns as $pattern) {
         if (strpos($lowerPassword, strtolower($pattern)) !== false) {
            $failedChecks[] = "Password must not contain common patterns.";
            break;
         }
      }
      $checks++;

      // Return results
      if (!empty($failedChecks)) {
         return [false, $failedChecks, $checks]; // Return false and failed checks
      }

      return [true, "All checks passed.", $checks]; // Return true if all checks pass
   }
}
