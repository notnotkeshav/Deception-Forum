<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>⛧ Privacy Settings ⛧</title>
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
      color: #f8f8f8;
      font-family: 'Courier New', monospace;
      min-height: 100vh;
      padding-top: 80px;
    }

    .settings-wrapper {
      max-width: 800px;
      margin: 3rem auto;
      padding: 0 1rem;
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

    .section-title {
      color: #f03;
      font-size: 1.3rem;
      font-weight: bold;
      margin: 2rem 0 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #333;
    }

    .section-title:first-child {
      margin-top: 0;
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

    .visibility-select {
      background: #111;
      border: 2px solid #555;
      color: #f03;
      padding: 0.5rem 1rem;
      font-family: 'courier new', monospace;
      font-size: 0.9rem;
      cursor: pointer;
    }

    .visibility-select:focus {
      border-color: #f03;
      outline: none;
    }

    .form-actions {
      margin-top: 2rem;
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
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

    .btn-view-profile {
      border-color: #0066cc;
      color: #0af;
    }

    .btn-view-profile:hover {
      background: #0066cc;
      color: #fff;
      box-shadow: 0 0 20px rgba(0, 170, 255, 0.5);
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
      <h2 class="page-title">⛧ Profile Privacy Settings ⛧</h2>
      <p class="page-description">
        Control what information is visible on your public profile. Changes are saved automatically.
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

    <form method="POST" action="/profile/settings" class="settings-form">
      <div class="section-title">Profile Visibility</div>

      <div class="setting-item">
        <div class="setting-info">
          <label class="setting-label">Profile Visibility</label>
          <span class="setting-description">
            Make your profile private or public to other users
          </span>
        </div>
        <select name="profile_visibility" class="visibility-select">
          <option value="public" <?= $settings['profile_visibility'] === 'public' ? 'selected' : '' ?>>Public</option>
          <option value="private" <?= $settings['profile_visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
        </select>
      </div>

      <div class="section-title">Personal Information</div>

      <div class="setting-item">
        <div class="setting-info">
          <label class="setting-label">
            <span class="status-indicator <?= $settings['show_name'] ? 'active' : '' ?>"></span>
            Show Real Name
          </label>
          <span class="setting-description">
            Display your real name on your profile
          </span>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="show_name"
            data-setting="show_name"
            <?= $settings['show_name'] ? 'checked' : '' ?>>
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <label class="setting-label">
            <span class="status-indicator <?= $settings['show_join_date'] ? 'active' : '' ?>"></span>
            Show Join Date
          </label>
          <span class="setting-description">
            Display when you joined the forum
          </span>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="show_join_date"
            data-setting="show_join_date"
            <?= $settings['show_join_date'] ? 'checked' : '' ?>>
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <label class="setting-label">
            <span class="status-indicator <?= $settings['show_last_login'] ? 'active' : '' ?>"></span>
            Show Last Login
          </label>
          <span class="setting-description">
            Display your last login time
          </span>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="show_last_login"
            data-setting="show_last_login"
            <?= $settings['show_last_login'] ? 'checked' : '' ?>>
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <label class="setting-label">
            <span class="status-indicator <?= $settings['show_reputation'] ? 'active' : '' ?>"></span>
            Show Reputation
          </label>
          <span class="setting-description">
            Display your reputation score
          </span>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="show_reputation"
            data-setting="show_reputation"
            <?= $settings['show_reputation'] ? 'checked' : '' ?>>
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="section-title">Activity & Content</div>

      <div class="setting-item">
        <div class="setting-info">
          <label class="setting-label">
            <span class="status-indicator <?= $settings['show_stats'] ? 'active' : '' ?>"></span>
            Show Statistics
          </label>
          <span class="setting-description">
            Display thread count, comment count, and total votes
          </span>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="show_stats"
            data-setting="show_stats"
            <?= $settings['show_stats'] ? 'checked' : '' ?>>
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <label class="setting-label">
            <span class="status-indicator <?= $settings['show_threads'] ? 'active' : '' ?>"></span>
            Show Threads
          </label>
          <span class="setting-description">
            Display your recent threads on your profile
          </span>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="show_threads"
            data-setting="show_threads"
            <?= $settings['show_threads'] ? 'checked' : '' ?>>
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <div class="setting-info">
          <label class="setting-label">
            <span class="status-indicator <?= $settings['show_comments'] ? 'active' : '' ?>"></span>
            Show Comments
          </label>
          <span class="setting-description">
            Display your recent comments on your profile
          </span>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="show_comments"
            data-setting="show_comments"
            <?= $settings['show_comments'] ? 'checked' : '' ?>>
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn">Save All Settings</button>
        <a href="/profile?u=<?= urlencode($_SESSION['user']['username'] ?? '') ?>" class="btn btn-view-profile" target="_blank">👁 View Public Profile</a>
        <a href="/user" class="btn btn-secondary">Back to Dashboard</a>
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

          this.disabled = true;

          fetch('/profile/settings', {
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
                if (enabled) {
                  statusIndicator.classList.add('active');
                } else {
                  statusIndicator.classList.remove('active');
                }
                console.log('✓ Setting updated:', setting);
              } else {
                this.checked = !enabled;
                alert('Failed to update setting: ' + data.message);
              }
            })
            .catch(error => {
              console.error('Error:', error);
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