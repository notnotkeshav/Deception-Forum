<?php

use Backend\Core\App;

const BASE_PATH = __DIR__ . "/";
require(BASE_PATH . "Backend/Utils/functions.php");
// loadEnv(base_path(".env"));

spl_autoload_register(function ($class) {
   $class =  str_replace('\\', '/', $class);
   require(base_path($class . ".php"));
});

require(base_path("Backend/Core/bootstrap.php"));

$mailer = App::container()->resolve('Core\Mailer');
echo __DIR__ . PHP_EOL;
$queueDir = __DIR__ . '/Backend/Core/email_queue';

$files = glob($queueDir . '/*.json');

if($files == false){
    echo "No email found to send JOB QUITTING...";
}

foreach ($files as $file) {
    echo "Processing file: $file" . PHP_EOL;

    $fileContents = file_get_contents($file);
    echo "File contents: " . $fileContents . PHP_EOL;

    $data = json_decode($fileContents, true);

    if (!$data || !isset($data['to'])) {
        echo "Invalid or corrupt data. Deleting file: $file" . PHP_EOL;
        unlink($file);
        continue;
    }

    echo "Decoded data: " . print_r($data, true) . PHP_EOL;

    try {
        $mailer->sendHTML($data['to'], $data['subject'], $data['body']);
        unlink($file); 
        echo "Email sent successfully to {$data['to']}" . PHP_EOL;
    } catch (Exception $e) {
        error_log("Email failed: " . $e->getMessage());
        echo "Email failed: " . $e->getMessage() . PHP_EOL;
        // Optionally move to a failed_jobs/ folder
    }
}
