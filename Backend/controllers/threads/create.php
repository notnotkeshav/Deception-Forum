<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

if ($method === "GET") {
   // Render the create thread view
   view("threads/create.view.php", [
      "heading" => "Create Page"
   ]);
} else {
   // Retrieve and validate input
   $title = trim($_POST['title'] ?? '');
   $content = trim($_POST['content'] ?? '');
   $category = strtolower(trim($_POST['category'] ?? ''));
   $imageUrls = $_POST['imageUrl'] ?? [];

   // Clear expired cache entries
   $cache->clearExpired();

   try {
      // Check for duplicate thread title
      if ($cache->get('thread:' . $title)) {
         sendJsonResponse(false, "A thread with the same title already exists.", null, 409); // Conflict
      }

      // Validate required fields
      if (empty($title) || empty($content) || empty($category)) {
         sendJsonResponse(false, "All fields are required.", [
            'missingFields' => [
               'title' => empty($title),
               'content' => empty($content),
               'category' => empty($category)
            ]
         ], 400);
      }

      // Begin transaction
      $db->beginTransaction();

      // Insert thread into the database
      $query = "INSERT INTO threads (title, content, userId) VALUES (:title, :content, :userId)";
      $db->query($query, [
         ':title' => $title,
         ':content' => $content,
         ':userId' => $_SESSION['userId']
      ]);
      $stmt = $db->query("SELECT id FROM threads WHERE title = :title and userId = :userId", [":title" => $title, ":userId" => $_SESSION['userId']]);
      $threadId = $db->getOne($stmt)['id'];

      // Handle category (fetch from cache or create if not exists)
      $categoryId = null;
      $cachedCategory = $cache->get('category:' . $category);
      if ($cachedCategory) {
         $categoryId = $cachedCategory['value'];
      } else {
         $stmt = $db->query("SELECT id FROM categories WHERE name = :name LIMIT 1", [':name' => $category]);
         $result = $db->getOne($stmt);

         if ($result) {
            $categoryId = $result['id'];
         } else {
            $db->query("INSERT INTO categories (name) VALUES (:name)", [':name' => $category]);
            $stmt = $db->query("SELECT id FROM categories WHERE name = :name LIMIT 1", [':name' => $category]);
            $categoryId = $db->getOne($stmt)['id'];
         }
         $cache->set('category:' . $category, $categoryId);
      }

      if (is_null($categoryId)) {
         sendJsonResponse(false, "Invalid category ID.", [], 400);
      }

      // Link thread to category
      $db->query("INSERT INTO threadcategorylink (threadId, categoryId) VALUES (:threadId, :categoryId)", [
         ':threadId' => $threadId,
         ':categoryId' => $categoryId
      ]);

      // Insert thread images if any
      $imagesInserted = 0;
      if (!empty($imageUrls)) {
         foreach ($imageUrls as $imageUrl) {
            if (!empty($imageUrl)) {
               $db->query("INSERT INTO threadimages (threadId, imageUrl) VALUES (:threadId, :imageUrl)", [
                  ':threadId' => $threadId,
                  ':imageUrl' => $imageUrl
               ]);
               $imagesInserted++;
            }
         }
      }

      $db->commit();
      $cache->set('thread:' . $title, $title);

      sendJsonResponse(true, "Thread created successfully.", [
         'threadId' => $threadId,
         'categoryId' => $categoryId,
         'imagesInserted' => $imagesInserted
      ], 201);
   } catch (Exception $e) {
      $db->rollBack();
      error_log($e->getMessage());
      sendJsonResponse(false, $e->getMessage(), [], 500);
   }
}
