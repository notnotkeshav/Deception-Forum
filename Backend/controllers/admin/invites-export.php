<?php

use Backend\Utils\InviteManager;

// GET /admin/invites/export — Export invites as CSV

if ($_SESSION['accessLevel'] < 5) {
    abort(403, ['message' => 'Superadmin access required']);
}

$batchId = $_GET['batchId'] ?? null;

try {
    $csv = InviteManager::exportInviteCodes($batchId, 'csv');

    // Send CSV file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="invites_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $csv;
    exit();
} catch (\Exception $e) {
    sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
}
