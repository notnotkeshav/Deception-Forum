<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<h1><?php echo htmlspecialchars($thread['title']); ?></h1>

<?php if ($_SESSION['userId'] == $thread['userId']) : ?>
   <form action="/thread/edit" method="get">
      <input type="hidden" name="id" value="<?php echo $thread['id']; ?>">
      <button type="submit">Edit Thread</button>
   </form>

   <form action="" method="post">
      <input type="hidden" name="_method" value="DELETE">
      <button type="submit">Delete</button>
   </form>
<?php endif; ?>

<div class="thread-details">
   <p><strong>Created at:</strong> <?php echo htmlspecialchars($thread['createdAt']); ?></p>
   <?php if ($thread['editedAt']): ?>
      <p><strong>Last edited at:</strong> <?php echo htmlspecialchars($thread['editedAt']); ?></p>
   <?php endif; ?>
   <p><?php echo nl2br($thread['content']); ?></p>
</div>

<?php if (isset($thread['category']) && $thread['category'] !== null): ?>
   <h3>Category: <?php echo ucfirst(htmlspecialchars($thread['category']['name'])); ?></h3>
<?php else: ?>
   <p>No category assigned to this thread.</p>
<?php endif; ?>

<h3>Images</h3>
<div class="thread-images">
   <?php foreach ($thread['images'] as $image): ?>
      <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="Thread Image" style="max-width: 200px; max-height: 200px; margin: 5px;">
   <?php endforeach; ?>
</div>

<div class="comment-section">
   <form action="/comment" method="post" id="comment-form">
      <input type="hidden" name="threadId" value="<?php echo htmlspecialchars($thread['id']); ?>">
      <label for="content">Write a comment:
         <div id="contentEditor" style="height: 200px; width:50%;"></div>
         <input type="hidden" id="content" name="content">
      </label>
      <button type="submit">Comment</button>
   </form>
</div>

<div class="reply-section" style="display: none;">
   <form action="/comment/reply" method="post" id="reply-form">
      <input type="hidden" name="parentCommentId" id="parent-comment-id">
      <input type="hidden" name="threadId" value="<?php echo htmlspecialchars($thread['id']); ?>">
      <label for="content">Write a comment:
         <div id="contentEditor" style="height: 200px; width:50%;"></div>
         <input type="hidden" id="content" name="content">
      </label>
      <button type="submit">Reply</button>
   </form>
</div>

<h3>Comments</h3>
<ul id="comments-list">
   <!-- Comments will be dynamically loaded here via AJAX -->
</ul>

<link href="/public/stylesheets/quill.snow.css" rel="stylesheet">
<script src="/public/javascripts/quill.min.js"></script>
<script>
   const quill = new Quill('#contentEditor', {
      theme: 'snow',
      modules: {
         toolbar: [
            [{
               'header': [2, 3, false]
            }],
            [{
               'list': 'ordered'
            }, {
               'list': 'bullet'
            }],
            ['bold', 'italic', 'underline'],
            ['link'],
         ]
      }
   });

   $('#comment-form').on('submit', function() {
      const content = quill.root.innerHTML;
      $('#content').val(content);
   });

   // Fetch comments dynamically
   const loadComments = () => {
      $.ajax({
         url: `/comments?threadId=<?php echo $thread['id']; ?>`,
         method: 'GET',
         dataType: 'json',
         success: (response) => {
            if (response.success) {
               const comments = response.comments;
               const commentList = $('#comments-list');
               commentList.empty();

               comments.forEach((comment) => {
                  const sanitizedContent = DOMPurify.sanitize(comment.content);
                  const commentHTML = `
                     <li id="comment-${comment.id}">
                         <strong>User ID ${comment.userId}</strong>
                         <p><strong>Commented at:</strong> ${comment.createdAt}</p>
                         <div>${sanitizedContent}</div>
                         <button class="edit-btn" response-comment-id="${comment.id}">Edit</button>
                         <button class="delete-btn" response-comment-id="${comment.id}">Delete</button>
                         <button class="reply-btn" response-comment-id="${comment.id}">Reply</button>
                     </li>`;

                  commentList.append(commentHTML);
               });
            } else {
               console.log('Failed to load comments.');
            }
         },
         error: (jqXHR, textStatus, errorThrown) => {
            console.log(`AJAX Error: ${textStatus}, ${errorThrown}`);
         }
      });
   }

   loadComments();
</script>


<?php require(base_path("/frontend/views/partials/footer.php")); ?>