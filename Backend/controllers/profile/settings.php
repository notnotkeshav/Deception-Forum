<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
  abort(401, ['message' => 'Unauthorized']);
}

if ($method === 'GET') {
  try {
    // Get current privacy settings
    $stmt = $db->query(
      "SELECT * FROM profile_privacy WHERE userId = :userId",
      [':userId' => $userId]
    );
    $settings = $db->getOne($stmt);

    // If no settings exist, create defaults
    if (!$settings) {
      $db->query(
        "INSERT INTO profile_privacy (userId) VALUES (:userId)",
        [':userId' => $userId]
      );

      $stmt = $db->query(
        "SELECT * FROM profile_privacy WHERE userId = :userId",
        [':userId' => $userId]
      );
      $settings = $db->getOne($stmt);
    }

    view('profile/settings.view.php', [
      'heading' => 'Profile Privacy Settings',
      'settings' => $settings
    ]);
  } catch (Exception $e) {
    error_log("Error loading privacy settings: " . $e->getMessage());
    abort(500, ['message' => 'Error loading settings']);
  }
} elseif ($method === 'POST') {
  // Update all settings via form submission
  try {
    $db->query(
      "UPDATE profile_privacy SET 
                show_email = :show_email,
                show_name = :show_name,
                show_join_date = :show_join_date,
                show_last_login = :show_last_login,
                show_reputation = :show_reputation,
                show_threads = :show_threads,
                show_comments = :show_comments,
                show_stats = :show_stats,
                profile_visibility = :profile_visibility
             WHERE userId = :userId",
      [
        ':userId' => $userId,
        ':show_email' => isset($_POST['show_email']) ? 1 : 0,
        ':show_name' => isset($_POST['show_name']) ? 1 : 0,
        ':show_join_date' => isset($_POST['show_join_date']) ? 1 : 0,
        ':show_last_login' => isset($_POST['show_last_login']) ? 1 : 0,
        ':show_reputation' => isset($_POST['show_reputation']) ? 1 : 0,
        ':show_threads' => isset($_POST['show_threads']) ? 1 : 0,
        ':show_comments' => isset($_POST['show_comments']) ? 1 : 0,
        ':show_stats' => isset($_POST['show_stats']) ? 1 : 0,
        ':profile_visibility' => $_POST['profile_visibility'] ?? 'public'
      ]
    );

    $_SESSION['flash']['success'] = 'Privacy settings updated successfully';
    redirect('/profile/settings');
  } catch (Exception $e) {
    error_log("Error updating privacy settings: " . $e->getMessage());
    $_SESSION['flash']['error'] = 'Failed to update settings';
    redirect('/profile/settings');
  }
} elseif ($method === 'PUT') {
  // AJAX update for individual setting
  $body = getRequestBody();

  if (empty($body['setting']) || !isset($body['enabled'])) {
    sendJsonResponse(false, 'Invalid request data', [], 400);
  }

  $setting = $body['setting'];
  $enabled = (bool) $body['enabled'];

  $allowedSettings = [
    'show_email',
    'show_name',
    'show_join_date',
    'show_last_login',
    'show_reputation',
    'show_threads',
    'show_comments',
    'show_stats'
  ];

  if (!in_array($setting, $allowedSettings)) {
    sendJsonResponse(false, 'Invalid setting name', [], 400);
  }

  try {
    $db->query(
      "UPDATE profile_privacy SET {$setting} = :enabled WHERE userId = :userId",
      [':enabled' => $enabled ? 1 : 0, ':userId' => $userId]
    );

    sendJsonResponse(true, 'Setting updated');
  } catch (Exception $e) {
    error_log("Error updating setting: " . $e->getMessage());
    sendJsonResponse(false, 'Failed to update setting', [], 500);
  }
} else {
  sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
