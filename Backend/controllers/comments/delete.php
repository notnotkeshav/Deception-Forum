<?php
/**
 * Delete a comment (soft delete)
 * DELETE /comment/delete
 * Requires: auth
 */

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'DELETE' || $method === 'POST') {
    try {
        verifyCsrfToken($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');

        $commentId = $_POST['commentId'] ?? $_GET['commentId'] ?? '';
        $userId = $_SESSION['userId'];

        if (!$commentId) {
            sendJsonResponse(false, "Missing commentId", [], 400);
        }

        // Verify comment exists and user owns it (or is moderator)
        $stmt = $db->query(
            "SELECT userId FROM comments WHERE id = :id AND isDeleted = 0",
            [':id' => $commentId]
        );
        $comment = $db->getOne($stmt);

        if (!$comment) {
            sendJsonResponse(false, "Comment not found", [], 404);
        }

        // Check authorization (owner or moderator)
        $isModerator = $_SESSION['moderator'] ?? false;
        if ($comment['userId'] !== $userId && !$isModerator) {
            sendJsonResponse(false, "You don't have permission to delete this comment", [], 403);
        }

        // Soft delete
        $db->query(
            "UPDATE comments SET isDeleted = 1 WHERE id = :id",
            [':id' => $commentId]
        );

        sendJsonResponse(true, "Comment deleted successfully");

    } catch (Exception $e) {
        error_log("Delete comment error: " . $e->getMessage());
        sendJsonResponse(false, "Error deleting comment", [], 500);
    }
} else {
    abort(405);
}
