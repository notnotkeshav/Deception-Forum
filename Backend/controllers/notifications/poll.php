<?php

use Backend\Core\App;

// Get current user
$user = authUser();
if (!$user) {
    sendJsonResponse(false, 'Unauthorized', [], 401);
}

$userId = $user['id'];

// Set headers for long polling
header('Content-Type: application/json');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Get last check timestamp from query params
$lastCheckTime = (int) ($_GET['last_check'] ?? 0);
$timeout = min((int) ($_GET['timeout'] ?? 30), 60); // Max 60 seconds timeout

// Validate timeout
if ($timeout < 5) {
    $timeout = 5; // Minimum 5 seconds
}

// Perform long polling
$result = longPollNotifications($userId, $lastCheckTime, $timeout);

// Send response
echo json_encode($result);
exit;
