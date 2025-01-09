<?php

use Backend\Core\App;
use Backend\Core\Container;
use Backend\Core\Database;
use Backend\Core\Cache;
use Backend\Core\Mailer;
use Backend\Core\TemplateLoader;

$container = new Container();

$container->bind('Core\Cache', function () {
   return new Cache();
});

$container->bind('Core\Database', function () use ($container) {
   $config = require base_path("backend/core/config.php");
   return new Database($config['database'], getenv("DB_USERNAME"), getenv("DB_PASSWORD"), $container->resolve('Core\Cache'));
});

$container->bind('Core\Mailer', function () {
   $config = require base_path("backend/core/config.php");
   return new Mailer($config['mailer']);
});

$container->bind('Core\TemplateLoader', function () {
   $templateDir = base_path("backend/core/mail-templates");
   return new TemplateLoader($templateDir);
});

App::setContainer($container);
