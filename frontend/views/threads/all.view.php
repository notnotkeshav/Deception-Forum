<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-4">
    <h3><a href="threads/new" class="btn btn-primary">Create New Thread</a></h3>
    <h1 class="my-4">All Threads</h1>

    <ul class="list-group">
        <?php foreach ($threads as $thread): ?>
            <li class="list-group-item">
                <a href="/thread?id=<?php echo $thread['id']; ?>" class="text-decoration-none"><?php echo $thread['title']; ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Pagination Links -->
    <nav aria-label="Page navigation example" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($currentPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>">Previous</a>
                </li>
            <?php endif; ?>

            <li class="page-item disabled">
                <span class="page-link">Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?></span>
            </li>

            <?php if ($currentPage < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>