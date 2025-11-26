<?php
const BASE_PATH = __DIR__ . "/";
require(BASE_PATH . "Backend/Utils/functions.php");

loadEnv(base_path("Backend/Core/.env"));

spl_autoload_register(function ($class) {
   $class =  str_replace('\\', '/', $class);
   require(base_path($class . ".php"));
});

require(base_path("Backend/Core/bootstrap.php"));

use Backend\Core\App;

// Start session to get user ID
session_start();

$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
  // If no session, use first user from database
  $db = App::container()->resolve('Core\Database');
  $stmt = $db->query("SELECT id FROM users LIMIT 1", []);
  $user = $db->getOne($stmt);
  $userId = $user['id'] ?? null;
}

if (!$userId) {
  die("No users found in database. Create a user first.");
}

echo "Creating test notifications for user: {$userId}\n\n";

// Create test notifications
$notifications = [
  [
    'type' => 'thread_comment',
    'title' => 'New Comment on Your Thread',
    'message' => 'John Doe commented on your thread "How to learn PHP"',
    'data' => ['thread_id' => 'test-thread-123', 'comment_id' => 'test-comment-456']
  ],
  [
    'type' => 'comment_reply',
    'title' => 'Reply to Your Comment',
    'message' => 'Jane Smith replied to your comment',
    'data' => ['thread_id' => 'test-thread-456', 'comment_id' => 'test-comment-789']
  ],
  [
    'type' => 'thread_vote',
    'title' => 'Thread Upvoted',
    'message' => 'Your thread "Best practices for MySQL" received an upvote!',
    'data' => ['thread_id' => 'test-thread-789']
  ],
  [
    'type' => 'mention',
    'title' => 'You Were Mentioned',
    'message' => 'Bob mentioned you in a comment',
    'data' => ['thread_id' => 'test-thread-999', 'comment_id' => 'test-comment-111']
  ],
  [
    'type' => 'system',
    'title' => 'Welcome to the Forum!',
    'message' => 'Thank you for joining our community. Please read the rules and guidelines.',
    'data' => null
  ]
];

$db = App::container()->resolve('Core\Database');

foreach ($notifications as $notif) {
  try {
    $result = createNotification(
      $userId,
      $notif['type'],
      $notif['title'],
      $notif['message'],
      $notif['data']
    );

    if ($result) {
      echo "✓ Created: {$notif['title']}\n";
    } else {
      echo "✗ Failed (user disabled this type): {$notif['title']}\n";
    }
  } catch (Exception $e) {
    echo "✗ Error creating {$notif['title']}: " . $e->getMessage() . "\n";
  }
}

echo "\n✅ Done! Visit /notifications to see your test notifications.\n";
