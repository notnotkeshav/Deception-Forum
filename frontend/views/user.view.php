<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⛧ USER PROFILE ⛧</title>
    <style>
        @font-face {
            font-family: 'vamp';
            src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #000;
            color: #fff;
            font-family: 'Courier New', monospace;
            min-height: 100vh;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }

        .profile-section {
            display: flex;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .profile-card {
            flex: 0 0 300px;
            background: #111;
            border: 2px solid #960d0d;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 0 15px rgba(255, 0, 0, 0.2);
        }

        .profile-pic {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border: 1px solid #333;
            margin-bottom: 1.5rem;
        }

        .profile-title {
            font-family: 'vamp', sans-serif;
            color: #f03;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #960d0d;
            padding-bottom: 0.5rem;
        }

        .profile-info {
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .profile-info strong {
            color: #f03;
        }

        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem;
            border: none;
            border-radius: 0.25rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
        }

        .btn-primary {
            background: #c40303;
            color: #fff;
        }

        .btn-primary:hover {
            background: #960d0d;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
        }

        .btn-warning {
            background: #960;
            color: #fff;
        }

        .btn-warning:hover {
            background: #c90;
            box-shadow: 0 0 10px rgba(255, 153, 0, 0.5);
        }

        .btn-secondary {
            background: #333;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #444;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
        }

        .content-section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-family: 'vamp', sans-serif;
            color: #f03;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #960d0d;
            padding-bottom: 0.5rem;
        }

        .thread-list, .comment-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .thread-item, .comment-item {
            background: #111;
            border: 1px solid #333;
            border-radius: 0.25rem;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .thread-item:hover, .comment-item:hover {
            border-color: #f03;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
        }

        .thread-title {
            color: #f03;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            text-decoration: none;
            display: block;
        }

        .thread-content, .comment-content {
            color: #ccc;
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }

        .thread-meta, .comment-meta {
            font-size: 0.8rem;
            color: #888;
        }

        .thread-meta span, .comment-meta span {
            margin-right: 1rem;
        }

        .status-active {
            color: #0f0;
        }

        .status-inactive {
            color: #f00;
        }
    </style>
</head>

<body>
    <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

    <div class="container">
        <!-- User Profile -->
        <div class="profile-section">
            <div class="profile-card">
                <img src="<?php echo htmlspecialchars($user['profilePic']); ?>" class="profile-pic" alt="PROFILE IMAGE">
                <h3 class="profile-title">⛧ <?php echo htmlspecialchars($user['name']); ?> ⛧</h3>
                <div class="profile-info">
                    <p><strong>EMAIL:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>STATUS:</strong> <span class="status-<?php echo strtolower(htmlspecialchars($user['status'])); ?>"><?php echo htmlspecialchars($user['status']); ?></span></p>
                    <p><strong>REPUTATION:</strong> <?php echo htmlspecialchars($user['reputation']); ?></p>
                </div>
                <div class="profile-actions">
                    <a href="edit-profile" class="btn btn-primary">EDIT PROFILE</a>
                    <a href="change-password" class="btn btn-warning">CHANGE PASSWORD</a>
                    <a href="totp-setup" class="btn btn-secondary">MANAGE TFA</a>
                </div>
            </div>

            <!-- User Threads -->
            <div class="content-section" style="flex-grow: 1;">
                <h3 class="section-title">YOUR THREADS</h3>
                <div class="thread-list">
                    <?php foreach ($threads as $thread) { ?>
                        <a href="/thread?id=<?php echo $thread['id'] ?>" class="thread-item">
                            <h4 class="thread-title"><?php echo htmlspecialchars($thread['title']); ?></h4>
                            <div class="thread-content"><?php echo $thread['content']; ?></div>
                            <div class="thread-meta">
                                <span>VIEWS: <?php echo htmlspecialchars($thread['viewsCount']); ?></span>
                                <span>UPVOTES: <?php echo htmlspecialchars($thread['upvoteCount']); ?></span>
                                <span>DOWNVOTES: <?php echo htmlspecialchars($thread['downvoteCount']); ?></span>
                                <span>STATUS: <span class="status-<?php echo strtolower(htmlspecialchars($thread['status'])); ?>"><?php echo htmlspecialchars($thread['status']); ?></span></span>
                            </div>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- User Comments -->
        <div class="content-section">
            <h3 class="section-title">YOUR COMMENTS</h3>
            <div class="comment-list">
                <?php foreach ($comments as $comment) { ?>
                    <div class="comment-item">
                        <div class="comment-content"><?php echo $comment['content']; ?></div>
                        <div class="comment-meta">
                            <span>UPVOTES: <?php echo htmlspecialchars($comment['upvoteCount']); ?></span>
                            <span>DOWNVOTES: <?php echo htmlspecialchars($comment['downvoteCount']); ?></span>
                            <span>STATUS: <span class="status-<?php echo strtolower(htmlspecialchars($comment['status'])); ?>"><?php echo htmlspecialchars($comment['status']); ?></span></span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>