<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<h2><?php echo htmlspecialchars($thread['title']); ?></h2>
<?php if ($_SESSION['userId'] == $thread['userId']) : ?>
   <form action="/thread/edit" method="get">
      <input type="hidden" name="id" value="<?php echo $thread['id']; ?>">
      <button type="submit">Edit Thread</button>
   </form>
<?php endif; ?>
<div class="thread-details">
   <p><strong>Created at:</strong> <?php echo htmlspecialchars($thread['createdAt']); ?></p>
   <?php if ($thread['editedAt']): ?>
      <p><strong>Last edited at:</strong> <?php echo htmlspecialchars($thread['editedAt']); ?></p>
   <?php endif; ?>
   <p><?php echo nl2br(htmlspecialchars($thread['content'])); ?></p>
</div>


<?php if (isset($thread['category']) && $thread['category'] !== null): ?>
   <h3> Category: <?php echo ucfirst(htmlspecialchars($thread['category']['name'])); ?></h3>
<?php else: ?>
   <p>No category assigned to this thread.</p>
<?php endif; ?>

<h3>Images</h3>
<div class="thread-images">
   <?php foreach ($thread['images'] as $image): ?>
      <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="Thread Image" style="max-width: 200px; max-height: 200px; margin: 5px;">
   <?php endforeach; ?>
</div>

<h3>Comments</h3>
<ul id="comments-list">
   <?php if (!empty($thread['comments'])): ?>
      <?php foreach ($thread['comments'] as $comment): ?>
         <?php if ($comment['deleted'] == 0): ?> <!-- Only show non-deleted comments -->
            <li id="comment-<?php echo $comment['id']; ?>">
               <strong>User ID <?php echo htmlspecialchars($comment['userId']); ?></strong>

               <!-- Display editedAt if available, otherwise show createdAt -->
               <p><strong>Commented at:</strong> <?php echo ($comment['editedAt']) ? htmlspecialchars($comment['editedAt']) : htmlspecialchars($comment['createdAt']); ?></p>

               <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>

               <?php
               date_default_timezone_set('UTC');
               $createdAt = new DateTime($comment['createdAt']);
               $editedAt = isset($comment['editedAt']) ? new DateTime($comment['editedAt']) : $createdAt;
               $currentTime = new DateTime();
               $timeDifference = $editedAt->getTimestamp() - $currentTime->getTimestamp();
               $isEditable = $timeDifference <= 900;
               ?>

               <?php if ($_SESSION['userId'] == $comment['userId']) : ?>
                  <div class="comment-actions">
                     <!-- Edit Button -->
                     <button class="edit-btn" data-comment-id="<?php echo $comment['id']; ?>">Edit</button>

                     <!-- Delete Button -->
                     <?php if ($isEditable) : ?>
                        <button class="delete-btn" data-comment-id="<?php echo $comment['id']; ?>" onclick="return confirm('Are you sure you want to delete this comment?');">Delete</button>
                     <?php else: ?>
                        <p>Comment cannot be deleted after 15 minutes.</p>
                     <?php endif; ?>
                  </div>
               <?php endif; ?>
            </li>
         <?php endif; ?>
      <?php endforeach; ?>
   <?php else: ?>
      <li>No comments yet. Be the first to comment!</li>
   <?php endif; ?>
</ul>

<div class="comment-section">
   <form action="/comment" method="post" id="comment-form">
      <input type="hidden" name="threadId" value="<?php echo htmlspecialchars($thread['id']); ?>">
      <textarea name="content" rows="4" cols="30" placeholder="Write your comment here..." required></textarea><br>
      <button type="submit">Comment</button>
   </form>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>

<script>
   // Handle Edit and Delete buttons with AJAX
   document.querySelectorAll('.edit-btn').forEach(button => {
      button.addEventListener('click', function() {
         const commentId = this.getAttribute('data-comment-id');
         const newContent = prompt('Edit your comment:', '');

         if (newContent !== null && newContent !== '') {
            fetch('/comment', {
                  method: 'PUT',
                  headers: {
                     'Content-Type': 'application/json',
                  },
                  body: JSON.stringify({
                     commentId: commentId,
                     content: newContent,
                  }),
               })
               .then(response => response.json())
               .then(data => {
                  if (data.success) {
                     // Update the comment's content on the page
                     document.querySelector(`#comment-${commentId} p`).textContent = newContent;
                  } else {
                     alert(data.error);
                  }
               });
         }
      });
   });

   document.querySelectorAll('.delete-btn').forEach(button => {
      button.addEventListener('click', function() {
         const commentId = this.getAttribute('data-comment-id');

         fetch('/comment', {
               method: 'DELETE',
               headers: {
                  'Content-Type': 'application/json',
               },
               body: JSON.stringify({
                  commentId: commentId,
               }),
            })
            .then(response => response.json())
            .then(data => {
               if (data.success) {
                  // Remove the deleted comment from the DOM
                  document.querySelector(`#comment-${commentId}`).remove();
               } else {
                  alert(data.error);
               }
            });
      });
   });

   // Handle the comment submission form with AJAX
   document.getElementById('comment-form').addEventListener('submit', function(event) {
      event.preventDefault();

      // Create a FormData object to capture form inputs
      const formData = new FormData(this);

      fetch('/comment', {
            method: 'POST',
            body: formData,
         })
         .then(response => response.json())
         .then(data => {
            if (data.success) {
               // Create a new list item with the new comment data
               const newComment = data.comment; // Assuming the new comment data is returned
               const commentList = document.getElementById('comments-list');
               const newCommentHTML = `
                  <li id="comment-${newComment.id}">
                      <strong>User ID ${newComment.userId}</strong>
                      <p><strong>Commented at:</strong> ${newComment.createdAt}</p>
                      <p>${newComment.content}</p>
                      <button class="edit-btn" data-comment-id="${newComment.id}">Edit</button>
                      <button class="delete-btn" data-comment-id="${newComment.id}">Delete</button>
                  </li>`;
               commentList.insertAdjacentHTML('beforeend', newCommentHTML);

               // Attach event listeners for the new comment's edit and delete buttons
               document.querySelector(`#comment-${newComment.id} .edit-btn`).addEventListener('click', function() {
                  const commentId = this.getAttribute('data-comment-id');
                  const newContent = prompt('Edit your comment:', '');

                  if (newContent !== null && newContent !== '') {
                     fetch('/comment', {
                           method: 'PUT',
                           headers: {
                              'Content-Type': 'application/json',
                           },
                           body: JSON.stringify({
                              commentId: commentId,
                              content: newContent,
                           }),
                        })
                        .then(response => response.json())
                        .then(data => {
                           if (data.success) {
                              document.querySelector(`#comment-${commentId} p`).textContent = newContent;
                           } else {
                              alert(data.error);
                           }
                        });
                  }
               });

               document.querySelector(`#comment-${newComment.id} .delete-btn`).addEventListener('click', function() {
                  const commentId = this.getAttribute('data-comment-id');

                  fetch('/comment', {
                        method: 'DELETE',
                        headers: {
                           'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                           commentId: commentId,
                        }),
                     })
                     .then(response => response.json())
                     .then(data => {
                        if (data.success) {
                           document.querySelector(`#comment-${commentId}`).remove();
                        } else {
                           alert(data.error);
                        }
                     });
               });

            } else {
               alert(data.error);
            }
         });
   });
</script>