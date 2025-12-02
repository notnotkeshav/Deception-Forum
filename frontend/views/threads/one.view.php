<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>⛧ Thread - Red Skull ⛧</title>
<link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
   <style>
      @font-face {
         font-family: 'vamp';
         src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
      }

      body {
         background: #000;
         color: #fff;
         font-family: 'Courier New', monospace;
         min-height: 100vh;
         margin: 0;
         padding: 0;
      }

      .main-container {
         width: 95%;
         max-width: 1400px;
         margin: 1.5rem auto;
      }

      .thread-header-section {
         background: #0a0a0a;
         border: 1px solid #333;
         padding: 1.5rem;
         margin-bottom: 1.5rem;
      }

      .thread-title {
         font-weight: bold;
         color: #f03;
         font-size: 2rem;
         letter-spacing: 1.5px;
         margin-bottom: 1rem;
         text-shadow: 0 0 8px rgba(255, 0, 0, 0.4);
      }

      .thread-meta {
         display: flex;
         justify-content: space-between;
         align-items: center;
         padding-top: 1rem;
         border-top: 1px solid #960d0d;
         flex-wrap: wrap;
         gap: 1rem;
      }

      .meta-item {
         color: #f2f2f2;
         font-size: 0.85rem;
      }

      .meta-label {
         color: #f03;
         text-transform: uppercase;
         font-weight: bold;
         margin-right: 0.5rem;
      }

      .thread-status-badge {
         display: inline-block;
         padding: 0.2rem 0.5rem;
         font-size: 0.75rem;
         border-radius: 2px;
         margin-left: 0.5rem;
         text-transform: uppercase;
      }

      .status-locked {
         background: #333;
         color: #aaa;
      }

      .category-badge {
         background: #960d0d;
         color: #fff;
         padding: 0.3rem 0.8rem;
         border-radius: 2px;
         text-transform: uppercase;
         font-size: 0.8rem;
         font-weight: bold;
      }

      /* Content and Actions Grid */
      .thread-body-grid {
         display: grid;
         grid-template-columns: 1fr 250px;
         gap: 1.5rem;
         margin-bottom: 1.5rem;
      }

      .thread-content-section {
         background: #0a0a0a;
         border: 1px solid #333;
         padding: 1.5rem;
      }

      .content-header {
         color: #f03;
         text-transform: uppercase;
         font-size: 0.9rem;
         font-weight: bold;
         margin-bottom: 1rem;
         padding-bottom: 0.5rem;
         border-bottom: 1px solid #960d0d;
      }

      .thread-content {
         color: #ccc;
         font-size: 0.95rem;
         line-height: 1.8;
         margin-bottom: 1.5rem;
         overflow: scroll;
      }

      .thread-sidebar {
         display: flex;
         flex-direction: column;
         gap: 1.5rem;
      }

      .sidebar-card {
         background: #0a0a0a;
         border: 1px solid #333;
         padding: 1.2rem;
      }

      .sidebar-card-title {
         color: #f03;
         text-transform: uppercase;
         font-size: 0.85rem;
         font-weight: bold;
         margin-bottom: 1rem;
         padding-bottom: 0.5rem;
         border-bottom: 1px solid #960d0d;
      }

      .vote-container {
         display: flex;
         flex-direction: column;
         gap: 0.8rem;
      }

      .vote-btn {
         background: #111;
         border: 2px solid #960d0d;
         color: #f03;
         padding: 0.7rem;
         cursor: pointer;
         font-family: 'Courier New', monospace;
         font-weight: bold;
         text-transform: uppercase;
         font-size: 0.85rem;
         transition: all 0.3s;
         letter-spacing: 1px;
      }

      .vote-btn:hover:not([disabled]) {
         background: #960d0d;
         color: #fff;
         box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
      }

      .vote-btn[disabled] {
         opacity: 0.3;
         cursor: not-allowed;
      }

      .vote-count-display {
         background: #111;
         border: 1px solid #333;
         color: #fff;
         padding: 0.6rem;
         text-align: center;
         font-size: 0.9rem;
      }

      .vote-count-display .count-number {
         color: #f03;
         font-weight: bold;
         font-size: 1.2rem;
      }

      .action-btn {
         background: #111;
         border: 2px solid #333;
         color: #fff;
         padding: 0.6rem 1rem;
         cursor: pointer;
         font-family: 'Courier New', monospace;
         font-weight: bold;
         text-transform: uppercase;
         font-size: 0.8rem;
         transition: all 0.3s;
         width: 100%;
         margin-bottom: 0.5rem;
         letter-spacing: 1px;
      }

      .action-btn-edit {
         border-color: #ffaa00;
         color: #ffaa00;
      }

      .action-btn-edit:hover {
         background: #ffaa00;
         color: #000;
         box-shadow: 0 0 10px rgba(255, 170, 0, 0.5);
      }

      .action-btn-delete {
         border-color: #960d0d;
         color: #f03;
      }

      .action-btn-delete:hover {
         background: #960d0d;
         color: #fff;
         box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
      }

      .action-btn-lock {
         border-color: #00ff00;
         color: #00ff00;
      }

      .action-btn-lock:hover {
         background: #00ff00;
         color: #000;
      }

      .action-btn-unlock {
         border-color: #f03;
         color: #f03;
      }

      .action-btn-unlock:hover {
         background: #f03;
         color: #000;
      }

      .lock-warning {
         background: #1a0000;
         border: 1px solid #960d0d;
         color: #ff3535;
         padding: 1rem;
         text-align: center;
         font-size: 0.85rem;
         text-transform: uppercase;
         font-weight: bold;
      }

      /* Images Section */
      .images-section {
         background: #0a0a0a;
         border: 1px solid #333;
         padding: 1.5rem;
         margin-bottom: 1.5rem;
      }

      .images-grid {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
         gap: 1.2rem;
         margin-top: 1rem;
      }

      .images-grid img {
         width: 100%;
         height: auto;
         max-height: 300px;
         object-fit: contain;
         border: 2px solid #960d0d;
         background: #000;
         transition: all 0.3s;
      }

      .images-grid img:hover {
         border-color: #f03;
         box-shadow: 0 0 15px rgba(255, 0, 0, 0.5);
      }

      /* Alert Messages */
      .alert-message {
         padding: 1rem 1.5rem;
         margin-bottom: 1rem;
         border: 1px solid;
         font-size: 0.9rem;
         text-transform: uppercase;
         font-weight: bold;
      }

      .alert-success {
         background: #0a1a0a;
         border-color: #00ff00;
         color: #00ff00;
      }

      .alert-error {
         background: #1a0000;
         border-color: #f03;
         color: #ff3535;
      }

      .d-inline-block {
         display: inline-block;
      }

      /* Responsive adjustments for smaller screens */
      @media (max-width: 900px) {
         .thread-body-grid {
            grid-template-columns: 1fr;
         }
      }
   </style>
