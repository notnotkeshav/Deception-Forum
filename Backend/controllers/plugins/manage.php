<?php

use Backend\Utils\AdvancedFeatures;

// GET /plugins/manage — List user's installed plugins
// POST /plugins/manage — Install plugin for user
// PUT /plugins/manage — Update plugin configuration
// DELETE /plugins/manage — Uninstall plugin

$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
    sendJsonResponse(false, 'Unauthorized', [], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $plugins = AdvancedFeatures::getUserPlugins($userId);
        sendJsonResponse(true, 'Plugins retrieved', ['plugins' => $plugins, 'count' => count($plugins)]);
    } catch (\Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $pluginId = $_POST['pluginId'] ?? null;
    $config = json_decode($_POST['config'] ?? '{}', true);

    if (!$pluginId) {
        sendJsonResponse(false, 'Plugin ID required', [], 400);
    }

    try {
        AdvancedFeatures::enablePluginForUser($userId, $pluginId, $config);
        sendJsonResponse(true, 'Plugin installed', ['pluginId' => $pluginId]);
    } catch (\Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $pluginId = $_POST['pluginId'] ?? null;
    $config = json_decode($_POST['config'] ?? '{}', true);

    if (!$pluginId) {
        sendJsonResponse(false, 'Plugin ID required', [], 400);
    }

    try {
        // Update configuration
        $db = App::resolve('Core\Database');
        $db->query(
            "UPDATE user_plugins SET config = :config WHERE userId = :userId AND pluginId = :pluginId",
            [':userId' => $userId, ':pluginId' => $pluginId, ':config' => json_encode($config)]
        );
        sendJsonResponse(true, 'Plugin configuration updated', ['pluginId' => $pluginId]);
    } catch (\Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    verifyCsrfToken($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');

    $pluginId = $_POST['pluginId'] ?? $_GET['pluginId'] ?? null;

    if (!$pluginId) {
        sendJsonResponse(false, 'Plugin ID required', [], 400);
    }

    try {
        AdvancedFeatures::disablePluginForUser($userId, $pluginId);
        sendJsonResponse(true, 'Plugin uninstalled', ['pluginId' => $pluginId]);
    } catch (\Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
    }
}
