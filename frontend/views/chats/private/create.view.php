<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-4">
    <h1 class="text-center"><?= htmlspecialchars($heading) ?></h1>

    <form action="/private-chat/new" method="POST" class="mt-3">
        <div class="mb-3">
            <label for="recipientId" class="form-label">Select a User:</label>
            <select id="recipientId" name="recipientId" class="form-select" required>
                <option value="" disabled selected>Choose a user</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>">
                        <?= htmlspecialchars($user['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success w-100">Start Chat</button>
    </form>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>