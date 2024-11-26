<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>


<h3><a href="threads/new">Create New Thread</a></h3>
<h1>All Threads</h1>

<ul>
    <?php foreach ($threads as $thread): ?>
        <li>
            <a href="/thread?id=<?php echo $thread['id']; ?>"><?php echo $thread['title']; ?></a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Pagination Links -->
<div class="pagination">
    <?php if ($currentPage > 1): ?>
        <a href="?page=<?php echo $currentPage - 1; ?>">Previous</a>
    <?php endif; ?>

    <span>Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?></span>

    <?php if ($currentPage < $totalPages): ?>
        <a href="?page=<?php echo $currentPage + 1; ?>">Next</a>
    <?php endif; ?>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>