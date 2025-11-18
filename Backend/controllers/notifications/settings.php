<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
    abort(401, ['message' => 'Unauthorized']);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get current notification settings
        $settings = getNotificationSettings($userId);

        view('notifications/settings.view.php', [
            'heading' => 'Notification Settings',
            'settings' => $settings
        ]);
        break;

    case 'POST':
        // Update notification settings (form submission)
        $settings = [
            'thread_comment' => isset($_POST['thread_comment']),
            'comment_reply' => isset($_POST['comment_reply']),
            'thread_vote' => isset($_POST['thread_vote']),
            'comment_vote' => isset($_POST['comment_vote']),
            'new_thread' => isset($_POST['new_thread']),
            'mention' => isset($_POST['mention']),
            'system' => isset($_POST['system'])
        ];

        $success = updateNotificationSettings($userId, $settings);

        if ($success) {
            $_SESSION['flash']['success'] = 'Notification settings updated successfully';
        } else {
            $_SESSION['flash']['error'] = 'Failed to update notification settings';
        }

        redirect('/notifications/settings');
        break;

    case 'PUT':
        // AJAX update for individual setting toggle
        $requestBody = getRequestBody();

        if (empty($requestBody['setting']) || !isset($requestBody['enabled'])) {
            sendJsonResponse(false, 'Invalid request data', [], 400);
        }

        $setting = $requestBody['setting'];
        $enabled = (bool) $requestBody['enabled'];

        $allowedSettings = ['thread_comment', 'comment_reply', 'thread_vote', 'comment_vote', 'new_thread', 'mention', 'system'];

        if (!in_array($setting, $allowedSettings)) {
            sendJsonResponse(false, 'Invalid setting name', [], 400);
        }

        $success = updateNotificationSettings($userId, [$setting => $enabled]);

        sendJsonResponse($success, $success ? 'Setting updated' : 'Failed to update setting');
        break;

    default:
        abort(405, ['message' => 'Method not allowed']);
}
