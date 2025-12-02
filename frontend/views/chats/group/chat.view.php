    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>⛧ Group Chat ⛧</title>
<link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                padding-top: 4.375rem;
            }

            .chat-wrapper {
                max-width: 56.25rem;
                margin: 1rem auto;
                padding: 0 0.8rem;
            }

            .chat-header {
                background: #0a0a0a;
                border-left: 0.1875rem solid #960d0d;
                padding: 0.8rem 1.2rem;
                margin-bottom: 1rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .chat-title {
                font-family: 'vamp', sans-serif;
                color: #f03;
                font-size: 1.4rem;
                letter-spacing: 0.125rem;
            }

            .chat-info {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .member-count {
                color: #666;
                font-size: 0.8rem;
            }

            .member-count span {
                color: #f03;
                font-weight: bold;
                margin-left: 0.3rem;
            }

            .add-member-btn {
                background: #0a0a0a;
                border: 0.0625rem solid #960d0d;
                color: #f03;
                font-weight: bold;
                padding: 0.4rem 1rem;
                text-decoration: none;
                text-transform: uppercase;
                font-size: 0.7rem;
                letter-spacing: 0.0625rem;
                transition: all 0.2s;
            }

            .add-member-btn:hover {
                background: #960d0d;
                color: #fff;
            }

            .chat-window {
                background: #000;
                border: 0.0625rem solid #960d0d;
                padding: 0.8rem;
                height: 70vh;
                overflow-y: auto;
                margin-bottom: 0.8rem;
            }

            .chat-window::-webkit-scrollbar {
                width: 0.3125rem;
            }

            .chat-window::-webkit-scrollbar-track {
                background: #0a0a0a;
            }

            .chat-window::-webkit-scrollbar-thumb {
                background: #960d0d;
                border-radius: 0.1875rem;
            }

            .message-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .msg-item {
                margin-bottom: 0.4rem;
                display: flex;
                position: relative;
            }

            .msg-owner {
                justify-content: flex-end;
            }

            .msg-other {
                justify-content: flex-start;
            }

            .msg-container {
                max-width: 70%;
                position: relative;
            }

            .msg-bubble {
                background: #0a0a0a;
                border: 0.0625rem solid #333;
                padding: 0.5rem 0.7rem 1.2rem 0.7rem;
                border-radius: 0.25rem;
                position: relative;
            }

            .msg-owner .msg-bubble {
                background: #1a0000;
                border-color: #960d0d;
            }

            .msg-hover-actions {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                display: flex;
                gap: 0.3rem;
                opacity: 0;
                transition: opacity 0.15s;
                z-index: 10;
            }

            .msg-owner .msg-hover-actions {
                left: -10rem;
            }

            .msg-other .msg-hover-actions {
                right: -6rem;
            }

            .msg-item:hover .msg-hover-actions {
                opacity: 1;
            }

            .hover-icon {
                background: #0a0a0a;
                border: 0.0625rem solid #960d0d;
                color: #f03;
                font-size: 0.7rem;
                cursor: pointer;
                padding: 0.35rem;
                transition: all 0.15s;
                border-radius: 0.1875rem;
                width: 1.625rem;
                height: 1.625rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .hover-icon:hover {
                background: #960d0d;
                color: #fff;
                transform: scale(1.1);
            }

            .date-separator {
                width: 100%;
                display: flex;
                justify-content: center;
                margin: 1.2rem 0;
                list-style: none;
            }

            .date-separator span {
                background: #0a0a0a;
                border: 0.0625rem solid #960d0d;
                color: #f03;
                padding: 0.25rem 0.8rem;
                border-radius: 0.75rem;
                font-size: 0.7rem;
                text-transform: uppercase;
                letter-spacing: 0.0625rem;
                display: inline-block;
                box-shadow: 0 0 0.625rem rgba(150, 13, 13, 0.2);
            }

            .msg-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.25rem;
                gap: 0.8rem;
            }

            .msg-sender {
                color: #f03;
                font-weight: bold;
                font-size: 0.75rem;
            }

            .msg-time {
                color: #555;
                font-size: 0.65rem;
            }

            .msg-text {
                color: #ddd;
                line-height: 1.3;
                font-size: 0.9rem;
                word-wrap: break-word;
            }

            .msg-vote-count {
                position: absolute;
                bottom: 0.3rem;
                font-size: 0.65rem;
                color: #666;
                display: flex;
                align-items: center;
                gap: 0.4rem;
            }

            .msg-owner .msg-vote-count {
                right: 0.7rem;
            }

            .msg-other .msg-vote-count {
                left: 0.7rem;
            }

            .msg-vote-count i {
                font-size: 0.6rem;
                margin-right: 0.15rem;
            }

            .empty-chat {
                text-align: center;
                color: #555;
                font-size: 0.95rem;
                padding: 2.5rem 0;
            }

            .message-form {
                background: #0a0a0a;
                border: 0.0625rem solid #960d0d;
                padding: 0.6rem;
                display: flex;
                gap: 0.6rem;
            }

            .message-input {
                flex: 1;
                background: #111;
                border: 0.0625rem solid #333;
                color: #fff;
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
                font-family: 'Courier New', monospace;
                transition: all 0.2s;
            }

            .message-input:focus {
                outline: none;
                border-color: #960d0d;
                background: #1a0000;
            }

            .message-input::placeholder {
                color: #555;
            }

            .send-btn {
                background: #1a0000;
                border: 0.0625rem solid #960d0d;
                color: #f03;
                font-family: 'vamp', sans-serif;
                font-size: 0.95rem;
                font-weight: bold;
                padding: 0.6rem 1.8rem;
                cursor: pointer;
                letter-spacing: 0.125rem;
                text-transform: uppercase;
                transition: all 0.2s;
            }

            .send-btn:hover {
                background: #960d0d;
                color: #fff;
            }
        </style>
    </head>
    <body>
        <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

        <div class="chat-wrapper">
            <div class="chat-header">
                <h1 class="chat-title">⛧ <?= htmlspecialchars($heading) ?> ⛧</h1>
                <div class="chat-info">
                    <p class="member-count">MEMBERS:<span><?= htmlspecialchars($groupInfo['memberCount'] ?? '0') ?></span></p>
                    <a href="/group-chat/member/add?groupId=<?= htmlspecialchars($_GET['id'] ?? '') ?>" class="add-member-btn">Add Member</a>
                </div>
            </div>

            <div id="chat-window" class="chat-window">
                <?php if (empty($messages)): ?>
                    <div class="empty-chat">No messages yet</div>
                <?php else: ?>
                    <ul id="message-list" class="message-list"></ul>
                <?php endif; ?>
            </div>

            <form id="sendMessageForm" class="message-form">
                <input type="hidden" id="groupId" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">
                <input type="text" id="messageInput" class="message-input" placeholder="Type a message..." required autocomplete="off">
                <button type="submit" class="send-btn">Send</button>
            </form>
        </div>

        <script src="/public/javascripts/jquery-3.7.1.min.js"></script>
        <script src="/public/javascripts/group-chat.js"></script>
    </body>
    </html>
