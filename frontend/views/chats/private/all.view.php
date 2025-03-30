<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-4">
   <h2 class="text-center">All Private Chats</h2>
   <a href="/private-chat/new" class="btn btn-primary mb-3">Start a New Chat</a>

   <?php if (!empty($chats)): ?>
      <ul class="list-group">
         <?php foreach ($chats as $chat): ?>
            <li class="list-group-item d-flex align-items-center">
               <a href="/private-chat?id=<?= htmlspecialchars($chat['id']) ?>" class="text-decoration-none text-dark">
                  <strong><?= htmlspecialchars($chat['id']) ?></strong>
               </a>
            </li>
         <?php endforeach; ?>
      </ul>
   <?php else: ?>
      <p class="text-muted text-center">No chats found</p>
   <?php endif; ?>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>