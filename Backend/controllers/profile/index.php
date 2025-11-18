<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

// Get method from parent scope or directly from server
$method = $method ?? $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  // Get username from query parameter
  $username =  $_GET['u'] ?? null;
  error_log("Fetching profile for username: " . $username);

  if (!$username) {
    abort(404, ['message' => 'Username parameter is required. Use: /profile?u=username']);
  }

  // Remove any URL fragments
  $username = trim($username);

  try {
    // Check if user is viewing their own profile
    $currentUser = $_SESSION['user'] ?? null;
    $currentUsername = $currentUser['username'] ?? null;

    // Get user details by username
    $userStmt = $db->query(
      "SELECT u.id, u.username, u.name, u.profilePic, u.reputation, 
                    u.createdAt, u.lastLogin, u.status, u.upgrades,
                    COALESCE(pp.show_email, 0) as show_email,
                    COALESCE(pp.show_name, 1) as show_name,
                    COALESCE(pp.show_join_date, 1) as show_join_date,
                    COALESCE(pp.show_last_login, 0) as show_last_login,
                    COALESCE(pp.show_reputation, 1) as show_reputation,
                    COALESCE(pp.show_threads, 1) as show_threads,
                    COALESCE(pp.show_comments, 1) as show_comments,
                    COALESCE(pp.show_stats, 1) as show_stats,
                    COALESCE(pp.profile_visibility, 'public') as profile_visibility
             FROM users u
             LEFT JOIN profile_privacy pp ON u.id = pp.userId
             WHERE u.username = :username 
             AND u.isDeleted = 0",
      [':username' => $username]
    );
    $user = $db->getOne($userStmt);

    if (!$user) {
      abort(404, ['message' => 'User "' . htmlspecialchars($username) . '" not found']);
    }

    // Check if profile is private
    if ($user['profile_visibility'] === 'private') {
      abort(403, ['message' => 'This profile is private']);
    }

    // Build visible profile data
    $profile = [
      'username' => $user['username'],
      'profilePic' => $user['profilePic'],
      'status' => $user['status'],
      'upgrades' => $user['upgrades']
    ];

    if ($user['show_name']) {
      $profile['name'] = $user['name'];
    }

    if ($user['show_join_date']) {
      $profile['joinedDate'] = $user['createdAt'];
    }

    if ($user['show_last_login']) {
      $profile['lastLogin'] = $user['lastLogin'];
    }

    if ($user['show_reputation']) {
      $profile['reputation'] = $user['reputation'];
    }

    // Get stats if enabled
    $stats = [];
    if ($user['show_stats']) {
      $threadStmt = $db->query(
        "SELECT COUNT(*) as count FROM threads WHERE userId = :userId AND isDeleted = 0",
        [':userId' => $user['id']]
      );
      $threadCount = $db->getOne($threadStmt);
      $stats['threadCount'] = $threadCount['count'] ?? 0;

      $commentStmt = $db->query(
        "SELECT COUNT(*) as count FROM comments WHERE userId = :userId AND isDeleted = 0",
        [':userId' => $user['id']]
      );
      $commentCount = $db->getOne($commentStmt);
      $stats['commentCount'] = $commentCount['count'] ?? 0;

      $voteStmt = $db->query(
        "SELECT 
                    (SELECT COALESCE(SUM(upvoteCount - downvoteCount), 0) FROM threads WHERE userId = :userId1 AND isDeleted = 0) +
                    (SELECT COALESCE(SUM(upvoteCount - downvoteCount), 0) FROM comments WHERE userId = :userId2 AND isDeleted = 0) as totalVotes",
        [':userId1' => $user['id'], ':userId2' => $user['id']]
      );
      $voteResult = $db->getOne($voteStmt);
      $stats['totalVotes'] = $voteResult['totalVotes'] ?? 0;
    }

    // Get threads if enabled
    $threads = [];
    if ($user['show_threads']) {
      $threadsStmt = $db->query(
        "SELECT id, title, createdAt, viewsCount, upvoteCount, downvoteCount, status
                 FROM threads 
                 WHERE userId = :userId 
                 AND isDeleted = 0 
                 ORDER BY createdAt DESC 
                 LIMIT 10",
        [':userId' => $user['id']]
      );
      $threads = $db->getAll($threadsStmt);
    }

    // Get recent comments if enabled
    $comments = [];
    if ($user['show_comments']) {
      $commentsStmt = $db->query(
        "SELECT c.id, c.content, c.createdAt, c.upvoteCount, c.downvoteCount,
                        t.id as threadId, t.title as threadTitle
                 FROM comments c
                 JOIN threads t ON c.threadId = t.id
                 WHERE c.userId = :userId 
                 AND c.isDeleted = 0
                 AND t.isDeleted = 0
                 ORDER BY c.createdAt DESC 
                 LIMIT 10",
        [':userId' => $user['id']]
      );
      $comments = $db->getAll($commentsStmt);
    }

    view("profile/index.view.php", [
      "heading" => "Profile - " . $user['username'],
      "profile" => $profile,
      "stats" => $stats,
      "threads" => $threads,
      "comments" => $comments,
      "isOwnProfile" => false
    ]);
  } catch (Exception $e) {
    error_log("Error fetching profile: " . $e->getMessage());
    abort(500, ['message' => 'Error loading profile']);
  }
} else {
  sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
