<?php
/**
 * Block or unblock a user
 * POST /user/block
 * Requires: auth
 */

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'POST') {
    try {
        verifyCsrfToken($_POST['csrf_token'] ?? '');

        $blockedUserId = $_POST['blockedUserId'] ?? '';
        $action = $_POST['action'] ?? 'block';  // 'block' or 'unblock'
        $reason = $_POST['reason'] ?? NULL;
        $userId = $_SESSION['userId'];

        if (!$blockedUserId) {
            sendJsonResponse(false, "Missing blockedUserId", [], 400);
        }

        if ($blockedUserId === $userId) {
            sendJsonResponse(false, "You cannot block yourself", [], 400);
        }

        // Verify blocked user exists
        $stmt = $db->query("SELECT id FROM users WHERE id = :id AND isDeleted = 0", [':id' => $blockedUserId]);
        if (!$db->getOne($stmt)) {
            sendJsonResponse(false, "User not found", [], 404);
        }

        if ($action === 'block') {
            // Check if already blocked
            $stmt = $db->query(
                "SELECT id FROM blocked_users WHERE userId = :uid AND blockedUserId = :bid",
                [':uid' => $userId, ':bid' => $blockedUserId]
            );

            if ($db->getOne($stmt)) {
                sendJsonResponse(false, "User is already blocked", [], 400);
            }

            // Block user
            $db->query(
                "INSERT INTO blocked_users (userId, blockedUserId, reason) VALUES (:uid, :bid, :reason)",
                [':uid' => $userId, ':bid' => $blockedUserId, ':reason' => $reason]
            );

            // Delete existing DMs with blocked user
            $db->query(
                "UPDATE privateChatMessages SET isDeleted = 1
                 WHERE chatId IN (
                    SELECT id FROM privateChats
                    WHERE (user1Id = :uid AND user2Id = :bid) OR (user1Id = :bid AND user2Id = :uid)
                 )",
                [':uid' => $userId, ':bid' => $blockedUserId]
            );

            sendJsonResponse(true, "User blocked successfully");

        } elseif ($action === 'unblock') {
            // Check if blocked
            $stmt = $db->query(
                "SELECT id FROM blocked_users WHERE userId = :uid AND blockedUserId = :bid",
                [':uid' => $userId, ':bid' => $blockedUserId]
            );

            if (!$db->getOne($stmt)) {
                sendJsonResponse(false, "User is not blocked", [], 400);
            }

            // Unblock user
            $db->query(
                "DELETE FROM blocked_users WHERE userId = :uid AND blockedUserId = :bid",
                [':uid' => $userId, ':bid' => $blockedUserId]
            );

            sendJsonResponse(true, "User unblocked successfully");

        } else {
            sendJsonResponse(false, "Invalid action. Use 'block' or 'unblock'", [], 400);
        }

    } catch (Exception $e) {
        error_log("Block user error: " . $e->getMessage());
        sendJsonResponse(false, "Error processing block request", [], 500);
    }
} else {
    abort(405);
}
