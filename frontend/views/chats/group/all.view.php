<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>⛧ Group Chats ⛧</title>
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

      .chat-container {
         display: flex;
         flex-direction: column;
         max-width: 900px;
         margin: 2.5rem auto;
         padding: 0 1rem;
      }

      .page-header {
         display: flex;
         justify-content: space-between;
         align-items: center;
         margin-bottom: 2.5rem;
      }

      .page-title {
         font-family: 'vamp', sans-serif;
         color: #f03;
         font-size: 2.2rem;
         letter-spacing: 3px;
         text-shadow: 0 0 15px rgba(255, 0, 51, 0.6);
         margin-bottom: 2rem;
      }

      .new-chat-btn {
         display: inline-block;
         background: #1a0000;
         border: 2px solid #960d0d;
         color: #f03;
         font-family: 'courier new', monospace;
         font-size: 1.2rem;
         font-weight: bold;
         padding: 0.8rem 2rem;
         text-decoration: none;
         letter-spacing: 1.2px;
         text-transform: uppercase;
         transition: all 0.3s ease;
         box-shadow: 0 0 20px rgba(150, 13, 13, 0.3);
      }

      .new-chat-btn:hover {
         background: #960d0d;
         color: #fff;
         box-shadow: 0 0 30px rgba(255, 0, 51, 0.6);
         transform: translateY(-2px);
      }

      .groups-list {
         list-style: none;
         padding: 0;
         margin-top: 2rem;
      }

      .group-item {
         background: #0a0a0a;
         border-left: 3px solid #333;
         padding: 1.5rem 2rem;
         margin-bottom: 1rem;
         transition: all 0.3s ease;
      }

      .group-item:hover {
         background: #111;
         border-left-color: #960d0d;
         transform: translateX(8px);
         box-shadow: 0 2px 15px rgba(150, 13, 13, 0.2);
      }

      .group-link {
         text-decoration: none;
         color: #f03;
         font-size: 1.2rem;
         font-weight: bold;
         letter-spacing: 1px;
         display: flex;
         align-items: center;
         gap: 1rem;
      }

      .group-link:hover {
         color: #ff3333;
      }

      .group-link::before {
         content: "⛧";
         font-size: 1.3rem;
         color: #960d0d;
      }

      .empty-state {
         text-align: center;
         color: #666;
         font-size: 1.1rem;
         font-style: italic;
         padding: 3rem 0;
         background: #0a0a0a;
         border: 1px solid #333;
         margin-top: 2rem;
      }

      .empty-state::before {
         content: "⚠";
         display: block;
         font-size: 3rem;
         color: #f03;
         margin-bottom: 1rem;
      }
   </style>
</head>

<body>
   <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

   <div class="chat-container">
      <div class="page-header">
         <h2 class="page-title">⛧ <?= htmlspecialchars($heading) ?> ⛧</h2>
         <a href="/group-chat/new" class="new-chat-btn">Start New Chat</a>
      </div>

      <?php if (!empty($groups)): ?>
         <ul class="groups-list">
            <?php foreach ($groups as $group): ?>
               <li class="group-item">
                  <a href="/group-chat?id=<?= htmlspecialchars($group['id']) ?>" class="group-link">
                     <?= htmlspecialchars($group['groupName']) ?>
                  </a>
               </li>
            <?php endforeach; ?>
         </ul>
      <?php else: ?>
         <div class="empty-state">
            No group chats found
         </div>
      <?php endif; ?>
   </div>
</body>

</html>