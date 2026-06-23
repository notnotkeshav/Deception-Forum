<?php
/**
 * Invite Management Dashboard
 * Superadmin only - View, create, revoke invite codes
 */

$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite Management - Deception Forum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 2em auto;
            padding: 0 1em;
        }

        .dashboard-header {
            background: #121212;
            border: 1px solid #ffd700;
            padding: 1.5em;
            margin-bottom: 2em;
            border-radius: 4px;
        }

        .dashboard-title {
            color: #ffd700;
            font-size: 1.8em;
            font-weight: bold;
            margin: 0;
        }

        .dashboard-tabs {
            display: flex;
            gap: 0.5em;
            margin-bottom: 2em;
            border-bottom: 1px solid #333;
            flex-wrap: wrap;
        }

        .dashboard-tabs button {
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            color: #aaa;
            padding: 0.8em 1.2em;
            cursor: pointer;
            transition: all 0.2s;
        }

        .dashboard-tabs button.active {
            color: #ffd700;
            border-bottom-color: #ffd700;
        }

        .dashboard-panel {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .dashboard-panel.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dashboard-section {
            background: #121212;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 1.5em;
            margin-bottom: 1.5em;
        }

        .dashboard-section h3 {
            color: #ffd700;
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

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }

        .invite-card {
            background: #1a1a1a;
            border-left: 3px solid #ffd700;
            padding: 1em;
            margin-bottom: 0.8em;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1em;
        }

        .invite-info {
            flex: 1;
        }

        .invite-code {
            color: #ffd700;
            font-family: monospace;
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 0.2em;
            word-break: break-all;
        }

        .invite-meta {
            color: #aaa;
            font-size: 0.9em;
        }

        .invite-status {
            display: inline-block;
            padding: 0.2em 0.5em;
            border-radius: 2px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 0.5em;
        }

        .status-unused {
            background: #0a3a0a;
            color: #0a0;
        }

        .status-used {
            background: #1a1a3a;
            color: #3498db;
        }

        .status-revoked {
            background: #3a1a1a;
            color: #f03;
        }

        .status-expired {
            background: #3a2a1a;
            color: #ffaa00;
        }

        .btn-dashboard {
            background: #121212;
            border: 1.5px solid #ffd700;
            color: #ffd700;
            padding: 0.6em 1.2em;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.15s;
            font-weight: bold;
        }

        .btn-dashboard:hover {
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1em;
            margin-bottom: 1.5em;
        }

        .stat-card {
            background: #1a1a1a;
            border-left: 3px solid #ffd700;
            padding: 1em;
            border-radius: 3px;
            text-align: center;
        }

        .stat-number {
            color: #ffd700;
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 0.2em;
        }

        .stat-label {
            color: #aaa;
            font-size: 0.9em;
        }

        .batch-card {
            background: #1a1a1a;
            border-left: 3px solid #9b59b6;
            padding: 1em;
            margin-bottom: 0.8em;
            border-radius: 3px;
        }

        .batch-name {
            color: #ffd700;
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 0.2em;
        }

        .batch-stats {
            display: flex;
            gap: 1.5em;
            margin-top: 0.5em;
            font-size: 0.9em;
        }

        .batch-stat {
            color: #aaa;
        }

        .alert-dashboard {
            background: #1a3a1a;
            border-left: 3px solid #0a0;
            color: #0a0;
            padding: 1em;
            border-radius: 3px;
            margin-bottom: 1em;
            animation: slideDown 0.3s ease-out;
        }

        .alert-dashboard.error {
            background: #3a1a1a;
            border-left-color: #f03;
            color: #f03;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .invite-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .search-box {
            margin-bottom: 1em;
        }

        .search-box input {
            background: #1a1a1a;
            border: 1px solid #333;
            color: #fff;
            padding: 0.8em;
            border-radius: 3px;
            width: 100%;
        }

        .filter-group {
            display: flex;
            gap: 0.5em;
            margin-bottom: 1em;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: #1a1a1a;
            border: 1px solid #333;
            color: #aaa;
            padding: 0.5em 1em;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn.active {
            background: #ffd700;
            border-color: #ffd700;
            color: #000;
        }

        .copy-btn {
            background: none;
            border: none;
            color: #0a0;
            cursor: pointer;
            font-size: 0.9em;
            padding: 0.2em 0.4em;
        }

        .copy-btn:hover {
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">🎫 Invite Management</h1>
            <p style="color: #aaa; margin: 0.5em 0 0 0;">
                Superadmin only - Create, view, and revoke invite codes
            </p>
        </div>

        <!-- Tab Navigation -->
        <div class="dashboard-tabs">
            <button class="dashboard-tab-btn active" data-panel="overview">📊 Overview</button>
            <button class="dashboard-tab-btn" data-panel="create">➕ Create Invites</button>
            <button class="dashboard-tab-btn" data-panel="view">👁️ View All</button>
            <button class="dashboard-tab-btn" data-panel="batches">📦 Batches</button>
        </div>

        <!-- Alert Container -->
        <div id="dashboard-alert-container"></div>

        <!-- ==================== OVERVIEW PANEL ==================== -->
        <div id="overview-panel" class="dashboard-panel active">
            <div class="dashboard-section">
                <h3>📈 Invite Statistics</h3>
                <div class="stats-grid" id="stats-container">
                    <div class="stat-card">
                        <div class="stat-number" id="stat-total">—</div>
                        <div class="stat-label">Total Invites</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="stat-available">—</div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="stat-used">—</div>
                        <div class="stat-label">Used</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="stat-revoked">—</div>
                        <div class="stat-label">Revoked</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="stat-expired">—</div>
                        <div class="stat-label">Expired</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h3>🔄 Quick Actions</h3>
                <div style="display: flex; gap: 1em; flex-wrap: wrap;">
                    <button type="button" class="btn-dashboard" onclick="switchTab('create')">
                        ➕ Create New Invite
                    </button>
                    <button type="button" class="btn-dashboard" onclick="switchTab('view')">
                        👁️ View All Invites
                    </button>
                    <button type="button" class="btn-dashboard" onclick="switchTab('batches')">
                        📦 Manage Batches
                    </button>
                    <button type="button" class="btn-dashboard" onclick="exportInvites()">
                        📥 Export Codes (CSV)
                    </button>
                </div>
            </div>

            <div class="dashboard-section">
                <h3>📌 Recent Invites</h3>
                <div id="recent-invites" class="invite-list"></div>
            </div>
        </div>

        <!-- ==================== CREATE PANEL ==================== -->
        <div id="create-panel" class="dashboard-panel">
            <div class="dashboard-section">
                <h3>Create Single Invite</h3>
                <form id="create-single-form">
                    <div class="form-group">
                        <label>Expiration (days)</label>
                        <input type="number" name="expirationDays" value="7" min="0" max="365">
                        <small style="color: #777;">0 = never expires</small>
                    </div>

                    <button type="submit" class="btn-dashboard" style="width: 100%; margin-top: 1em;">
                        ✓ Generate Invite
                    </button>

                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="single">
                </form>

                <div id="single-result" style="margin-top: 1.5em;"></div>
            </div>

            <div class="dashboard-section">
                <h3>Create Bulk Invites</h3>
                <form id="create-bulk-form">
                    <div class="form-group">
                        <label>Number of Codes</label>
                        <input type="number" name="count" value="10" min="1" max="10000" required>
                    </div>

                    <div class="form-group">
                        <label>Expiration (days)</label>
                        <input type="number" name="expirationDays" value="30" min="0" max="365">
                    </div>

                    <div class="form-group">
                        <label>Batch Name (optional)</label>
                        <input type="text" name="batchName" placeholder="e.g., Wave 1 - June 2026">
                    </div>

                    <button type="submit" class="btn-dashboard" style="width: 100%;">
                        ✓ Generate Batch
                    </button>

                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="bulk">
                </form>

                <div id="bulk-result" style="margin-top: 1.5em;"></div>
            </div>
        </div>

        <!-- ==================== VIEW PANEL ==================== -->
        <div id="view-panel" class="dashboard-panel">
            <div class="dashboard-section">
                <h3>All Invite Codes</h3>

                <div class="filter-group">
                    <button class="filter-btn active" onclick="filterInvites('all')">All</button>
                    <button class="filter-btn" onclick="filterInvites('unused')">Available</button>
                    <button class="filter-btn" onclick="filterInvites('used')">Used</button>
                    <button class="filter-btn" onclick="filterInvites('revoked')">Revoked</button>
                    <button class="filter-btn" onclick="filterInvites('expired')">Expired</button>
                </div>

                <div class="search-box">
                    <input type="text" placeholder="Search by code..." id="invite-search">
                </div>

                <div id="invites-list" class="invite-list"></div>
                <button type="button" class="btn-dashboard" style="width: 100%; margin-top: 1em;" onclick="loadMoreInvites()">
                    ⬇️ Load More
                </button>
            </div>
        </div>

        <!-- ==================== BATCHES PANEL ==================== -->
        <div id="batches-panel" class="dashboard-panel">
            <div class="dashboard-section">
                <h3>📦 Invite Batches</h3>
                <div id="batches-list"></div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/public/javascripts/jquery-3.7.1.min.js"></script>
    <script src="/public/javascripts/invite-manager.js"></script>

    <script>
        const csrfToken = '<?= htmlspecialchars($csrfToken) ?>';
        let currentFilter = 'all';

        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadRecentInvites();
            loadBatches();
            attachFormHandlers();
        });

        function switchTab(panelName) {
            document.querySelectorAll('.dashboard-tab-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.panel === panelName) btn.classList.add('active');
            });

            document.querySelectorAll('.dashboard-panel').forEach(panel => {
                panel.classList.remove('active');
            });

            document.getElementById(panelName + '-panel').classList.add('active');

            if (panelName === 'view') loadInvites();
            if (panelName === 'batches') loadBatches();
        }

        // Tab click handlers
        document.querySelectorAll('.dashboard-tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                switchTab(this.dataset.panel);
            });
        });

        function showAlert(message, type = 'success') {
            const alert = document.createElement('div');
            alert.className = `alert-dashboard ${type === 'success' ? '' : 'error'}`;
            alert.innerHTML = `
                <strong>${type === 'success' ? '✓' : '✕'}</strong> ${message}
                <button type="button" style="float: right; background: none; border: none; color: inherit; cursor: pointer; font-size: 1.2em;" onclick="this.parentElement.remove()">×</button>
            `;
            document.getElementById('dashboard-alert-container').appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }
    </script>
</body>
</html>
