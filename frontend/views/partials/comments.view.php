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

<div id="edit-reply-section" style="display: none;">
   <form id="edit-reply-form">
      <input type="hidden" id="edit-reply-comment-id" name="commentId">
      <label for="content">Edit / Reply:
         <div id="editReplyEditor" style="height: 200px; width:50%;"></div>
         <input type="hidden" id="edit-reply-content" name="content">
      </label>
      <button type="submit" id="edit-reply-submit">Submit</button>
      <button type="button" id="edit-reply-cancel">Cancel</button>
   </form>
</div>

<h2><u>Comments</u> :</h2>
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

   const editReplyQuill = new Quill('#editReplyEditor', {
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


   const loadComments = () => {
      const urlParams = new URLSearchParams(window.location.search);
      const threadId = urlParams.get('id');

      if (!threadId) {
         console.error('Thread ID not found in URL.');
         return;
      }

      $.ajax({
         url: `/comments?threadId=${threadId}`,
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
                         <p><strong>User ID ${comment.userId} Commented at:</strong> ${comment.createdAt}</p>
                         <div>${sanitizedContent}</div>
                         <p>Upvotes: <span class="upvotes">${comment.upvoteCount}</span>, Downvotes: <span class="downvotes">${comment.downvoteCount}</span></p>
                        <button class="edit-btn" data-comment-id="${comment.id}">Edit</button>
                        <button class="delete-btn" data-comment-id="${comment.id}">Delete</button>
                        <button class="reply-btn" data-comment-id="${comment.id}">Reply</button>
                        <button class="upvote-btn" data-comment-id="${comment.id}">Upvote</button>
                        <button class="downvote-btn" data-comment-id="${comment.id}">Downvote</button>
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
   };

   $('#comment-form').on('submit', function(e) {
      e.preventDefault();

      const urlParams = new URLSearchParams(window.location.search);
      const threadId = urlParams.get('id');
      const content = quill.root.innerHTML;
      console.log(threadId, content);

      if (!threadId) {
         console.error('Thread ID not found in URL.');
         return;
      }

      $.ajax({
         url: '/comment',
         method: 'POST',
         contentType: 'application/x-www-form-urlencoded',
         dataType: 'json',
         data: {
            threadId: threadId,
            content: content,
         },
         success: (response) => {
            if (response.success) {
               loadComments();
               quill.root.innerHTML = '';
            } else {
               console.error('Failed to submit comment:', response.error);
            }
         },
         error: (jqXHR, textStatus, errorThrown) => {
            console.error(`AJAX Error: ${textStatus}, ${errorThrown}`);
         }
      });
   });

   loadComments();
</script>

<script src="/public/javascripts/comment.js"></script>