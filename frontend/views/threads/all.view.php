<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⛧ Forum Categories - Red Skull ⛧</title>
<link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
    <style>
        @font-face {
            font-family: 'vamp';
            src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
        }

        body {
            background: #000;
            color: #fff;
            font-family: 'Courier New', monospace;
            min-height: 100vh;
        }

        .main-container {
            width: 95%;
            max-width: 1400px;
            margin: 1.5rem auto;
        }

        .page-header {
            text-align: left;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #960d0d;
        }

        h1 {
            font-family: 'vamp', sans-serif;
            color: #f03;
            font-size: 2.5rem;
            letter-spacing: 3px;
            text-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
            margin-bottom: 1rem;
        }

        .action-buttons {
            display: flex;
            justify-content: right;
            gap: 1rem;
        }

        .btn-primary {
            background: #960d0d;
            color: #fff;
            padding: 0.6rem 1.5rem;
            border: 2px solid #960d0d;
            text-decoration: none;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .btn-primary:hover {
            background: #c00;
            border-color: #c00;
            box-shadow: 0 0 15px rgba(255, 0, 0, 0.5);
        }

        .btn-secondary {
            background: #111;
            color: #fff;
            padding: 0.6rem 1.5rem;
            border: 2px solid #333;
            text-decoration: none;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .btn-secondary:hover {
            border-color: #960d0d;
            color: #f03;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
        }

        /* Category Section */
        .category-section {
            margin-bottom: 2rem;
            border: 1px solid #333;
            background: #0a0a0a;
        }

        .category-header {
            background: #111;
            border-bottom: 2px solid #960d0d;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-title {
            color: #f03;
            font-size: 1.5rem;
            text-transform: uppercase;
            font-weight: bold;
        }

        .category-stats {
            color: #f2f2f2;
            font-size: 0.9rem;
        }

        .category-description {
            color: #aaa;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        /* Thread Table */
        .thread-table {
            width: 100%;
            border-collapse: collapse;
        }

        .thread-table thead {
            background: #0f0f0f;
            border-bottom: 1px solid #960d0d;
        }

        .thread-table th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #f03;
            letter-spacing: 1px;
            border-right: 1px solid #1a1a1a;
        }

        .thread-table th:last-child {
            border-right: none;
        }

        .thread-table tbody tr {
            border-bottom: 1px solid #1a1a1a;
            transition: all 0.3s;
        }

        .thread-table tbody tr:hover {
            background: #111;
            box-shadow: inset 3px 0 0 #960d0d;
        }

        .thread-table td {
            padding: 1rem;
            font-size: 0.9rem;
            border-right: 1px solid #1a1a1a;
        }

        .thread-table td:last-child {
            border-right: none;
        }

        .thread-title-cell {
            width: 50%;
        }

        .thread-title {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
            display: block;
        }

        .thread-title:hover {
            color: #f03;
            text-shadow: 0 0 5px rgba(255, 0, 0, 0.5);
        }

        .thread-status {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 2px;
            margin-left: 0.5rem;
            text-transform: uppercase;
        }

        .status-pinned {
            background: #960d0d;
            color: #fff;
        }

        .status-locked {
            background: #333;
            color: #aaa;
        }

        .status-archived {
            background: #1a1a1a;
            color: #666;
        }

        .thread-author {
            color: #aaa;
            font-size: 0.85rem;
        }

        .thread-author a {
            color: #f03;
            text-decoration: none;
        }

        .thread-author a:hover {
            text-decoration: underline;
        }

        .thread-stats {
            text-align: center;
            color: #666;
        }

        .stat-number {
            color: #f03;
            font-weight: bold;
        }

        .thread-date {
            color: #f2f2f2;
            font-size: 0.85rem;
            text-align: center;
        }

        /* Empty state */
        .empty-category {
            padding: 2rem;
            text-align: center;
            color: #666;
            font-style: italic;
        }

        /* Pagination */
        .pagination-container {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #960d0d;
        }

        .pagination {
            display: inline-flex;
            gap: 0.75rem;
            align-items: center;
        }

        .page-btn {
            background: #111;
            color: #fff;
            padding: 0.5rem 1rem;
            border: 1px solid #333;
            text-decoration: none;
            transition: all 0.3s;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .page-btn:hover {
            background: #960d0d;
            border-color: #960d0d;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
        }

        .page-btn.disabled {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }

        .page-display {
            color: #aaa;
            padding: 0.5rem 1rem;
            border: 1px solid #333;
            background: #0a0a0a;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

    <div class="main-container">
        <div class="page-header">
            <h1>⛧ ALL THREADS ⛧</h1>
            <div class="action-buttons">
                <a href="/threads/new" class="btn-primary">+ CREATE THREAD</a>
                <!-- <a href="/thread/random" class="btn-secondary">⛧ RANDOM THREAD</a> -->
            </div>
        </div>

        <?php foreach ($categories as $category): ?>
            <div class="category-section">
                <div class="category-header">
                    <div>
                        <div class="category-title">⛧ Category: <?php echo htmlspecialchars($category['name']); ?></div>
                        <?php if ($category['description']): ?>
                            <div class="category-description"><?php echo htmlspecialchars($category['description']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="category-stats">
                        <?php echo $category['threadCount']; ?> Threads
                    </div>
                </div>

                <?php if (isset($threadsByCategory[$category['id']]) && !empty($threadsByCategory[$category['id']])): ?>
                    <table class="thread-table">
                        <thead>
                            <tr>
                                <th class="thread-title-cell">Thread</th>
                                <th>Author</th>
                                <th style="text-align: center;">Replies</th>
                                <!-- <th style="text-align: center;">Views</th> -->
                                <th style="text-align: center;">Votes</th>
                                <th style="text-align: center;">Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($threadsByCategory[$category['id']] as $thread): ?>
                                <tr>
                                    <td class="thread-title-cell">
                                        <a href="/thread?id=<?php echo htmlspecialchars($thread['id']); ?>" class="thread-title">
                                            <?php echo htmlspecialchars($thread['title']); ?>
                                        </a>
                                        <?php if ($thread['status'] === 'pinned'): ?>
                                            <span class="thread-status status-pinned">Pinned</span>
                                        <?php endif; ?>
                                        <?php if ($thread['locked']): ?>
                                            <span class="thread-status status-locked">Locked</span>
                                        <?php endif; ?>
                                        <?php if ($thread['status'] === 'archived'): ?>
                                            <span class="thread-status status-archived">Archived</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="thread-author">
                                        <a href="/profile?u=<?= urlencode($thread['username']) ?>">
                                            <?= htmlspecialchars($thread['username']) ?>
                                        </a>
                                    </td>
                                    <td class="thread-stats">
                                        <span class="stat-number"><?php echo $thread['commentCount']; ?></span>
                                    </td>
                                    <!-- <td class="thread-stats">
                                        <span class="stat-number"><?php echo $thread['viewsCount']; ?></span>
                                    </td> -->
                                    <td class="thread-stats">
                                        <span class="stat-number">
                                            <?php echo $thread['upvoteCount'] - $thread['downvoteCount']; ?>
                                        </span>
                                    </td>
                                    <td class="thread-date">
                                        <?php echo date('M d, Y', strtotime($thread['createdAt'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-category">
                        No threads in this category yet. Be the first to create one!
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($uncategorized)): ?>
            <div class="category-section">
                <div class="category-header">
                    <div class="category-title">⛧ UNCATEGORIZED</div>
                </div>
                <table class="thread-table">
                    <thead>
                        <tr>
                            <th class="thread-title-cell">Thread</th>
                            <th>Author</th>
                            <th style="text-align: center;">Replies</th>
                            <th style="text-align: center;">Views</th>
                            <th style="text-align: center;">Votes</th>
                            <th style="text-align: center;">Last Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uncategorized as $thread): ?>
                            <tr>
                                <td class="thread-title-cell">
                                    <a href="/thread?id=<?php echo htmlspecialchars($thread['id']); ?>" class="thread-title">
                                        <?php echo htmlspecialchars($thread['title']); ?>
                                    </a>
                                </td>
                                <td class="thread-author">
                                    <a href="/user/profile?id=<?php echo htmlspecialchars($thread['userId']); ?>">
                                        <?php echo htmlspecialchars($thread['username']); ?>
                                    </a>
                                </td>
                                <td class="thread-stats">
                                    <span class="stat-number"><?php echo $thread['commentCount']; ?></span>
                                </td>
                                <!-- <td class="thread-stats">
                                    <span class="stat-number"><?php echo $thread['viewsCount']; ?></span>
                                </td> -->
                                <td class="thread-stats">
                                    <span class="stat-number">
                                        <?php echo $thread['upvoteCount'] - $thread['downvoteCount']; ?>
                                    </span>
                                </td>
                                <td class="thread-date">
                                    <?php echo date('M d, Y', strtotime($thread['createdAt'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <div class="pagination-container">
            <nav class="pagination" aria-label="Thread pagination">
                <?php if ($currentPage > 1): ?>
                    <a class="page-btn" href="?page=<?php echo $currentPage - 1; ?>">« PREVIOUS</a>
                <?php else: ?>
                    <span class="page-btn disabled">« PREVIOUS</span>
                <?php endif; ?>

                <span class="page-display">
                    Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
                </span>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="page-btn" href="?page=<?php echo $currentPage + 1; ?>">NEXT »</a>
                <?php else: ?>
                    <span class="page-btn disabled">NEXT »</span>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</body>

</html>