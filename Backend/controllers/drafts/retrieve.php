<?php

use Backend\Utils\AdvancedFeatures;

// GET /drafts/retrieve — Get saved drafts

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    abort(405, ['message' => 'Method not allowed']);
}

$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
    sendJsonResponse(false, 'Unauthorized', [], 401);
}

$draftType = $_GET['draftType'] ?? null;
$threadId = $_GET['threadId'] ?? null;

try {
    if ($draftType) {
        // Get specific draft
        $draft = AdvancedFeatures::getDraft($userId, $draftType, $threadId);
        sendJsonResponse(true, 'Draft retrieved', ['draft' => $draft]);
    } else {
        // Get all drafts
        $drafts = AdvancedFeatures::getUserDrafts($userId);
        sendJsonResponse(true, 'Drafts retrieved', ['drafts' => $drafts]);
    }
} catch (\Exception $e) {
    sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
}
