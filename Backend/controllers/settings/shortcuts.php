<?php

use Backend\Utils\AdvancedFeatures;

// GET /settings/shortcuts — Get default and user shortcuts
// POST /settings/shortcuts — Update keyboard shortcut
// DELETE /settings/shortcuts — Reset shortcuts to default

$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
    sendJsonResponse(false, 'Unauthorized', [], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $defaultShortcuts = AdvancedFeatures::getDefaultShortcuts();
    $userShortcuts = AdvancedFeatures::getUserShortcuts($userId);

    // Merge with defaults
    $shortcuts = [];
    foreach ($defaultShortcuts as $default) {
        $shortcut = [
            'action' => $default['action'],
            'keys' => $default['keys'],
            'description' => $default['description'],
            'category' => $default['category'],
            'enabled' => true
        ];

        // Override with user settings if exists
        foreach ($userShortcuts as $user) {
            if ($user['action'] === $default['action']) {
                $shortcut['keys'] = $user['keys'];
                $shortcut['enabled'] = $user['enabled'];
                break;
            }
        }

        $shortcuts[] = $shortcut;
    }

    sendJsonResponse(true, 'Shortcuts retrieved', ['shortcuts' => $shortcuts]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $action = $_POST['action'] ?? null;
    $keys = $_POST['keys'] ?? null;

    if (!$action || !$keys) {
        sendJsonResponse(false, 'Action and keys required', [], 400);
    }

    // Validate keys format (simple validation)
    if (!preg_match('/^[a-z0-9+\-\s,]+$/i', $keys)) {
        sendJsonResponse(false, 'Invalid key format', [], 400);
    }

    try {
        AdvancedFeatures::setShortcut($userId, $action, $keys);
        sendJsonResponse(true, 'Shortcut updated', ['action' => $action, 'keys' => $keys]);
    } catch (\Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    verifyCsrfToken($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');

    try {
        AdvancedFeatures::resetShortcutsToDefault($userId);
        sendJsonResponse(true, 'Shortcuts reset to default');
    } catch (\Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
    }
}
