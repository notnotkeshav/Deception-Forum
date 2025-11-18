<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
    sendJsonResponse(false, "Unauthorized", [], 401);
}

try {
    $stmt = $db->query(
        "SELECT COUNT(*) as count FROM notifications WHERE userId = :userId AND read_at IS NULL",
        [':userId' => $userId]
    );
    $result = $db->getOne($stmt);
    
    sendJsonResponse(true, "Count retrieved", ['count' => $result['count']], 200);
} catch (Exception $e) {
    sendJsonResponse(false, "Error", ["error" => $e->getMessage()], 500);
}
