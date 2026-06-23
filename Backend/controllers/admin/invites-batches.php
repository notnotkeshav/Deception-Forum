<?php

use Backend\Utils\InviteManager;

// GET /admin/invites/batches — Get invite batches

if ($_SESSION['accessLevel'] < 5) {
    sendJsonResponse(false, 'Superadmin access required', [], 403);
}

$limit = min((int)($_GET['limit'] ?? 20), 100);
$offset = (int)($_GET['offset'] ?? 0);

try {
    $batches = InviteManager::getBatches($limit, $offset, null);

    sendJsonResponse(true, 'Batches retrieved', [
        'batches' => $batches,
        'limit' => $limit,
        'offset' => $offset
    ]);
} catch (\Exception $e) {
    sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
}
