<?php

use Backend\Utils\AdvancedFeatures;

// POST /drafts/save — Save or update draft

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort(405, ['message' => 'Method not allowed']);
}

$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
    sendJsonResponse(false, 'Unauthorized', [], 401);
}

$draftType = $_POST['draftType'] ?? null; // 'thread', 'comment', 'message'
$content = $_POST['content'] ?? '';
$threadId = $_POST['threadId'] ?? null;
$metadata = json_decode($_POST['metadata'] ?? '{}', true);

if (!$draftType || !in_array($draftType, ['thread', 'comment', 'message'])) {
    sendJsonResponse(false, 'Invalid draft type', [], 400);
}

if (empty($content)) {
    sendJsonResponse(false, 'Content required', [], 400);
}

try {
    $draftId = AdvancedFeatures::saveDraft($userId, $draftType, $content, $threadId, $metadata);
    sendJsonResponse(true, 'Draft saved', ['draftId' => $draftId]);
} catch (\Exception $e) {
    sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
}