</head>

<body>
   <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

   <div class="main-container" id="thread-container"
      data-thread-id="<?php echo htmlspecialchars($thread['id']); ?>"
      data-thread-locked="<?php echo htmlspecialchars($thread['locked']); ?>">

      <!-- Thread Header -->
      <div class="thread-header-section">
         <div class="thread-title">
            ⛧ <?php echo htmlspecialchars($thread['title']); ?>
            <?php if ($thread['locked']): ?>
               <span class="thread-status-badge status-locked">🔒 LOCKED</span>
            <?php endif; ?>
         </div>

         <div class="thread-meta">
            <div class="meta-item">
               <?php if (isset($thread['category']) && $thread['category'] !== null): ?>
                  <span class="category-badge">Category: <?php echo strtoupper(htmlspecialchars($thread['category']['name'])); ?></span>
               <?php else: ?>
                  <span class="category-badge">UNCATEGORIZED</span>
               <?php endif; ?>
            </div>
            <div class="meta-item">
               <span class="meta-label">POSTED:</span>
               <?php echo date('M d, Y - H:i', strtotime($thread['createdAt'])); ?>
            </div>
            <?php if ($thread['editedAt']): ?>
               <div class="meta-item">
                  <span class="meta-label">EDITED:</span>
                  <?php echo date('M d, Y - H:i', strtotime($thread['editedAt'])); ?>
               </div>
            <?php endif; ?>
         </div>
      </div>

      <!-- Alert Messages -->
      <div class="alert-message alert-success" id="success-block" style="display:none;"></div>
      <div class="alert-message alert-error" id="error-block" style="display:none;"></div>

      <!-- Main Content Grid -->
      <div class="thread-body-grid">
         <!-- Thread Content -->
         <div class="thread-content-section">
            <div class="content-header">⛧ THREAD CONTENT</div>
            <div class="thread-content">
               <?php echo $thread['content'] ?>
            </div>
         </div>

         <!-- Sidebar -->
         <div class="thread-sidebar">
            <!-- Voting Card -->
            <div class="sidebar-card">
               <div class="sidebar-card-title">⛧ VOTING</div>
               <div class="vote-container">
                  <button id="upvote-thread"
                     data-thread-id="<?php echo $thread['id']; ?>"
                     class="vote-btn"
                     <?php if ($thread['locked'] == 1) echo 'disabled'; ?>>
                     <div class="">
                        <div class="count-number" id="thread-upvotes-count">
                           <?php echo htmlspecialchars($thread['upvoteCount']); ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #fff;">UPVOTES</div>
                     </div>
                  </button>

                  <button id="downvote-thread"
                     data-thread-id="<?php echo $thread['id']; ?>"
                     class="vote-btn"
                     <?php if ($thread['locked'] == 1) echo 'disabled'; ?>>
                     <div class="">
                        <div class="count-number" id="thread-downvotes-count">
                           <?php echo htmlspecialchars($thread['downvoteCount']); ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #fff;">DOWNVOTES</div>
                     </div>
                  </button>
               </div>
            </div>

            <!-- Thread Actions Card (Owner) -->
            <?php if ($_SESSION['userId'] == $thread['userId']) : ?>
               <div class="sidebar-card">
                  <div class="sidebar-card-title">⛧ YOUR ACTIONS</div>
                  <?php if (!$thread['locked']): ?>
                     <form class="d-inline-block" action="thread/edit" method="get" style="width: 100%;">
                        <input type="hidden" name="id" value="<?php echo $thread['id']; ?>">
                        <button type="submit" class="action-btn action-btn-edit">✏ EDIT THREAD</button>
                     </form>
                     <button type="button" id="deleteThread" class="action-btn action-btn-delete">🗑 DELETE THREAD</button>
                  <?php else: ?>
                     <div class="lock-warning">THREAD IS LOCKED</div>
                  <?php endif; ?>
               </div>
            <?php endif; ?>

            <!-- Moderator Actions Card -->
            <?php if ($_SESSION['moderator']) : ?>
               <div class="sidebar-card">
                  <div class="sidebar-card-title">⛧ MODERATOR</div>
                  <button id="lock-unlock-btn"
                     data-thread-id="<?php echo $thread['id']; ?>"
                     class="action-btn <?php echo $thread['locked'] ? 'action-btn-unlock' : 'action-btn-lock'; ?>">
                     <?php echo $thread['locked'] ? '🔓 UNLOCK THREAD' : '🔒 LOCK THREAD'; ?>
                  </button>
               </div>
            <?php endif; ?>
         </div>
      </div>

      <!-- Images Section -->
      <?php if (!empty($thread['images'])): ?>
         <div class="images-section">
            <div class="content-header">⛧ ATTACHED IMAGES</div>
            <div class="images-grid">
               <?php foreach ($thread['images'] as $image): ?>
                  <img src="<?php echo $image['url']; ?>" alt="Thread Image">
               <?php endforeach; ?>
            </div>
         </div>
      <?php endif; ?>
   </div>

   <?php require(base_path("/frontend/views/partials/comments.view.php")); ?>

   <script src="/public/javascripts/jquery-3.7.1.min.js"></script>
   <script>
      const API_BASE_URL = '';
      let token = sessionStorage.getItem('token');

      const showError = (msg) => {
         $('#error-block').text(msg).show();
         setTimeout(() => $('#error-block').fadeOut(), 5000);
      };
      const showSuccess = (msg) => {
         $('#success-block').text(msg).show();
         setTimeout(() => $('#success-block').fadeOut(), 5000);
      };

      const handleAjaxError = (xhr, status, error) => {
         console.error(`[${status}] AJAX Error:`, error);
         if (xhr.status === 401) {
            showError('UNAUTHORIZED ACCESS - LOGIN REQUIRED');
            sessionStorage.removeItem('token');
            setTimeout(() => window.location.href = '/signin', 1000);
         } else {
            showError(xhr.responseJSON?.message || 'UNEXPECTED ERROR - RETRY');
         }
      };

      const sendRequest = async (url, method, data, contentType, successCallback) => {
         try {
            const response = await $.ajax({
               url,
               method,
               contentType,
               dataType: 'json',
               data,
               headers: {
                  'Authorization': `Bearer ${token}`
               },
            });
            if (response) successCallback(response);
         } catch (xhr) {
            handleAjaxError(xhr, 'error', xhr.responseText || xhr.statusText);
         }
      };

      $('#deleteThread').on('click', function() {
         const threadId = $('#thread-container').data('thread-id');
         if (!confirm('CONFIRM DELETE? THIS CANNOT BE UNDONE.')) return;
         sendRequest(
            `${API_BASE_URL}/thread?id=${threadId}`,
            'DELETE',
            null,
            'application/json',
            (response) => {
               if (response.success) {
                  showSuccess('THREAD DELETED - REDIRECTING...');
                  setTimeout(() => window.location.href = `/threads`, 1200);
               }
            }
         );
      });

      $('#upvote-thread').on('click', function() {
         const threadId = $(this).data('thread-id');
         handleVote(threadId, 'upvote');
      });
      $('#downvote-thread').on('click', function() {
         const threadId = $(this).data('thread-id');
         handleVote(threadId, 'downvote');
      });

      const handleVote = (threadId, voteType) => {
         const userId = sessionStorage.getItem('userId');
         if (!userId) {
            showError("LOGIN REQUIRED TO VOTE");
            return;
         }
         sendRequest(
            '/thread/vote',
            'PUT',
            JSON.stringify({
               action: 'vote',
               threadId,
               voteType,
               userId,
            }),
            'application/json',
            (response) => {
               if (response.success) {
                  $('#thread-upvotes-count').text(response.details.updatedUpvotes);
                  $('#thread-downvotes-count').text(response.details.updatedDownvotes);
                  showSuccess('VOTE REGISTERED');
               } else {
                  showError(response.error || 'VOTE FAILED');
                  console.error('Vote failed:', response.error);
               }
            }
         );
      };
   </script>
</body>

</html>