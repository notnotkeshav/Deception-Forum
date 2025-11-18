<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$body = getRequestBody();

if ($method === 'PUT') {
    if (empty($body['threadId'])) {
        // Thread ID is required
        sendJsonResponse(false, "Thread ID is required.", [], 400);
    }

    try {
        $db->beginTransaction();

        // Check if the thread exists and get the current lock status
        $stmt = $db->query(
            "SELECT locked FROM threads WHERE id = :id",
            [":id" => $body['threadId']]
        );
        $thread = $db->getOne($stmt);

        if (!$thread) {
            // Thread does not exist
            sendJsonResponse(false, "Thread not found.", [], 404);
        }

        // Check if the user has moderator privileges
        if (empty($_SESSION['moderator'])) {
            // Insufficient permissions
            sendJsonResponse(false, "You do not have permission to lock/unlock this thread.", [], 403);
        }

        // Determine new lock status
        $newLockStatus = $thread['locked'] ? 0 : 1;
        $lockedBy = $newLockStatus ? $_SESSION['userId'] : null;

        // Update the thread's lock status
        $stmt = $db->query(
            "UPDATE threads SET locked = :locked, lockedBy = :lockedBy WHERE id = :id",
            [
                ":locked" => $newLockStatus,
                ":lockedBy" => $lockedBy,
                ":id" => $body['threadId']
            ]
        );

        if (!$stmt) {
            throw new Exception("Failed to update thread lock status.");
        }

        $cache->delete("thread:" . $body['threadId']);

        $db->commit();
        // After locking thread
        $notificationManager = new \Backend\Utils\NotificationManager();
        $notificationManager->notifySystem(
            $thread['authorId'],
            "Thread Locked",
            "Your thread \"{$thread['title']}\" has been locked by a moderator",
            ['thread_id' => $threadId]
        );

        sendJsonResponse(true, "Thread lock status updated successfully.", [
            "locked" => $newLockStatus
        ], 200);
    } catch (Exception $e) {
        $db->rollBack();
        error_log($e->getMessage());
        sendJsonResponse(false, "An error occurred: " . $e->getMessage(), [], 500);
    }
} else {
    //  Invalid HTTP method
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
