<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$threadsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $threadsPerPage;

// Count total threads
$stmt = $db->query("SELECT COUNT(*) AS total FROM threads WHERE deleted = 0");
$totalThreads = $db->getOne($stmt)['total'];
$totalPages = ceil($totalThreads / $threadsPerPage);

// Fetch threads for the current page
$stmt = $db->query(
   "SELECT * FROM threads WHERE deleted = 0 LIMIT :limit OFFSET :offset",
   [
      ":limit" => (int)$threadsPerPage,
      ":offset" => (int)$offset
   ]
);

$threads = $db->getAll($stmt);

view("threads/all.view.php", [
   "heading" => "All Threads",
   "threads" => $threads,
   "currentPage" => $page,
   "totalPages" => $totalPages,
]);
