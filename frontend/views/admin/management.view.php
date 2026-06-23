<?php
/**
 * Admin Management Panel
 * User suspension, post hiding, audit logs
 */

$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Deception Forum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .admin-container {
            max-width: 1100px;
            margin: 2em auto;
            padding: 0 1em;
        }

        .admin-header {
            background: #121212;
            border: 1px solid #960d0d;
            padding: 1.5em;
            margin-bottom: 2em;
            border-radius: 4px;
        }

        .admin-title {
            color: #f03;
            font-size: 1.8em;
            font-weight: bold;
            margin: 0;
        }

        .admin-tabs {
            display: flex;
            gap: 0.5em;
            margin-bottom: 2em;
            border-bottom: 1px solid #333;
            flex-wrap: wrap;
        }

        .admin-tabs button {
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            color: #aaa;
            padding: 0.8em 1.2em;
            cursor: pointer;
            transition: all 0.2s;
        }

        .admin-tabs button.active {
            color: #f03;
            border-bottom-color: #f03;
        }

        .admin-panel {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .admin-panel.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .admin-section {
            background: #121212;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 1.5em;
            margin-bottom: 1.5em;
        }

        .admin-section h3 {
            color: #f03;
            font-weight: bold;
            margin-bottom: 1em;
            border-bottom: 1px solid #333;
            padding-bottom: 0.5em;
        }

        .form-group {
            margin-bottom: 1em;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.3em;
            color: #ccc;
            font-weight: 500;
        }

        .form-group input, .form-group select, .form-group textarea {
            background: #1a1a1a;
            border: 1px solid #333;
            color: #fff;
            padding: 0.6em;
            border-radius: 3px;
            width: 100%;
            font-family: inherit;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #f03;
            box-shadow: 0 0 0 2px rgba(240, 51, 51, 0.2);
        }

        .btn-admin {
            background: #121212;
            border: 1.5px solid #f03;
            color: #f03;
            padding: 0.6em 1.2em;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.15s;
            font-weight: bold;
        }

        .btn-admin:hover {
            background: #f03;
            color: #fff;
        }

        .user-row, .post-row {
            background: #1a1a1a;
            border-left: 3px solid #f03;
            padding: 1em;
            margin-bottom: 0.8em;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1em;
        }

        .user-info, .post-info {
            flex: 1;
        }

        .user-name {
            color: #ffd700;
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 0.2em;
        }

        .user-status {
            color: #aaa;
            font-size: 0.9em;
        }

        .status-suspended {
            color: #f03;
            font-weight: bold;
        }

        .status-active {
            color: #0a0;
            font-weight: bold;
        }

        .alert-admin {
            background: #1a3a1a;
            border-left: 3px solid #0a0;
            color: #0a0;
            padding: 1em;
            border-radius: 3px;
            margin-bottom: 1em;
            animation: slideDown 0.3s ease-out;
        }

        .alert-admin.error {
            background: #3a1a1a;
            border-left-color: #f03;
            color: #f03;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .search-box {
            margin-bottom: 1.5em;
        }

        .search-box input {
            background: #1a1a1a;
            border: 1px solid #333;
            color: #fff;
            padding: 0.8em;
            border-radius: 3px;
            width: 100%;
        }

        .search-box input:focus {
            outline: none;
            border-color: #f03;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1 class="admin-title">🛡️ Admin Management Panel</h1>
            <p style="color: #aaa; margin: 0.5em 0 0 0;">
                Moderate users, manage posts, and review audit logs
            </p>
        </div>

        <!-- Tab Navigation -->
        <div class="admin-tabs">
            <button class="admin-tab-btn active" data-panel="suspend">👤 Suspend Users</button>
            <button class="admin-tab-btn" data-panel="hide-posts">🚫 Hide Posts</button>
            <button class="admin-tab-btn" data-panel="audit-logs">📋 Audit Logs</button>
        </div>

        <!-- Alert Container -->
        <div id="admin-alert-container"></div>

        <!-- ==================== SUSPEND USERS PANEL ==================== -->
        <div id="suspend-panel" class="admin-panel active">
            <div class="admin-section">
                <h3>Suspend User</h3>
                <form id="suspend-form">
                    <div class="form-group">
                        <label>Username or Email</label>
                        <input type="text" name="search" placeholder="Search for user..." id="user-search" required>
                    </div>

                    <div id="user-search-results" style="margin-bottom: 1em;"></div>

                    <div class="form-group" style="display: none;" id="reason-group">
                        <label>Suspension Reason</label>
                        <textarea name="reason" placeholder="Why are you suspending this user?" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn-admin" style="display: none;" id="suspend-btn">
                        🔒 Suspend User
                    </button>

                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="userId" id="selected-user-id">
                </form>
            </div>

            <div class="admin-section">
                <h3>Suspended Users</h3>
                <div id="suspended-users-list"></div>
            </div>
        </div>

        <!-- ==================== HIDE POSTS PANEL ==================== -->
        <div id="hide-posts-panel" class="admin-panel">
            <div class="admin-section">
                <h3>Hide Post</h3>
                <form id="hide-post-form">
                    <div class="form-group">
                        <label>Post Type</label>
                        <select name="postType" required>
                            <option value="">Select type...</option>
                            <option value="thread">Thread</option>
                            <option value="comment">Comment</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Post ID</label>
                        <input type="text" name="postId" placeholder="Enter post ID" required>
                    </div>

                    <div class="form-group">
                        <label>Reason for Hiding</label>
                        <textarea name="reason" placeholder="Why are you hiding this post?" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn-admin">🚫 Hide Post</button>

                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                </form>
            </div>

            <div class="admin-section">
                <h3>Recently Hidden Posts</h3>
                <div id="hidden-posts-list"></div>
            </div>
        </div>

        <!-- ==================== AUDIT LOGS PANEL ==================== -->
        <div id="audit-logs-panel" class="admin-panel">
            <div class="admin-section">
                <h3>Audit Logs</h3>
                <div class="search-box">
                    <input type="text" placeholder="Filter logs..." id="audit-search">
                </div>
                <div id="audit-logs-list"></div>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alert-container" style="position: fixed; top: 1em; right: 1em; z-index: 1000; max-width: 400px;"></div>

    <!-- Scripts -->
    <script src="/public/javascripts/jquery-3.7.1.min.js"></script>
    <script src="/public/javascripts/admin-management.js"></script>

    <script>
        const csrfToken = '<?= htmlspecialchars($csrfToken) ?>';

        // Tab switching
        document.querySelectorAll('.admin-tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.admin-panel').forEach(p => p.classList.remove('active'));

                this.classList.add('active');
                const panelId = this.dataset.panel + '-panel';
                document.getElementById(panelId).classList.add('active');
            });
        });

        function showAdminAlert(message, type = 'success') {
            const alert = document.createElement('div');
            alert.className = `alert-admin ${type === 'success' ? '' : 'error'}`;
            alert.innerHTML = `
                <strong>${type === 'success' ? '✓' : '✕'}</strong> ${message}
                <button type="button" style="float: right; background: none; border: none; color: inherit; cursor: pointer; font-size: 1.2em;" onclick="this.parentElement.remove()">×</button>
            `;
            document.getElementById('admin-alert-container').appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }
    </script>
</body>
</html>
