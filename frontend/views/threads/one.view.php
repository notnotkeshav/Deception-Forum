<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div id="thread-container"
   data-thread-id="<?php echo htmlspecialchars($thread['id']); ?>"
   data-thread-locked="<?php echo htmlspecialchars($thread['locked']); ?>"
   class="container mt-4">

   <h1 class="display-4"><?php echo htmlspecialchars($thread['title']); ?></h1>

   <div class="d-flex align-items-center mb-3">
      <div class="me-3 thread">
         <button
            id="upvote-thread"
            data-thread-id="<?php echo $thread['id']; ?>"
            class="btn btn-sm btn-primary"
            <?php if ($thread['locked'] == 1) echo 'disabled'; ?>>
            👍 Upvote
         </button>
         <span class="badge bg-light text-dark vote-count" id="thread-upvotes-count"><?php echo htmlspecialchars($thread['upvoteCount']); ?></span>
      </div>
      <div class="thread">
         <button
            id="downvote-thread"
            data-thread-id="<?php echo $thread['id']; ?>"
            class="btn btn-sm btn-secondary"
            <?php if ($thread['locked'] == 1) echo 'disabled'; ?>>
            👎 Downvote
         </button>
         <span class="badge bg-light text-dark vote-count" id="thread-downvotes-count"><?php echo htmlspecialchars($thread['downvoteCount']); ?></span>
      </div>
   </div>

   <?php if ($_SESSION['userId'] == $thread['userId']) : ?>
      <?php if (!$thread['locked']): ?>
         <div class="mb-3">
            <form class="d-inline-block me-2" action="thread/edit" method="get">
               <input type="hidden" name="id" value="<?php echo $thread['id']; ?>">
               <button type="submit" class="btn btn-warning btn-sm">Edit Thread</button>
            </form>
            <form action="" method="post" class="d-inline-block">
               <input type="hidden" name="_method" value="DELETE">
               <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
         </div>
      <?php else: ?>
         <p class="text-danger"><strong>This thread is locked. Editing and deleting are disabled.</strong></p>
      <?php endif; ?>
   <?php endif; ?>

   <!-- Admin/Moderator Lock/Unlock Button -->
   <?php if ($_SESSION['moderator']) : ?>
      <button id="lock-unlock-btn"
         data-thread-id="<?php echo $thread['id']; ?>"
         class="btn <?php echo $thread['locked'] ? 'btn-danger' : 'btn-success'; ?> btn-lg mb-4">
         <?php echo $thread['locked'] ? 'Unlock Thread' : 'Lock Thread'; ?>
      </button>
   <?php endif; ?>

   <div class="thread-details mb-4">
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
   <div class="d-flex flex-wrap mb-4">
      <?php foreach ($thread['images'] as $image): ?>
         <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="Thread Image" class="img-thumbnail me-2" style="max-width: 200px; max-height: 200px;">
      <?php endforeach; ?>
   </div>
</div>

<?php require(base_path("/frontend/views/partials/comments.view.php")); ?>
<?php require(base_path("/frontend/views/partials/footer.php")); ?>