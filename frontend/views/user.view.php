<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⛧ USER PROFILE ⛧</title>
<link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
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
            color: #e8e8e8;
            font-family: 'Courier New', monospace;
            min-height: 100vh;
            padding-top: 80px;
            overflow-y: scroll;
        }

        .main-container {
            width: 95%;
            max-width: 1400px;
            margin: 2rem auto;
            display: flex;
            flex-direction: column;
        }

        .profile-header {
            background: #0a0a0a;
            border-left: 3px solid #960d0d;
            padding: 2rem;
            margin-bottom: 2.5rem;
            display: flex;
            gap: 2.5rem;
            align-items: center;
        }

        .profile-pic {
            width: 160px;
            height: 160px;
            object-fit: cover;
            border: 2px solid #960d0d;
            flex-shrink: 0;
        }

        .profile-info {
            flex: 1;
        }

        .username {
            font-family: 'vamp', sans-serif;
            color: #f03;
            font-size: 2rem;
            letter-spacing: 2px;
            margin-bottom: 1rem;
            text-shadow: 0 0 10px rgba(255, 0, 51, 0.5);
        }

        .stats-row {
            display: flex;
            gap: 2.5rem;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .stat-value {
            color: #f03;
            font-size: 1.1rem;
            font-weight: bold;
        }

        .status-active {
            color: #0f0 !important;
        }

        .status-inactive {
            color: #f00 !important;
        }

        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            flex-shrink: 0;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: 1px solid #960d0d;
            background: #0a0a0a;
            color: #f03;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 0.8rem;
            text-align: center;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .btn:hover {
            background: #960d0d;
            color: #fff;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .content-section {
            display: flex;
            flex-direction: column;
            height: 600px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #960d0d;
        }

        .section-title {
            font-family: 'vamp', sans-serif;
            color: #f03;
            font-size: 1.4rem;
            letter-spacing: 2px;
        }

        .section-count {
            color: #777;
            font-size: 0.9rem;
        }

        .items-list {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-right: 0.5rem;
        }

        .items-list::-webkit-scrollbar {
            width: 8px;
        }

        .items-list::-webkit-scrollbar-track {
            background: #0a0a0a;
        }

        .items-list::-webkit-scrollbar-thumb {
            background: #960d0d;
            border-radius: 4px;
        }

        .items-list::-webkit-scrollbar-thumb:hover {
            background: #f03;
        }

        .item-card {
            background: #0a0a0a;
            padding: 1.3rem;
            border-left: 2px solid #333;
            transition: all 0.2s;
            text-decoration: none;
            display: block;
            flex-shrink: 0;
        }

        .item-card:hover {
            border-left-color: #960d0d;
            background: #111;
            transform: translateX(5px);
        }

        .item-title {
            color: #f03;
            font-size: 1.1rem;
            margin-bottom: 0.7rem;
            font-weight: bold;
        }

        .item-content {
            color: #aaa;
            margin-bottom: 0.8rem;
            line-height: 1.5;
            font-size: 0.95rem;
            word-wrap: break-word;
        }

        .item-meta {
            display: flex;
            gap: 1.2rem;
            font-size: 0.8rem;
            color: #555;
            flex-wrap: wrap;
        }

        .item-meta span {
            color: #777;
        }

        .item-meta .value {
            color: #f03;
            font-weight: bold;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-actions {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

    <div class="main-container">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($user['profilePic']) ?>" class="profile-pic" alt="PROFILE">
            
            <div class="profile-info">
                <div class="username">⛧ <?= htmlspecialchars($user['username']) ?> ⛧</div>
                <div class="stats-row">
                    <div class="stat-item">
                        <span class="stat-label">Email</span>
                        <span class="stat-value" style="font-size:0.85rem;color:#999;"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Reputation</span>
                        <span class="stat-value"><?= htmlspecialchars($user['reputation']) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Status</span>
                        <span class="stat-value status-<?= strtolower(htmlspecialchars($user['status'])) ?>"><?= htmlspecialchars($user['status']) ?></span>
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <!-- <a href="edit-profile" class="btn">Edit Profile (404) </a> -->
                <a href="change-password" class="btn">Change Password</a>
                <a href="profile/settings" class="btn">Profile Settings</a>
                <!-- <a href="admin/setup" class="btn">Manage TFA</a> -->
            </div>
        </div>

        <div class="content-grid">
            <!-- Threads Section -->
            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">YOUR THREADS</h3>
                    <span class="section-count">(<?= count($threads) ?>)</span>
                </div>
                <div class="items-list">
                    <?php foreach ($threads as $thread): ?>
                        <a href="/thread?id=<?= $thread['id'] ?>" class="item-card">
                            <div class="item-title"><?= htmlspecialchars($thread['title']) ?></div>
                            <div class="item-meta">
                                <span>VIEWS: <span class="value"><?= htmlspecialchars($thread['viewsCount']) ?></span></span>
                                <span>UPVOTES: <span class="value"><?= htmlspecialchars($thread['upvoteCount']) ?></span></span>
                                <span>DOWNVOTES: <span class="value"><?= htmlspecialchars($thread['downvoteCount']) ?></span></span>
                                <span>STATUS: <span class="value status-<?= strtolower(htmlspecialchars($thread['status'])) ?>"><?= htmlspecialchars($thread['status']) ?></span></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">YOUR COMMENTS</h3>
                    <span class="section-count">(<?= count($comments) ?>)</span>
                </div>
                <div class="items-list">
                    <?php foreach ($comments as $comment): ?>
                        <a href="/thread/comments?id=<?= $comment['id'] ?>" class="item-card">
                            <div class="item-content"><?= $comment['content'] ?></div>
                            <div class="item-meta">
                                <span>UPVOTES: <span class="value"><?= htmlspecialchars($comment['upvoteCount']) ?></span></span>
                                <span>DOWNVOTES: <span class="value"><?= htmlspecialchars($comment['downvoteCount']) ?></span></span>
                                <span>STATUS: <span class="value status-<?= strtolower(htmlspecialchars($comment['status'])) ?>"><?= htmlspecialchars($comment['status']) ?></span></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
