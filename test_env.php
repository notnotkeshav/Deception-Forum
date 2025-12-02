<?php
// Load .env file
$envFile = __DIR__ . '/.env';
echo "Looking for .env at: $envFile\n";

if (file_exists($envFile)) {
    echo ".env file EXISTS\n";
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $value = trim($value, '"');
        putenv(trim($key) . '=' . $value);
        echo "Set: " . trim($key) . " = " . $value . "\n";
    }
} else {
    echo ".env file NOT FOUND\n";
}

echo "\nTesting getenv():\n";
echo "DB_USERNAME: " . getenv('DB_USERNAME') . "\n";
echo "DB_PASSWORD: " . getenv('DB_PASSWORD') . "\n";
echo "DB_NAME: " . getenv('DB_NAME') . "\n";
