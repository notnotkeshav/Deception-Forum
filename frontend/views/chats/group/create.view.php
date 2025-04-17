<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-4">
    <h1 class="text-center"><?= htmlspecialchars($heading) ?></h1>

    <form action="" method="POST" class="mt-3">
        <div class="mb-3">
            <label for="groupName" class="form-label">Group Name:</label>
            <input type="text" id="groupName" name="groupName" class="form-control" placeholder="Enter group name" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Create Group Chat</button>
    </form>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>
