#!/usr/bin/env php
<?php

/**
 * Notification Cleanup Script
 * Removes old notifications to keep the database clean
 * Run this script periodically via cron job
 */

const BASE_PATH = __DIR__ . "/";
require(BASE_PATH . "Backend/Utils/functions.php");
loadEnv(base_path("Backend/Core/.env"));

spl_autoload_register(function ($class) {
   $class =  str_replace('\\', '/', $class);
   require(base_path($class . ".php"));
});

require(base_path("Backend/Core/bootstrap.php"));

echo "Starting notification cleanup..." . PHP_EOL;

$daysToKeep = isset($argv[1]) ? (int)$argv[1] : 30;

if ($daysToKeep < 1) {
    echo "Error: Days to keep must be at least 1" . PHP_EOL;
    exit(1);
}

echo "Cleaning notifications older than {$daysToKeep} days..." . PHP_EOL;

$success = cleanOldNotifications($daysToKeep);

if ($success) {
    echo "Notification cleanup completed successfully." . PHP_EOL;
    exit(0);
} else {
    echo "Notification cleanup failed." . PHP_EOL;
    exit(1);
}
