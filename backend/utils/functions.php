<?php

use Backend\Utils\Response;
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

function abort($code = 404)
{
   http_response_code($code);
   require view("errors/error{$code}.php");

   die();
}

function base_path($path)
{
   return BASE_PATH   . ltrim($path, "/");
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

function getQueryParams(){
   $url= getURL();
   $url_components = parse_url($url);
   parse_str($url_components['query'], $params);
   return $params;
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


