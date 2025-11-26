<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⛧ Notification Settings ⛧</title>
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
            color: #f8f8f8;
            font-family: 'Courier New', monospace;
            min-height: 100vh;
            padding-top: 80px;
        }

        .settings-wrapper {
            max-width: 800px;
            margin: 2.5rem auto;
            padding: 0 1rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-family: 'vamp', sans-serif;
            color: #f03;
            font-size: 2.2rem;
            letter-spacing: 3px;
            text-shadow: 0 0 15px rgba(255, 0, 51, 0.6);
            margin-bottom: 0.5rem;
        }

        .page-description {
            color: #999;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 3px solid;
            background: #0a0a0a;
        }

        .alert-success {
            border-color: #0f0;
            color: #0f0;
        }

        .alert-error {
            border-color: #f03;
            color: #f03;
        }

        .settings-form {
            background: #0a0a0a;
            border: 1px solid #333;
            padding: 2rem;
        }

        .setting-item {
            padding: 1.5rem 0;
            border-bottom: 1px solid #222;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
        }

        .setting-item:hover {
            background: #111;
            padding-left: 1rem;
            padding-right: 1rem;
            margin-left: -1rem;
            margin-right: -1rem;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info {
            flex: 1;
        }

        .setting-label {
            color: #f03;
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
            display: block;
        }

        .setting-description {
            color: #777;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #333;
            transition: 0.3s;
            border: 2px solid #555;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: #999;
            transition: 0.3s;
        }

        input:checked+.toggle-slider {
            background-color: #960d0d;
            border-color: #f03;
        }

        input:checked+.toggle-slider:before {
            transform: translateX(30px);
            background-color: #f03;
            box-shadow: 0 0 10px rgba(255, 0, 51, 0.6);
        }

        input:disabled+.toggle-slider {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .form-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: 2px solid;
            background: transparent;
            color: #f03;
            border-color: #960d0d;
            font-family: 'courier new', monospace;
            font-size: 0.9rem;
            font-weight: bold;
            cursor: pointer;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #960d0d;
            color: #fff;
            box-shadow: 0 0 20px rgba(150, 13, 13, 0.5);
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            border-color: #555;
            color: #999;
        }

        .btn-secondary:hover {
            background: #333;
            color: #fff;
            box-shadow: none;
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
            background: #333;
        }

        .status-indicator.active {
            background: #0f0;
            box-shadow: 0 0 8px rgba(0, 255, 0, 0.6);
        }
    </style>
</head>

<body>
    <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

    <div class="settings-wrapper">
        <div class="page-header">
            <h2 class="page-title">⛧ Notification Settings ⛧</h2>
            <p class="page-description">
                Control which notifications you want to receive. Changes are saved automatically.
            </p>
        </div>

        <?php if (isset($_SESSION['flash']['success'])): ?>
            <div class="alert alert-success">
                ✓ <?= htmlspecialchars($_SESSION['flash']['success']) ?>
            </div>
            <?php unset($_SESSION['flash']['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash']['error'])): ?>
            <div class="alert alert-error">
                ✗ <?= htmlspecialchars($_SESSION['flash']['error']) ?>
            </div>
            <?php unset($_SESSION['flash']['error']); ?>
        <?php endif; ?>

        <form method="POST" action="/notifications/settings" class="settings-form">
            <div class="setting-item">
                <div class="setting-info">
                    <label class="setting-label">
                        <span class="status-indicator <?= $settings['thread_comment'] ? 'active' : '' ?>"></span>
                        Comments on Your Threads
                    </label>
                    <span class="setting-description">
                        Get notified when someone comments on your threads
                    </span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="thread_comment"
                        data-setting="thread_comment"
                        <?= $settings['thread_comment'] ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <label class="setting-label">
                        <span class="status-indicator <?= $settings['comment_reply'] ? 'active' : '' ?>"></span>
                        Replies to Your Comments
                    </label>
                    <span class="setting-description">
                        Get notified when someone replies to your comments
                    </span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="comment_reply"
                        data-setting="comment_reply"
                        <?= $settings['comment_reply'] ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <label class="setting-label">
                        <span class="status-indicator <?= $settings['comment_vote'] ? 'active' : '' ?>"></span>
                        Votes on Your Comments
                    </label>
                    <span class="setting-description">
                        Get notified when someone upvotes your comments
                    </span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="comment_vote"
                        data-setting="comment_vote"
                        <?= $settings['comment_vote'] ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <label class="setting-label">
                        <span class="status-indicator <?= $settings['new_thread'] ? 'active' : '' ?>"></span>
                        New Threads
                    </label>
                    <span class="setting-description">
                        Get notified when new threads are posted (can be noisy)
                    </span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="new_thread"
                        data-setting="new_thread"
                        <?= $settings['new_thread'] ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <label class="setting-label">
                        <span class="status-indicator <?= $settings['mention'] ? 'active' : '' ?>"></span>
                        Mentions
                    </label>
                    <span class="setting-description">
                        Get notified when someone mentions you with @username
                    </span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="mention"
                        data-setting="mention"
                        <?= $settings['mention'] ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <label class="setting-label">
                        <span class="status-indicator <?= $settings['system'] ? 'active' : '' ?>"></span>
                        System Notifications
                    </label>
                    <span class="setting-description">
                        Get notified about important system announcements and updates
                    </span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="system"
                        data-setting="system"
                        <?= $settings['system'] ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Save All Settings</button>
                <a href="/notifications" class="btn btn-secondary">Back to Notifications</a>
            </div>
        </form>
    </div>

    <script>
        // Enable instant toggle updates via AJAX
        document.addEventListener('DOMContentLoaded', function() {
            const toggles = document.querySelectorAll('input[type="checkbox"][data-setting]');

            toggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const setting = this.dataset.setting;
                    const enabled = this.checked;
                    const statusIndicator = this.closest('.setting-item').querySelector('.status-indicator');

                    // Disable toggle while updating
                    this.disabled = true;

                    fetch('/notifications/settings', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                setting: setting,
                                enabled: enabled
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update status indicator
                                if (enabled) {
                                    statusIndicator.classList.add('active');
                                } else {
                                    statusIndicator.classList.remove('active');
                                }
                                console.log('✓ Setting updated:', setting);
                            } else {
                                // Revert toggle if update failed
                                this.checked = !enabled;
                                alert('Failed to update setting: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Revert toggle if request failed
                            this.checked = !enabled;
                            alert('An error occurred while updating the setting');
                        })
                        .finally(() => {
                            this.disabled = false;
                        });
                });
            });
        });
    </script>
</body>

</html>