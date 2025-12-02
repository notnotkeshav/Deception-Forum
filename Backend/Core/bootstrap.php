<?php

use Backend\Core\App;
use Backend\Core\Container;
use Backend\Core\Database;
use Backend\Core\Cache;
use Backend\Core\Mailer;
use Backend\Core\TemplateLoader;

$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $value = trim($value, '"\''); // Remove quotes
        putenv(trim($key) . '=' . $value);
    }
}

$container = new Container();

$container->bind('Core\Cache', function () {
   return new Cache();
});

$container->bind('Core\Database', function () use ($container) {
   $config = require base_path("Backend/Core/config.php");
   return new Database($config['database'], getenv("DB_USERNAME"), getenv("DB_PASSWORD"), $container->resolve('Core\Cache'));
});

$container->bind('Core\Mailer', function () {
   $config = require base_path("Backend/Core/config.php");
   return new Mailer($config['mailer']);
});

$container->bind('Core\TemplateLoader', function () {
   $templateDir = base_path("Backend/Core/mail-templates");
   return new TemplateLoader($templateDir);
});

App::setContainer($container);
