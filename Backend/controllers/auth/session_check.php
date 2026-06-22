<?php

use Backend\Core\App;

// Return current session status
$currentTime = time();
$sessionStarted = $_SESSION['session_started'] ?? $currentTime;
$lastActivity = $_SESSION['last_activity'] ?? $currentTime;
$sessionLifetime = SESSION_LIFETIME_SECONDS;
$timeElapsed = $currentTime - $sessionStarted;
$timeRemaining = $sessionLifetime - $timeElapsed;

sendJsonResponse(true, 'Session status', [
  'session_started' => $sessionStarted,
  'last_activity' => $lastActivity,
  'time_elapsed' => $timeElapsed,
  'time_remaining' => max(0, $timeRemaining),
  'session_lifetime' => $sessionLifetime,
  'will_expire_at' => $sessionStarted + $sessionLifetime,
  'user_id' => $_SESSION['userId'],
  'username' => $_SESSION['user']['username'] ?? 'Unknown'
]);
