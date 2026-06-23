<?php

use Backend\Utils\InviteManager;

// GET /admin/invites — View all invites (superadmin only)
// POST /admin/invites — Create new invites
// DELETE /admin/invites — Revoke invite

if ($_SESSION['accessLevel'] < 5) {
    sendJsonResponse(false, 'Superadmin access required (Level 5+)', [], 403);
}

$userId = $_SESSION['userId'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = min((int)($_GET['limit'] ?? 50), 200);
    $offset = (int)($_GET['offset'] ?? 0);

    $filters = [
        'status' => $_GET['status'] ?? null,
        'generatorId' => $_GET['generatorId'] ?? null,
        'batchId' => $_GET['batchId'] ?? null
    ];

    try {
        $invites = InviteManager::getAllInvites($limit, $offset, $filters);
        $stats = InviteManager::getInviteStats();

        sendJsonResponse(true, 'Invites retrieved', [
            'invites' => $invites,
            'stats' => $stats,
            'limit' => $limit,
            'offset' => $offset
        ]);
    } catch (\Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $action = $_POST['action'] ?? 'single';
    $count = (int)($_POST['count'] ?? 1);
    $expirationDays = (int)($_POST['expirationDays'] ?? 7);
    $batchName = $_POST['batchName'] ?? '';

    // Validate inputs
    if ($count < 1 || $count > 10000) {
        sendJsonResponse(false, 'Count must be between 1 and 10000', [], 400);
    }

    if ($expirationDays < 0 || $expirationDays > 365) {
        sendJsonResponse(false, 'Expiration must be between 0 and 365 days', [], 400);
    }

    try {
        if ($action === 'single') {
            $invite = InviteManager::createInvite($userId, $expirationDays);
            sendJsonResponse(true, 'Invite created', ['invite' => $invite]);
        } elseif ($action === 'bulk') {
            $result = InviteManager::createBulkInvites($userId, $count, $expirationDays, $batchName);
            sendJsonResponse(true, "Created {$count} invites", ['batch' => $result]);
        } else {
            sendJsonResponse(false, 'Invalid action', [], 400);
        }
    } catch (\Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    verifyCsrfToken($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');

    $inviteId = $_POST['inviteId'] ?? $_GET['inviteId'] ?? null;
    $batchId = $_POST['batchId'] ?? $_GET['batchId'] ?? null;

    try {
        if ($inviteId) {
            InviteManager::revokeInvite($inviteId, $userId);
            sendJsonResponse(true, 'Invite revoked');
        } elseif ($batchId) {
            InviteManager::revokeBatch($batchId, $userId);
            sendJsonResponse(true, 'Batch revoked');
        } else {
            sendJsonResponse(false, 'Invite ID or Batch ID required', [], 400);
        }
    } catch (\Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
    }
}
