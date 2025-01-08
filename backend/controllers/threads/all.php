<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$threadsPerPage = 10;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $threadsPerPage;

try {
   // Begin transaction for thread operations
   $db->beginTransaction();

   // Count total threads
   $stmt = $db->query("SELECT COUNT(*) AS total FROM threads WHERE isDeleted = 0");
   $totalThreadsResult = $db->getOne($stmt);

   if (!$totalThreadsResult) {
      throw new Exception("Failed to retrieve thread count.");
   }

   $totalThreads = (int)$totalThreadsResult['total'];
   $totalPages = ceil($totalThreads / $threadsPerPage);

   // Fetch threads for the current page
   $stmt = $db->query(
      "SELECT * FROM threads WHERE isDeleted = 0 ORDER BY createdAt DESC LIMIT :limit OFFSET :offset",
      [
         ":limit" => (int)$threadsPerPage,
         ":offset" => (int)$offset
      ]
   );

   $threads = $db->getAll($stmt);
   $db->commit();

   // Render the view
   view("threads/all.view.php", [
      "heading" => "All Threads",
      "threads" => $threads,
      "currentPage" => $page,
      "totalPages" => $totalPages,
   ]);
} catch (Exception $e) {
   $db->rollBack();
   error_log($e->getMessage());
   sendJsonResponse(false,  $e->getMessage(), [], 500);
}
