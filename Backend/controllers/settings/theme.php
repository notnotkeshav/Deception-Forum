<?php

use Backend\Utils\AdvancedFeatures;

// GET /settings/theme — Get user's theme settings
// POST /settings/theme — Update theme (level 4+ for custom)
// PUT /settings/theme — Create new custom theme
// DELETE /settings/theme — Delete custom theme

$userId = $_SESSION['userId'] ?? null;
$accessLevel = $_SESSION['accessLevel'] ?? 0;

if (!$userId) {
    sendJsonResponse(false, 'Unauthorized', [], 401);
}

// Level 4+ required for custom themes
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $accessLevel < 4) {
    sendJsonResponse(false, 'Access denied. Level 4+ required for custom themes', [], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user's themes and active theme
    $themes = AdvancedFeatures::getUserThemes($userId);
    $activeTheme = AdvancedFeatures::getActiveTheme($userId);
    $customCSS = AdvancedFeatures::getCustomCSS($userId);

    sendJsonResponse(true, 'Themes retrieved', [
        'themes' => $themes,
        'activeTheme' => $activeTheme,
        'customCSS' => $customCSS['customCSS'] ?? '',
        'customCSSEnabled' => $customCSS['enableCustomCSS'] ?? false
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set active theme
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $themeId = $_POST['themeId'] ?? null;

    if (!$themeId) {
        sendJsonResponse(false, 'Theme ID required', [], 400);
    }

    // Verify theme belongs to user
    $theme = AdvancedFeatures::getTheme($themeId);
    if (!$theme || $theme['userId'] !== $userId) {
        sendJsonResponse(false, 'Theme not found', [], 404);
    }

    AdvancedFeatures::setActiveTheme($userId, $themeId);
    sendJsonResponse(true, 'Theme activated', ['themeId' => $themeId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Create new custom theme
    verifyCsrfToken($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');

    $name = $_POST['name'] ?? null;
    $colors = json_decode($_POST['colors'] ?? '{}', true);
    $cssVars = json_decode($_POST['cssVars'] ?? '{}', true);
    $description = $_POST['description'] ?? '';

    if (!$name || empty($colors)) {
        sendJsonResponse(false, 'Name and colors required', [], 400);
    }

    try {
        $themeId = AdvancedFeatures::createTheme($userId, $name, $colors, $cssVars, false, $description);
        sendJsonResponse(true, 'Theme created', ['themeId' => $themeId]);
    } catch (\Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage(), [], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Delete theme
    verifyCsrfToken($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');

    $themeId = $_POST['themeId'] ?? $_GET['themeId'] ?? null;

    if (!$themeId) {
        sendJsonResponse(false, 'Theme ID required', [], 400);
    }

    $theme = AdvancedFeatures::getTheme($themeId);
    if (!$theme || $theme['userId'] !== $userId) {
        sendJsonResponse(false, 'Theme not found', [], 404);
    }

    AdvancedFeatures::deleteTheme($themeId);
    sendJsonResponse(true, 'Theme deleted', ['themeId' => $themeId]);
}
