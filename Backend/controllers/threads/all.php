<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$threadsPerPage = 20;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $threadsPerPage;

try {
   $db->beginTransaction();

   // Fetch all categories with thread count
   $stmt = $db->query("
        SELECT 
            c.id,
            c.name,
            c.description,
            COUNT(DISTINCT t.id) as threadCount,
            MAX(t.createdAt) as lastActivity
        FROM categories c
        LEFT JOIN threadCategoryLink tcl ON c.id = tcl.categoryId
        LEFT JOIN threads t ON tcl.threadId = t.id AND t.isDeleted = 0
        GROUP BY c.id, c.name, c.description
        ORDER BY c.name ASC
    ");
   $categories = $db->getAll($stmt);

   // Fetch threads grouped by category with user info
   $stmt = $db->query("
        SELECT 
            t.id,
            t.title,
            t.userId,
            t.createdAt,
            t.status,
            t.viewsCount,
            t.upvoteCount,
            t.downvoteCount,
            t.locked,
            u.username,
            c.id as categoryId,
            c.name as categoryName,
            (SELECT COUNT(*) FROM comments WHERE threadId = t.id AND isDeleted = 0) as commentCount
        FROM threads t
        INNER JOIN users u ON t.userId = u.id
        LEFT JOIN threadCategoryLink tcl ON t.id = tcl.threadId
        LEFT JOIN categories c ON tcl.categoryId = c.id
        WHERE t.isDeleted = 0
        ORDER BY c.name ASC, t.createdAt DESC
        LIMIT :limit OFFSET :offset
    ", [
      ":limit" => (int)$threadsPerPage,
      ":offset" => (int)$offset
   ]);
   $threads = $db->getAll($stmt);

   // Group threads by category
   $threadsByCategory = [];
   $uncategorized = [];

   foreach ($threads as $thread) {
      if ($thread['categoryId']) {
         $threadsByCategory[$thread['categoryId']][] = $thread;
      } else {
         $uncategorized[] = $thread;
      }
   }

   // Count total threads
   $stmt = $db->query("SELECT COUNT(*) AS total FROM threads WHERE isDeleted = 0");
   $totalThreadsResult = $db->getOne($stmt);
   $totalThreads = (int)$totalThreadsResult['total'];
   $totalPages = ceil($totalThreads / $threadsPerPage);

   $db->commit();

   view("threads/all.view.php", [
      "heading" => "Forum Categories",
      "categories" => $categories,
      "threadsByCategory" => $threadsByCategory,
      "uncategorized" => $uncategorized,
      "currentPage" => $page,
      "totalPages" => $totalPages,
   ]);
} catch (Exception $e) {
   $db->rollBack();
   error_log($e->getMessage());
   sendJsonResponse(false, $e->getMessage(), [], 500);
}
