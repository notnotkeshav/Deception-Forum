<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>⛧ Private Chats ⛧</title>
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

      .chats-wrapper {
         max-width: 900px;
         margin: 2.5rem auto;
         padding: 0 1rem;
      }

      .page-header {
         text-align: left;
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
         margin-left: 70%;
         margin-right: 0;
         background: #1a0000;
         border: 2px solid #960d0d;
         color: #f03;
         font-family: 'courier new', monospace;
         font-size: 1.2rem;
         font-weight: bold;
         padding: 0.9rem 2.5rem;
         text-decoration: none;
         letter-spacing: 1px;
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

      .chats-list {
         list-style: none;
         padding: 0;
         margin-top: 2rem;
      }

      .chat-item {
         background: #0a0a0a;
         border-left: 3px solid #333;
         padding: 1.2rem 1.8rem;
         margin-bottom: 0.8rem;
         transition: all 0.3s ease;
      }

      .chat-item:hover {
         background: #111;
         border-left-color: #960d0d;
         transform: translateX(8px);
         box-shadow: 0 2px 15px rgba(150, 13, 13, 0.2);
      }

      .chat-link {
         text-decoration: none;
         display: block;
      }

      .chat-user {
         color: #f03;
         font-size: 1.2rem;
         font-weight: bold;
         letter-spacing: 1px;
         display: flex;
         align-items: center;
         gap: 1rem;
         margin-bottom: 0.4rem;
      }

      .chat-user::before {
         content: "⛧";
         font-size: 1.3rem;
         color: #960d0d;
      }

      .chat-link:hover .chat-user {
         color: #ff3333;
      }

      .chat-preview {
         color: #777;
         font-size: 0.9rem;
         margin-left: 2.3rem;
         font-style: italic;
         white-space: nowrap;
         overflow: hidden;
         text-overflow: ellipsis;
         max-width: 600px;
      }

      .chat-time {
         color: #555;
         font-size: 0.75rem;
         margin-left: 2.3rem;
         margin-top: 0.2rem;
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

   <div class="chats-wrapper">
      <div class="page-header">
         <h2 class="page-title">⛧ All Private Chats ⛧</h2>
         <a href="/private-chat/new" class="new-chat-btn">Start New Chat</a>
      </div>

      <?php if (!empty($chats)): ?>
         <ul class="chats-list">
            <?php foreach ($chats as $chat): ?>
               <li class="chat-item">
                  <a href="/private-chat?id=<?= htmlspecialchars($chat['id']) ?>" class="chat-link">
                     <div class="chat-user">
                        <?= htmlspecialchars($chat['otherUsername']) ?>
                     </div>
                     <?php if (!empty($chat['lastMessage'])): ?>
                        <div class="chat-preview">
                           <span><?= htmlspecialchars(substr($chat['lastMessage'], 0, 60)) ?><?= strlen($chat['lastMessage']) > 60 ? '...' : '' ?></span>
                           <?php if (!empty($chat['lastMessageAt'])): ?>
                              <span class="chat-time">
                                 <?= htmlspecialchars($chat['lastMessageAt']) ?>
                              </span>
                           <?php endif; ?>
                        </div>
                     <?php endif; ?>
                  </a>
               </li>
            <?php endforeach; ?>
         </ul>
      <?php else: ?>
         <div class="empty-state">
            No private chats found
         </div>
      <?php endif; ?>
   </div>
</body>

</html>