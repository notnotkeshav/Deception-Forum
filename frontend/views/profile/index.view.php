<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>⛧ <?= htmlspecialchars($profile['username']) ?> ⛧</title>
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

    .profile-wrapper {
      max-width: 1000px;
      margin: 3rem auto;
      padding: 0 1rem;
    }

    .profile-header {
      background: #0a0a0a;
      border: 1px solid #960d0d;
      padding: 2rem;
      margin-bottom: 2rem;
      display: flex;
      gap: 2rem;
      align-items: center;
    }

    .profile-pic {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 3px solid #960d0d;
      object-fit: cover;
    }

    .profile-info {
      flex: 1;
    }

    .profile-username {
      font-family: 'vamp', sans-serif;
      color: #f03;
      font-size: 2rem;
      letter-spacing: 2px;
      text-shadow: 0 0 10px rgba(255, 0, 51, 0.6);
      margin-bottom: 0.5rem;
    }

    .profile-name {
      color: #999;
      font-size: 1.1rem;
      margin-bottom: 1rem;
    }

    .profile-meta {
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
      color: #777;
      font-size: 0.9rem;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .meta-label {
      color: #555;
    }

    .badge {
      background: #960d0d;
      color: #fff;
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.8rem;
      text-transform: uppercase;
    }

    .badge.vip {
      background: #ff6600;
    }

    .badge.pro {
      background: #0099ff;
    }

    .badge.elite {
      background: #9900ff;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: #0a0a0a;
      border: 1px solid #333;
      padding: 1.5rem;
      text-align: center;
    }

    .stat-value {
      font-size: 2rem;
      color: #f03;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }

    .stat-label {
      color: #777;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .section-title {
      color: #f03;
      font-size: 1.5rem;
      font-family: 'vamp', sans-serif;
      letter-spacing: 2px;
      margin: 2rem 0 1rem;
      text-shadow: 0 0 10px rgba(255, 0, 51, 0.4);
    }

    .content-list {
      list-style: none;
    }

    .content-item {
      background: #0a0a0a;
      border-left: 3px solid #333;
      padding: 1rem 1.5rem;
      margin-bottom: 0.8rem;
      transition: all 0.3s;
    }

    .content-item:hover {
      border-left-color: #960d0d;
      transform: translateX(5px);
      background: #111;
    }

    .item-title {
      color: #f03;
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
    }

    .item-title a {
      color: #f03;
      text-decoration: none;
    }

    .item-title a:hover {
      color: #ff3333;
    }

    .item-meta {
      color: #777;
      font-size: 0.85rem;
    }

    .item-content {
      color: #999;
      margin: 0.5rem 0;
      line-height: 1.5;
    }

    .item-votes {
      display: flex;
      gap: 1rem;
      margin-top: 0.5rem;
      font-size: 0.85rem;
    }

    .vote-up {
      color: #0f0;
    }

    .vote-down {
      color: #f00;
    }

    .edit-btn {
      background: transparent;
      border: 2px solid #960d0d;
      color: #f03;
      padding: 0.5rem 1.5rem;
      text-decoration: none;
      font-size: 0.9rem;
      text-transform: uppercase;
      transition: all 0.3s;
      display: inline-block;
      margin-top: 1rem;
    }

    .edit-btn:hover {
      background: #960d0d;
      color: #fff;
    }

    .empty-state {
      text-align: center;
      color: #666;
      padding: 2rem;
      background: #0a0a0a;
      border: 1px solid #333;
    }
  </style>
</head>

<body>
  <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

  <div class="profile-wrapper">
    <div class="profile-header">
      <?php if ($profile['profilePic']): ?>
        <img src="<?= htmlspecialchars($profile['profilePic']) ?>" alt="Profile" class="profile-pic">
      <?php else: ?>
        <div class="profile-pic" style="background: #960d0d; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #fff;">
          <?= strtoupper(substr($profile['username'], 0, 1)) ?>
        </div>
      <?php endif; ?>

      <div class="profile-info">
        <div class="profile-username">
          ⛧ <?= htmlspecialchars($profile['username']) ?> ⛧
        </div>

        <?php if (isset($profile['name'])): ?>
          <div class="profile-name"><?= htmlspecialchars($profile['name']) ?></div>
        <?php endif; ?>

        <div class="profile-meta">
          <?php if (isset($profile['joinedDate'])): ?>
            <div class="meta-item">
              <span class="meta-label">Joined:</span>
              <?= date('M Y', strtotime($profile['joinedDate'])) ?>
            </div>
          <?php endif; ?>

          <?php if (isset($profile['reputation'])): ?>
            <div class="meta-item">
              <span class="meta-label">Reputation:</span>
              <strong style="color: #f03;"><?= $profile['reputation'] ?></strong>
            </div>
          <?php endif; ?>

          <?php if ($profile['upgrades']): ?>
            <span class="badge <?= strtolower($profile['upgrades']) ?>">
              <?= htmlspecialchars($profile['upgrades']) ?>
            </span>
          <?php endif; ?>
        </div>

        <?php if ($isOwnProfile): ?>
          <a href="/profile/settings" class="edit-btn">⚙ Privacy Settings</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($stats)): ?>
      <div class="stats-grid">
        <?php if (isset($stats['threadCount'])): ?>
          <div class="stat-card">
            <div class="stat-value"><?= $stats['threadCount'] ?></div>
            <div class="stat-label">Threads</div>
          </div>
        <?php endif; ?>

        <?php if (isset($stats['commentCount'])): ?>
          <div class="stat-card">
            <div class="stat-value"><?= $stats['commentCount'] ?></div>
            <div class="stat-label">Comments</div>
          </div>
        <?php endif; ?>

        <?php if (isset($stats['totalVotes'])): ?>
          <div class="stat-card">
            <div class="stat-value"><?= $stats['totalVotes'] ?></div>
            <div class="stat-label">Total Votes</div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($threads)): ?>
      <h3 class="section-title">Recent Threads</h3>
      <ul class="content-list">
        <?php foreach ($threads as $thread): ?>
          <li class="content-item">
            <div class="item-title">
              <a href="/thread?id=<?= htmlspecialchars($thread['id']) ?>">
                <?= htmlspecialchars($thread['title']) ?>
              </a>
            </div>
            <div class="item-meta">
              <?= date('M d, Y', strtotime($thread['createdAt'])) ?> •
              <?= $thread['viewsCount'] ?> views
            </div>
            <div class="item-votes">
              <span class="vote-up">▲ <?= $thread['upvoteCount'] ?></span>
              <span class="vote-down">▼ <?= $thread['downvoteCount'] ?></span>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if (!empty($comments)): ?>
      <h3 class="section-title">Recent Comments</h3>
      <ul class="content-list">
        <?php foreach ($comments as $comment): ?>
          <li class="content-item">
            <div class="item-title">
              <a href="/thread?id=<?= htmlspecialchars($comment['threadId']) ?>">
                in: <?= htmlspecialchars($comment['threadTitle']) ?>
              </a>
            </div>
            <div class="item-content">
              <?= htmlspecialchars(substr($comment['content'], 0, 200)) ?><?= strlen($comment['content']) > 200 ? '...' : '' ?>
            </div>
            <div class="item-meta">
              <?= date('M d, Y', strtotime($comment['createdAt'])) ?>
            </div>
            <div class="item-votes">
              <span class="vote-up">▲ <?= $comment['upvoteCount'] ?></span>
              <span class="vote-down">▼ <?= $comment['downvoteCount'] ?></span>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if (empty($threads) && empty($comments) && empty($stats)): ?>
      <div class="empty-state">
        This user hasn't shared any public activity yet.
      </div>
    <?php endif; ?>
  </div>
</body>

</html>