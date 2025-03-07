<?php

# Array to string conversion Error => if we try to echo ["Array"], -> Do this instead:
function dumpAndDie($value)
{
   echo "<pre>";
   var_dump($value); # SuperGlobals => var of format _GET, _SERVER
   echo "</pre>";

   die(); # it will terminates the code below this line

}

function getURL()
{
   return $_SERVER['REQUEST_URI'];
}

function abort($code = 404, $data)
{
   http_response_code($code);
   require view("errors/{$code}.php", $data);
   die();
}

function base_path($path)
{
   return BASE_PATH . ltrim($path, "/");
}

function view($path, $args = [])
{
   extract($args);
   require base_path('frontend/views/' . $path);
}

function redirect($url)
{
   header("location: {$url}");
   exit();
}

function getQueryParams()
{
   try {
      $url = getURL();
      $url_components = parse_url($url);
      if (isset($url_components['query'])) {
         parse_str($url_components['query'], $params);
         return $params;
      } else {
         return [];
      }
   } catch (Exception $e) {
      return $e;
   }
}

function getRequestBody()
{
   $rawData = file_get_contents('php://input');
   $data = json_decode($rawData, true);
   if (json_last_error() !== JSON_ERROR_NONE) {
      return [];
   }

   return $data;
}

function sendJsonResponse($success, $message, $details = [], $httpCode = 200) {
   http_response_code($httpCode);
   echo json_encode([
       "success" => $success,
       "message" => $message,
       "details" => $details
   ]);
   exit();
}

function getBearerToken()
{
   $headers = getallheaders();
   if (isset($headers['Authorization'])) {
      // Split the string to get the token
      list($type, $token) = explode(' ', $headers['Authorization']);
      if (strcasecmp($type, 'Bearer') === 0) {
         return $token;
      }
   }
   return null;
}

function generateRandomPassword($length = 25)
{
   if ($length < 25 || $length > 255) {
      throw new InvalidArgumentException("Length must be between 25 and 255 characters.");
   }

   $uppercaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
   $lowercaseChars = 'abcdefghijklmnopqrstuvwxyz';
   $digitChars = '0123456789';
   $specialChars = '!@#$%^&*()_-+=<>?{}[]|~:;",\'./\\';

   // Ensure we have at least 2 uppercase, 2 lowercase, 3 digits, and 5 special characters
   $password = '';
   $password .= $uppercaseChars[random_int(0, strlen($uppercaseChars) - 1)];
   $password .= $uppercaseChars[random_int(0, strlen($uppercaseChars) - 1)];
   $password .= $lowercaseChars[random_int(0, strlen($lowercaseChars) - 1)];
   $password .= $lowercaseChars[random_int(0, strlen($lowercaseChars) - 1)];
   $password .= $digitChars[random_int(0, strlen($digitChars) - 1)];
   $password .= $digitChars[random_int(0, strlen($digitChars) - 1)];
   $password .= $digitChars[random_int(0, strlen($digitChars) - 1)];

   // Add special characters
   for ($i = 0; $i < 5; $i++) {
      $password .= $specialChars[random_int(0, strlen($specialChars) - 1)];
   }

   // Fill the rest of the password with random characters from all categories
   $allChars = $uppercaseChars . $lowercaseChars . $digitChars . $specialChars;
   $remainingLength = $length - strlen($password);

   for ($i = 0; $i < $remainingLength; $i++) {
      $password .= $allChars[random_int(0, strlen($allChars) - 1)];
   }

   // Shuffle the password to ensure randomness
   return str_shuffle($password);
}


function loadEnv($file)
{
   if (!file_exists($file)) {
      throw new Exception('.env file not found');
   }

   $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   foreach ($lines as $line) {
      if (strpos(trim($line), '#') === 0) {
         continue;
      }
      list($key, $value) = explode('=', $line, 2);
      $key = trim($key);
      $value = trim($value);
      putenv("$key=$value");
   }
}
