<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-4">
    <h1 class="text-center"><?= htmlspecialchars($heading) ?></h1>

    <form action="" method="POST" class="mt-4">
        <input type="hidden" name="groupId" value="<?= htmlspecialchars($groupId) ?>">

        <div class="mb-3">
            <label for="memberId" class="form-label">Select a user to add:</label>
            <select id="memberId" name="memberId" class="form-select" required>
                <option value="" disabled selected>Select user</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>">
                        <?= htmlspecialchars($user['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-success w-100">Add Member</button>
    </form>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>