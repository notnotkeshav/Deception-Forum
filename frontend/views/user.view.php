<?php
require(base_path("/frontend/views/partials/header.php"));
require(base_path("/frontend/views/partials/navbar.php"));

// Pass data to the view or render the page
?>

<div class="container my-5">
    <!-- User Profile -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <img src="<?php echo htmlspecialchars($user['profilePic']); ?>" class="card-img-top" alt="Profile Picture">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="card-text">Email: <?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="card-text">Status: <strong><?php echo htmlspecialchars($user['status']); ?></strong></p>
                    <p class="card-text">Reputation: <?php echo htmlspecialchars($user['reputation']); ?></p>
                    <!-- Buttons for editing profile and changing password -->
                    <div class="d-flex justify-content-between mt-3">
                        <a href="edit-profile" class="btn btn-primary">Edit Profile</a>
                        <a href="change-password" class="btn btn-warning">Change Password</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Threads -->
    <div class="mt-4">
        <h2>Your Threads:</h2>
        <div class="list-group">
            <?php foreach ($threads as $thread) { ?>
                <a href="/thread?id=<?php echo $thread['id']?>" class="list-group-item">
                    <h5 class="mb-1"><?php echo htmlspecialchars($thread['title']); ?></h5>
                    <div class="mb-1"><?php echo $thread['content']; ?></div>
                    <small>Views: <?php echo htmlspecialchars($thread['viewsCount']); ?> | 
                           Upvotes: <?php echo htmlspecialchars($thread['upvoteCount']); ?> | 
                           Downvotes: <?php echo htmlspecialchars($thread['downvoteCount']); ?> | 
                           Status: <?php echo htmlspecialchars($thread['status']); ?></small>
                </a>
            <?php } ?>
        </div>
    </div>

    <!-- User Comments -->
    <div class="mt-4">
        <h2>Your Comments:</h2>
        <div class="list-group">
            <?php foreach ($comments as $comment) { ?>
                <div class="list-group-item">
                    <p class="mb-1"><?php echo $comment['content']; ?></p>
                    <small>Upvotes: <?php echo htmlspecialchars($comment['upvoteCount']); ?> | 
                           Downvotes: <?php echo htmlspecialchars($comment['downvoteCount']); ?> | 
                           Status: <?php echo htmlspecialchars($comment['status']); ?></small>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>
