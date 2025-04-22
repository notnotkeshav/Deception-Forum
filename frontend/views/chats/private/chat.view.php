<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-4">
    <h1 class="text-center"><?= htmlspecialchars($heading) ?> : <?= $_GET['id']?></h1>
    
    <div id="chat-window" class="border rounded p-3 bg-light" style="height: 60vh; overflow-y: auto;">
        <?php if (empty($messages)) : ?>
            <p class="text-center text-muted">No messages</p>
        <?php else : ?>
            <ul id="message-list" class="list-unstyled">
                <!--  Message will appear here -->
            </ul>
        <?php endif; ?>
    </div>

    <form id="sendMessageForm" class="mt-3">
        <input type="hidden" id="chatId" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">
        <div class="input-group">
            <input type="text" id="messageInput" class="form-control" placeholder="Type a message..." required>
            <button type="submit" class="btn btn-primary">Send</button>
        </div>
    </form>
</div>
<script src="public/javascripts/private-chat.js"></script>
<?php require(base_path("/frontend/views/partials/footer.php")); ?>