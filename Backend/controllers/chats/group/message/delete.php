<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $messageId = $_DELETE['messageId'] ?? null;

    if (!$messageId) {
        sendJsonResponse(false, "Message ID is required", [], 400);
    }

    $userId = $_SESSION['userId'] ?? null;
    $isAdmin = $_SESSION['isAdmin'] ?? false; // You can modify this based on your auth system

    if (!$userId) {
        sendJsonResponse(false, "Unauthorized", [], 401);
    }

    // Try to delete as owner first
    $query = "UPDATE groupMessages 
              SET message = '[Deleted]', isDeleted = 1 
              WHERE id = :messageId AND (userId = :userId";

    // Allow admin deletion
    if ($isAdmin) {
        $query .= " OR 1=1"; // Admin override
    }

    $query .= ")";

    $updated = $db->query($query, [
        ':messageId' => $messageId,
        ':userId' => $userId
    ]);

    if ($updated) {
        sendJsonResponse(true, "Message deleted successfully", [], 200);
    } else {
        sendJsonResponse(false, "Failed to delete message", [], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
