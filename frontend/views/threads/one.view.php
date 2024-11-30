<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div id="thread-container"
   data-thread-id="<?php echo htmlspecialchars($thread['id']); ?>"
   data-thread-locked="<?php echo htmlspecialchars($thread['locked']); ?>">

   <h1><?php echo htmlspecialchars($thread['title']); ?></h1>

   <div class="thread-votes" id="thread-<?php echo $thread['id']; ?>">
      <button
         id="upvote-thread"
         data-thread-id="<?php echo $thread['id']; ?>"
         class="btn btn-sm btn-primary"
         <?php if ($thread['locked'] == 1) echo 'disabled'; ?>>
         👍 Upvote
      </button>
      <span class="vote-count upvotes">
         <?php echo htmlspecialchars($thread['upvoteCount']); ?>
      </span>

      <button
         id="downvote-thread"
         data-thread-id="<?php echo $thread['id']; ?>"
         class="btn btn-sm btn-secondary"
         <?php if ($thread['locked'] == 1) echo 'disabled'; ?>>
         👎 Downvote
      </button>
      <span class="vote-count downvotes">
         <?php echo htmlspecialchars($thread['downvoteCount']); ?>
      </span>
   </div>


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

   <!-- Admin/Moderator Lock/Unlock Button -->
   <?php if ($_SESSION['user']['accessLevel'] >= 10) : ?>
      <button id="lock-unlock-btn"
         data-thread-id="<?php echo $thread['id']; ?>"
         class="btn btn-large <?php echo $thread['locked'] ? 'btn-danger' : 'btn-success'; ?>">
         <?php echo $thread['locked'] ? 'Unlock Thread' : 'Lock Thread'; ?>
      </button>
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