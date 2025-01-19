<?php

use Backend\Utils\ValidationException;
use Backend\Routes\Router;

session_start();
const BASE_PATH = __DIR__ . "/";
require(BASE_PATH . "backend/utils/functions.php");

loadEnv(base_path("backend/core/.env"));

spl_autoload_register(function ($class) {
   $class =  str_replace('\\', '/', $class);
   require(base_path($class . ".php"));
});

require(base_path("backend/core/bootstrap.php"));
require(base_path("backend/utils/auth/generator.php"));

$router = new Router();
require base_path("backend/routes/routes.php");
$uri = parse_url($_SERVER['REQUEST_URI'])['path'];
$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
try {
   $router->route($uri, $method);
} catch (ValidationException $exception) {
   return redirect($router->previousURL());
}
