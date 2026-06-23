<?php

use Backend\Utils\AdvancedFeatures;

// GET /plugins/marketplace — List public plugins

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    abort(405, ['message' => 'Method not allowed']);
}

$limit = min((int)($_GET['limit'] ?? 20), 100);
$offset = (int)($_GET['offset'] ?? 0);
$sort = $_GET['sort'] ?? 'downloads'; // downloads, rating, newest

try {
    $plugins = AdvancedFeatures::getPublicPlugins($limit, $offset);

    // Sort by requested field
    if ($sort === 'rating') {
        usort($plugins, fn($a, $b) => $b['rating'] <=> $a['rating']);
    } elseif ($sort === 'newest') {
        usort($plugins, fn($a, $b) => strtotime($b['createdAt']) <=> strtotime($a['createdAt']));
    }

    sendJsonResponse(true, 'Plugins retrieved', ['plugins' => $plugins, 'count' => count($plugins)]);
} catch (\Exception $e) {
    sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
}
