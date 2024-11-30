<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div id="thread-container"
   data-thread-id="<?php echo htmlspecialchars($thread['id']);  ?>"
   data-thread-locked="<?php echo htmlspecialchars($thread['locked']); ?>"
   >
   <h1><?php echo htmlspecialchars($thread['title']); ?></h1>

   <?php if ($_SESSION['userId'] == $thread['userId']) : ?>
      <?php if (!$thread['locked']): ?>
         <form action="/thread/edit" method="get">
            <input type="hidden" name="id" value="<?php echo $thread['id']; ?>">
            <button type="submit">Edit Thread</button>
         </form>

         <form action="" method="post">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit">Delete</button>
         </form>
      <?php else: ?>
         <p><strong>This thread is locked. Editing and deleting are disabled.</strong></p>
      <?php endif; ?>
   <?php endif; ?>

   <div class="thread-details">
      <p><strong>Created at:</strong> <?php echo htmlspecialchars($thread['createdAt']); ?></p>
      <?php if ($thread['editedAt']): ?>
         <p><strong>Last edited at:</strong> <?php echo htmlspecialchars($thread['editedAt']); ?></p>
      <?php endif; ?>
      <p><?php echo nl2br($thread['content']); ?></p>
   </div>

   <?php if (isset($thread['category']) && $thread['category'] !== null): ?>
      <h3>Category: <?php echo ucfirst(htmlspecialchars($thread['category']['name'])); ?></h3>
   <?php else: ?>
      <p>No category assigned to this thread.</p>
   <?php endif; ?>

   <h3>Images</h3>
   <div class="thread-images">
      <?php foreach ($thread['images'] as $image): ?>
         <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="Thread Image" style="max-width: 200px; max-height: 200px; margin: 5px;">
      <?php endforeach; ?>
   </div>
</div>

<?php require(base_path("/frontend/views/partials/comments.view.php")); ?>
<?php require(base_path("/frontend/views/partials/footer.php")); ?>