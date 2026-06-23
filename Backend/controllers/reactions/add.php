<?php
/**
 * Add reaction to a message
 * POST /reactions/add
 * Requires: auth
 */

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'POST') {
    try {
        verifyCsrfToken($_POST['csrf_token'] ?? '');

        $targetType = $_POST['targetType'] ?? '';  // 'thread' or 'comment'
        $targetId = $_POST['targetId'] ?? '';
        $emoji = $_POST['emoji'] ?? '';
        $userId = $_SESSION['userId'];

        // Validate
        if (!in_array($targetType, ['thread', 'comment'])) {
            sendJsonResponse(false, "Invalid target type", [], 400);
        }

        if (!$targetId || !$emoji) {
            sendJsonResponse(false, "Missing targetId or emoji", [], 400);
        }

        // Validate emoji is one of the allowed ones
        $allowedEmojis = ['👍', '😂', '❤️', '🔥', '😍', '😢', '😡', '👏'];
        if (!in_array($emoji, $allowedEmojis)) {
            sendJsonResponse(false, "Emoji not allowed", [], 400);
        }

        // Verify target exists
        if ($targetType === 'thread') {
            $stmt = $db->query("SELECT id FROM threads WHERE id = :id AND isDeleted = 0", [':id' => $targetId]);
            if (!$db->getOne($stmt)) {
                sendJsonResponse(false, "Thread not found", [], 404);
            }
        } else {
            $stmt = $db->query("SELECT id FROM comments WHERE id = :id AND isDeleted = 0", [':id' => $targetId]);
            if (!$db->getOne($stmt)) {
                sendJsonResponse(false, "Comment not found", [], 404);
            }
        }

        // Check if user already reacted with this emoji
        $stmt = $db->query(
            "SELECT id FROM reactions WHERE targetType = :type AND targetId = :tid AND userId = :uid AND emoji = :emoji",
            [':type' => $targetType, ':tid' => $targetId, ':uid' => $userId, ':emoji' => $emoji]
        );

        if ($db->getOne($stmt)) {
            // Already reacted, remove it (toggle off)
            $db->query(
                "DELETE FROM reactions WHERE targetType = :type AND targetId = :tid AND userId = :uid AND emoji = :emoji",
                [':type' => $targetType, ':tid' => $targetId, ':uid' => $userId, ':emoji' => $emoji]
            );
            $reactionAdded = false;
        } else {
            // Add new reaction
            $db->query(
                "INSERT INTO reactions (targetType, targetId, userId, emoji) VALUES (:type, :tid, :uid, :emoji)",
                [':type' => $targetType, ':tid' => $targetId, ':uid' => $userId, ':emoji' => $emoji]
            );
            $reactionAdded = true;
        }

        // Get updated reaction counts
        $stmt = $db->query(
            "SELECT emoji, COUNT(*) as count FROM reactions WHERE targetType = :type AND targetId = :tid GROUP BY emoji",
            [':type' => $targetType, ':tid' => $targetId]
        );
        $reactionCounts = $db->getAll($stmt);

        sendJsonResponse(true, "Reaction updated", [
            'reactionAdded' => $reactionAdded,
            'reactions' => $reactionCounts
        ]);

    } catch (Exception $e) {
        error_log("Reaction error: " . $e->getMessage());
        sendJsonResponse(false, "Error adding reaction", [], 500);
    }
} else {
    abort(405);
}
