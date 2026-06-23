<?php
/**
 * Blocked Users List Page
 * Display and manage blocked users
 *
 * Expected variables:
 * - $blockedUsers (array): list of blocked users
 * - $totalBlocked (int): total count of blocked users
 */

use Backend\Utils\MessageFeatures;

$currentUserId = $_SESSION['userId'] ?? null;
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocked Users - Deception Forum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .header {
            background: #121212;
            border-bottom: 2px solid #960d0d;
            padding: 1.5em;
            margin-bottom: 2em;
        }

        .page-title {
            color: #f03;
            font-size: 1.8em;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 0;
        }

        .container {
            max-width: 900px;
            margin: auto;
        }

        .blocked-users-list {
            list-style: none;
            padding: 0;
        }

        .blocked-user-item {
            background: #121212;
            border-left: 2.3px solid #960d0d;
            padding: 1.2em;
            margin-bottom: 1em;
            border-radius: 3px;
            display: flex;
            align-items: center;
            gap: 1.5em;
            transition: all 0.2s;
        }

        .blocked-user-item:hover {
            background: #1a1a1a;
            box-shadow: 0 2px 8px rgba(255, 0, 51, 0.2);
        }

        .blocked-user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #2a2a2a;
            border: 2px solid #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            flex-shrink: 0;
        }

        .blocked-user-info {
            flex: 1;
        }

        .blocked-user-name {
            color: #ffd700;
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 0.3em;
        }

        .blocked-user-username {
            color: #aaa;
            font-size: 0.9em;
            margin-bottom: 0.2em;
        }

        .blocked-user-reason {
            color: #777;
            font-size: 0.85em;
            font-style: italic;
            margin-top: 0.4em;
        }

        .blocked-user-time {
            color: #666;
            font-size: 0.8em;
            margin-top: 0.3em;
        }

        .unblock-btn {
            background: #121212;
            border: 1.5px solid #0a0;
            color: #0a0;
            padding: 0.5em 1.2em;
            font-size: 0.9em;
            font-weight: bold;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.15s;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            flex-shrink: 0;
        }

        .unblock-btn:hover {
            background: #0a0;
            color: #000;
        }

        .empty-state {
            text-align: center;
            padding: 3em 1.5em;
            color: #666;
        }

        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 0.5em;
            opacity: 0.5;
        }

        .empty-state-text {
            font-size: 1.1em;
        }

        .alert {
            border-radius: 3px;
            padding: 1em;
            margin-bottom: 1.5em;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert.success {
            background: #1a3a1a;
            color: #0a0;
            border-left: 3px solid #0a0;
        }

        .alert .close-btn {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.2em;
            float: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="page-title">🚫 Blocked Users</h1>
        <p style="color: #aaa; margin: 0.5em 0 0 0; font-size: 0.95em;">
            Manage users you have blocked. Blocked users' messages and activity are hidden from your view.
        </p>
    </div>

    <div class="container">
        <!-- Alert Messages -->
        <div id="alert-container"></div>

        <!-- Blocked Users Count -->
        <div style="margin-bottom: 1.5em; padding: 0.8em; background: #1a1a1a; border-left: 2px solid #ffd700; border-radius: 3px;">
            <span style="color: #ffd700; font-weight: bold;">
                <?php if ($totalBlocked > 0): ?>
                    You have blocked <?= htmlspecialchars($totalBlocked) ?> user<?= $totalBlocked != 1 ? 's' : '' ?>
                <?php else: ?>
                    You haven't blocked any users
                <?php endif; ?>
            </span>
        </div>

        <!-- Blocked Users List -->
        <?php if (count($blockedUsers) > 0): ?>
            <ul class="blocked-users-list">
                <?php foreach ($blockedUsers as $blockedUser): ?>
                    <li class="blocked-user-item" data-user-id="<?= htmlspecialchars($blockedUser['id']) ?>">
                        <!-- Avatar -->
                        <div class="blocked-user-avatar">
                            <?php if (!empty($blockedUser['profilePic'])): ?>
                                <img src="<?= htmlspecialchars($blockedUser['profilePic']) ?>"
                                     alt="<?= htmlspecialchars($blockedUser['username']) ?>"
                                     style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                👤
                            <?php endif; ?>
                        </div>

                        <!-- User Info -->
                        <div class="blocked-user-info">
                            <div class="blocked-user-name">
                                <?= htmlspecialchars($blockedUser['name'] ?? $blockedUser['username'] ?? 'Unknown') ?>
                            </div>
                            <div class="blocked-user-username">
                                @<?= htmlspecialchars($blockedUser['username']) ?>
                            </div>
                            <?php if (!empty($blockedUser['reason'])): ?>
                                <div class="blocked-user-reason">
                                    Reason: <?= htmlspecialchars($blockedUser['reason']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="blocked-user-time">
                                Blocked on <?= htmlspecialchars(date('M d, Y H:i', strtotime($blockedUser['createdAt']))) ?>
                            </div>
                        </div>

                        <!-- Unblock Button -->
                        <button class="unblock-btn"
                                data-user-id="<?= htmlspecialchars($blockedUser['id']) ?>"
                                onclick="unblockUser(this, '<?= htmlspecialchars($blockedUser['id']) ?>')">
                            ✓ Unblock
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">✨</div>
                <div class="empty-state-text">You haven't blocked any users yet</div>
                <p style="color: #555; font-size: 0.95em; margin-top: 0.5em;">
                    Block users by clicking the 🚫 Block button on their profile.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="/public/javascripts/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@2.3.10/dist/purify.min.js"></script>
    <script src="/public/javascripts/message-features.js"></script>

    <script>
        function unblockUser(btn, userId) {
            if (!confirm('Are you sure you want to unblock this user?')) return;

            window.messageFeatures.unblockUser(userId).then(() => {
                // Remove from list
                const item = btn.closest('.blocked-user-item');
                item.style.animation = 'slideDown 0.3s ease-out reverse';
                setTimeout(() => item.remove(), 300);

                // Check if list is now empty
                const list = document.querySelector('.blocked-users-list');
                if (list && list.children.length === 0) {
                    location.reload();
                }

                // Show success message
                showAlert('User unblocked successfully', 'success');
            });
        }

        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = 'alert ' + type;
            alert.innerHTML = message + ' <button type="button" class="close-btn" onclick="this.parentElement.remove()">×</button>';
            document.getElementById('alert-container').appendChild(alert);

            setTimeout(() => alert.remove(), 5000);
        }

        // Add CSRF token support to messageFeatures
        window.addEventListener('load', function() {
            if (window.messageFeatures) {
                const originalGetCsrfToken = window.messageFeatures.getCsrfToken;
                window.messageFeatures.getCsrfToken = function() {
                    return '<?= htmlspecialchars($csrfToken) ?>' || originalGetCsrfToken.call(this);
                };
            }
        });
    </script>
</body>
</html>
