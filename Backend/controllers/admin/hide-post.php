<?php

use Backend\Utils\AdvancedFeatures;

// POST /admin/hide-post
// Hide or unhide a thread/comment (mod only)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort(405, ['message' => 'Method not allowed']);
}

verifyCsrfToken($_POST['csrf_token'] ?? '');

$postType = $_POST['postType'] ?? null; // 'thread' or 'comment'
$postId = $_POST['postId'] ?? null;
$action = $_POST['action'] ?? 'hide'; // hide or unhide
$reason = $_POST['reason'] ?? '';

if (!$postType || !$postId || !in_array($postType, ['thread', 'comment'])) {
    sendJsonResponse(false, 'Invalid parameters', [], 400);
}

if (!in_array($action, ['hide', 'unhide'])) {
    sendJsonResponse(false, 'Invalid action', [], 400);
}

// Check moderator privilege
if ($_SESSION['moderator'] !== true) {
    sendJsonResponse(false, 'Access denied', [], 403);
}

try {
    if ($postType === 'thread') {
        if ($action === 'hide') {
            AdvancedFeatures::hideThread($postId, $reason);
        } else {
            AdvancedFeatures::unhideThread($postId);
        }
    } else {
        if ($action === 'hide') {
            AdvancedFeatures::hideComment($postId, $reason);
        } else {
            AdvancedFeatures::unhideComment($postId);
        }
    }

    sendJsonResponse(true, "Post {$action}d successfully", ['postId' => $postId, 'postType' => $postType]);
} catch (\Exception $e) {
    sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
}
