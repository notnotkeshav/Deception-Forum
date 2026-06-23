<?php
/**
 * Advanced Settings Page
 * Themes, Keyboard Shortcuts, Plugins, Draft Management
 */

$userId = $_SESSION['userId'] ?? null;
$accessLevel = $_SESSION['accessLevel'] ?? 0;
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Settings - Deception Forum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .settings-container {
            max-width: 1000px;
            margin: 2em auto;
            padding: 0 1em;
        }

        .settings-nav {
            display: flex;
            gap: 0.5em;
            margin-bottom: 2em;
            border-bottom: 1px solid #333;
            flex-wrap: wrap;
        }

        .settings-nav button {
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            color: #aaa;
            padding: 0.8em 1.2em;
            cursor: pointer;
            transition: all 0.2s;
        }

        .settings-nav button.active {
            color: #ffd700;
            border-bottom-color: #ffd700;
        }

        .settings-nav button:hover {
            color: #fff;
        }

        .settings-panel {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .settings-panel.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .settings-section {
            background: #121212;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 1.5em;
            margin-bottom: 1.5em;
        }

        .settings-section h3 {
            color: #ffd700;
            font-weight: bold;
            margin-bottom: 1em;
            border-bottom: 1px solid #333;
            padding-bottom: 0.5em;
        }

        .settings-section label {
            display: block;
            margin-top: 1em;
            margin-bottom: 0.3em;
            color: #ccc;
            font-weight: 500;
        }

        .settings-section input, .settings-section select, .settings-section textarea {
            background: #1a1a1a;
            border: 1px solid #333;
            color: #fff;
            padding: 0.6em;
            border-radius: 3px;
            width: 100%;
            font-family: inherit;
        }

        .settings-section input:focus, .settings-section select:focus, .settings-section textarea:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }

        .color-picker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 0.5em;
            margin-top: 0.5em;
        }

        .color-swatch {
            width: 50px;
            height: 50px;
            border: 2px solid #333;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .color-swatch:hover {
            transform: scale(1.1);
            border-color: #ffd700;
        }

        .color-swatch.selected {
            border-color: #ffd700;
            box-shadow: 0 0 0 2px #ffd700;
        }

        .shortcut-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 1em;
            align-items: center;
            padding: 0.8em;
            background: #1a1a1a;
            border-radius: 3px;
            margin-bottom: 0.5em;
        }

        .plugin-card {
            background: #1a1a1a;
            border-left: 3px solid #9b59b6;
            padding: 1em;
            margin-bottom: 1em;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1em;
        }

        .plugin-info {
            flex: 1;
        }

        .plugin-name {
            color: #ffd700;
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 0.2em;
        }

        .plugin-author {
            color: #aaa;
            font-size: 0.9em;
        }

        .plugin-description {
            color: #777;
            font-size: 0.9em;
            margin-top: 0.3em;
        }

        .btn-custom {
            background: #121212;
            border: 1.5px solid #ffd700;
            color: #ffd700;
            padding: 0.6em 1.2em;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.15s;
            font-weight: bold;
        }

        .btn-custom:hover {
            background: #ffd700;
            color: #000;
        }

        .btn-danger {
            border-color: #f03;
            color: #f03;
        }

        .btn-danger:hover {
            background: #f03;
            color: #fff;
        }

        .btn-success {
            border-color: #0a0;
            color: #0a0;
        }

        .btn-success:hover {
            background: #0a0;
            color: #000;
        }

        .alert-custom {
            background: #1a3a1a;
            border-left: 3px solid #0a0;
            color: #0a0;
            padding: 1em;
            border-radius: 3px;
            margin-bottom: 1em;
            animation: slideDown 0.3s ease-out;
        }

        .alert-custom.error {
            background: #3a1a1a;
            border-left-color: #f03;
            color: #f03;
        }

        .restricted-badge {
            display: inline-block;
            background: #960d0d;
            color: #fff;
            padding: 0.2em 0.5em;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 0.5em;
        }

        .draft-item {
            background: #1a1a1a;
            border: 1px solid #333;
            padding: 1em;
            margin-bottom: 0.5em;
            border-radius: 3px;
        }

        .draft-timestamp {
            color: #777;
            font-size: 0.9em;
        }

        .draft-preview {
            color: #aaa;
            margin-top: 0.3em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <h1 style="color: #ffd700; margin-bottom: 1.5em;">⚙️ Advanced Settings</h1>

        <!-- Tab Navigation -->
        <div class="settings-nav">
            <button class="nav-btn active" data-panel="themes">🎨 Themes</button>
            <button class="nav-btn" data-panel="shortcuts">⌨️ Keyboard Shortcuts</button>
            <button class="nav-btn" data-panel="plugins">🔌 Plugins</button>
            <button class="nav-btn" data-panel="drafts">📝 Drafts</button>
        </div>

        <!-- ==================== THEMES PANEL ==================== -->
        <div id="themes-panel" class="settings-panel active">
            <?php if ($accessLevel < 4): ?>
                <div class="settings-section">
                    <p style="color: #f03;">
                        <strong>Level 4+ Required</strong>
                        <br>Custom themes are available for users with access level 4 and above.
                    </p>
                </div>
            <?php else: ?>
                <div class="settings-section">
                    <h3>Create Custom Theme</h3>
                    <form id="create-theme-form">
                        <label>Theme Name</label>
                        <input type="text" name="name" placeholder="e.g., Midnight Blue" required>

                        <label>Description (optional)</label>
                        <textarea name="description" placeholder="Describe your theme..." rows="2"></textarea>

                        <label>Primary Color</label>
                        <input type="color" id="primary-color" value="#ffd700">

                        <label>Secondary Color</label>
                        <input type="color" id="secondary-color" value="#121212">

                        <label>Accent Color</label>
                        <input type="color" id="accent-color" value="#9b59b6">

                        <label>Text Color</label>
                        <input type="color" id="text-color" value="#ffffff">

                        <button type="submit" class="btn-custom" style="margin-top: 1em; width: 100%;">
                            ✓ Create Theme
                        </button>

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    </form>
                </div>

                <div class="settings-section">
                    <h3>Your Themes</h3>
                    <div id="user-themes-list"></div>
                </div>

                <div class="settings-section">
                    <h3>Custom CSS (Advanced)</h3>
                    <label>Custom CSS Code</label>
                    <textarea id="custom-css" placeholder=":root { --custom: value; }" rows="5" style="font-family: monospace;"></textarea>
                    <label style="margin-top: 1em;">
                        <input type="checkbox" id="enable-custom-css"> Enable Custom CSS
                    </label>
                    <button type="button" class="btn-custom" style="width: 100%; margin-top: 1em;" onclick="saveCustomCSS()">
                        💾 Save Custom CSS
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==================== SHORTCUTS PANEL ==================== -->
        <div id="shortcuts-panel" class="settings-panel">
            <div class="settings-section">
                <h3>Keyboard Shortcuts</h3>
                <p style="color: #aaa; margin-bottom: 1em;">
                    Customize keyboard shortcuts for common actions. Press <code style="background: #1a1a1a; padding: 0.2em 0.4em;">?</code> to show help.
                </p>
                <div id="shortcuts-list"></div>
                <button type="button" class="btn-custom" style="width: 100%; margin-top: 1em;" onclick="resetShortcuts()">
                    🔄 Reset to Default
                </button>
            </div>
        </div>

        <!-- ==================== PLUGINS PANEL ==================== -->
        <div id="plugins-panel" class="settings-panel">
            <div class="settings-section">
                <h3>Installed Plugins</h3>
                <div id="installed-plugins-list"></div>
                <p style="color: #aaa; text-align: center; padding: 1em 0;" id="no-plugins">
                    No plugins installed yet.
                </p>
            </div>

            <div class="settings-section">
                <h3>Plugin Marketplace</h3>
                <div id="marketplace-plugins-list"></div>
            </div>
        </div>

        <!-- ==================== DRAFTS PANEL ==================== -->
        <div id="drafts-panel" class="settings-panel">
            <div class="settings-section">
                <h3>Auto-Saved Drafts</h3>
                <p style="color: #aaa; margin-bottom: 1em;">
                    Your drafts are automatically saved every 30 seconds while you type. They expire after 30 days.
                </p>
                <div id="drafts-list"></div>
                <p style="color: #aaa; text-align: center; padding: 1em 0;" id="no-drafts">
                    No saved drafts.
                </p>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alert-container" style="position: fixed; top: 1em; right: 1em; z-index: 1000; max-width: 400px;"></div>

    <!-- Scripts -->
    <script src="/public/javascripts/jquery-3.7.1.min.js"></script>
    <script src="/public/javascripts/keyboard-shortcuts.js"></script>
    <script src="/public/javascripts/theme-system.js"></script>
    <script src="/public/javascripts/plugins-system.js"></script>
    <script src="/public/javascripts/draft-system.js"></script>

    <script>
        const csrfToken = '<?= htmlspecialchars($csrfToken) ?>';
        const accessLevel = <?= $accessLevel ?>;

        // Tab switching
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));

                this.classList.add('active');
                const panelId = this.dataset.panel + '-panel';
                document.getElementById(panelId).classList.add('active');

                // Load data when panel opens
                if (this.dataset.panel === 'themes') loadThemes();
                if (this.dataset.panel === 'shortcuts') loadShortcuts();
                if (this.dataset.panel === 'plugins') loadPlugins();
                if (this.dataset.panel === 'drafts') loadDrafts();
            });
        });

        // Load initial data for first panel
        document.addEventListener('DOMContentLoaded', () => {
            if (accessLevel >= 4) loadThemes();
            loadShortcuts();
            loadPlugins();
        });

        function showAlert(message, type = 'success') {
            const alert = document.createElement('div');
            alert.className = `alert-custom ${type === 'success' ? '' : 'error'}`;
            alert.innerHTML = `
                <strong>${type === 'success' ? '✓' : '✕'}</strong> ${message}
                <button type="button" style="float: right; background: none; border: none; color: inherit; cursor: pointer; font-size: 1.2em;" onclick="this.parentElement.remove()">×</button>
            `;
            document.getElementById('alert-container').appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }
    </script>
</body>
</html>
