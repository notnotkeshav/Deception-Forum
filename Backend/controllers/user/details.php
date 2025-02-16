<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

// Only handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
   // Start database transaction
   $db->beginTransaction();

   try {
      // Fetch the logged-in user's from the session
      $user = $_SESSION['user'];
      $userId = $_SESSION['userId'];

      if (!$user) {
         throw new Exception("User not found.");
      }

      // Fetch threads created by the user
      $threadsQuery = "SELECT * FROM threads WHERE userId = :userId and isDeleted = 0 ORDER BY createdAt DESC";
      $stmt = $db->query($threadsQuery, [":userId" => $userId]);
      $threads = $db->getAll($stmt);

      // Fetch comments made by the user
      $commentsQuery = "SELECT * FROM comments WHERE userId = :userId and isDeleted = 0 ORDER BY createdAt DESC";
      $stmt = $db->query($commentsQuery, [":userId" => $userId]);
      $comments = $db->getAll($stmt);

      $db->commit();

      view("user.view.php", [
         "user" => $user,
         "threads" => $threads,
         "comments" => $comments
      ]);
   } catch (Exception $e) {
      if ($db->inTransaction()) {
         $db->rollBack();
      }

      error_log($e->getMessage());
      sendJsonResponse(false, "Error fetching user data: " . $e->getMessage(), [], 500);
   }
} else {
   sendJsonResponse(false, "Method Not Allowed. Use GET.", [], 405);
}
