<?php

use Backend\Core\App;
use Backend\Utils\TOTP;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$method = $method ?? $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
  sendJsonResponse(false, "Invalid HTTP method", [], 405);
}

try {
  $currentTime = time();
  $userId = $_SESSION['userId'] ?? null;

  if (!$userId) {
    throw new Exception('No active session', 401);
  }

  // Get TOTP code from request
  $code = trim($_POST['totp_code'] ?? '');

  if (!$code || strlen($code) !== 6 || !ctype_digit($code)) {
    throw new Exception('Invalid TOTP code format', 400);
  }

  // Get user's TOTP secret
  $userIdHash = hash('sha256', $userId);
  $userCacheKey = "user_totp:" . $userIdHash;
  $cachedUser = $cache->get($userCacheKey);

  if (!$cachedUser || $cachedUser['expiration'] < $currentTime) {
    $statement = $db->query('SELECT * FROM users WHERE id = ? AND isDeleted = 0', [$userId]);
    $user = $db->getOne($statement);

    if (!$user) {
      throw new Exception('User not found', 404);
    }

    $cache->set($userCacheKey, $user, 1800);
  } else {
    $user = $cachedUser['value'];
  }

  if (!$user['totp_enabled'] || empty($user['totp_secret'])) {
    throw new Exception('TOTP not enabled', 400);
  }

  // Verify TOTP code
  if (!TOTP::verify($user['totp_secret'], $code, 2)) {
    // Log failed attempt
    error_log("Failed session renewal TOTP for user: {$user['username']} from IP: " . $_SERVER['REMOTE_ADDR']);
    throw new Exception('Invalid TOTP code', 401);
  }

  // Renew session
  $_SESSION['session_started'] = $currentTime;
  $_SESSION['last_activity'] = $currentTime;
  $_SESSION['token_expiration'] = $currentTime + (24 * 60 * 60);

  // Regenerate session ID for security
  session_regenerate_id(true);

  // Update last login time
  $db->query('UPDATE users SET lastLogin = CURRENT_TIMESTAMP WHERE id = ?', [$userId]);

  sendJsonResponse(true, 'Session renewed successfully', [
    'new_session_started' => $currentTime,
    'expires_at' => $currentTime + (150 * 60)
  ]);
} catch (Exception $e) {
  error_log("Session renewal error: " . $e->getMessage());
  $httpCode = $e->getCode();
  if ($httpCode < 100 || $httpCode >= 600) {
    $httpCode = 500;
  }
  sendJsonResponse(false, $e->getMessage(), [], $httpCode);
}
