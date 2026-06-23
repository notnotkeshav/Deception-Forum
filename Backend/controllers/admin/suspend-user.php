<?php

use Backend\Utils\AdvancedFeatures;

// POST /admin/suspend-user
// Suspend or unsuspend a user (admin only)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort(405, ['message' => 'Method not allowed']);
}

verifyCsrfToken($_POST['csrf_token'] ?? '');

$userId = $_POST['userId'] ?? null;
$action = $_POST['action'] ?? 'suspend'; // suspend or unsuspend
$reason = $_POST['reason'] ?? '';

if (!$userId) {
    sendJsonResponse(false, 'User ID required', [], 400);
}

if (!in_array($action, ['suspend', 'unsuspend'])) {
    sendJsonResponse(false, 'Invalid action', [], 400);
}

// Check admin privilege
if ($_SESSION['moderator'] !== true) {
    sendJsonResponse(false, 'Access denied', [], 403);
}

try {
    if ($action === 'suspend') {
        AdvancedFeatures::suspendUser($userId, $reason);
        sendJsonResponse(true, 'User suspended successfully', ['userId' => $userId]);
    } else {
        AdvancedFeatures::unsuspendUser($userId);
        sendJsonResponse(true, 'User unsuspended successfully', ['userId' => $userId]);
    }
} catch (\Exception $e) {
    sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
}
