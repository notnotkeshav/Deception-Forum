<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-4">
   <h2 class="text-center"><?= $heading ?></h2>
   <a href="/group-chat/new" class="btn btn-primary mb-3">Start a New Chat</a>

   <?php if (!empty($groups)): ?>   
      <ul class="list-group">
         <?php foreach ($groups as $group): ?>
            <li class="list-group-item d-flex align-items-center">
               <a href="/group-chat?id=<?= htmlspecialchars($group['id']) ?>" class="text-decoration-none text-dark">
                  <strong><?= htmlspecialchars($group['groupName']) ?></strong>
               </a>
            </li>
         <?php endforeach; ?>
      </ul>
   <?php else: ?>
      <p class="text-muted text-center">No chats found</p>
   <?php endif; ?>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>