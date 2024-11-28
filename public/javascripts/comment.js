$(document).ready(function () {
   const createReplyQuill = new Quill('#createReplyEditor', {
      theme: 'snow',
      modules: {
         toolbar: [
            [{ header: [2, 3, false] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['bold', 'italic', 'underline'],
            ['link'],
         ],
      },
   });

   const editCommentQuill = new Quill('#editCommentEditor', {
      theme: 'snow',
      modules: {
         toolbar: [
            [{ header: [2, 3, false] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['bold', 'italic', 'underline'],
            ['link'],
         ],
      },
   });

   const loadComments = () => {
      const threadId = $('#threadId').val();

      if (!threadId) {
         console.error('Thread ID not found.');
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
               const currentUserId = sessionStorage.getItem('userId');

               const renderComment = (comment, level = 0) => {
                  const sanitizedContent = DOMPurify.sanitize(comment.content);
                  const isAuthorized = currentUserId === comment.userId.toString();

                  let commentHTML = `
                     <li id="comment-${comment.id}" style="margin-left: ${level * 20}px;">
                         <p><strong>User ID ${comment.userId} Commented at:</strong> ${comment.createdAt}</p>
                         <div>${sanitizedContent}</div>
                         <p>Upvotes: <span class="upvotes">${comment.upvoteCount}</span>, Downvotes: <span class="downvotes">${comment.downvoteCount}</span></p>
                         ${isAuthorized ? `
                           <button class="edit-btn" data-comment-id="${comment.id}" data-comment="${sanitizedContent}">Edit</button>
                           <button class="delete-btn" data-comment-id="${comment.id}">Delete</button>
                         ` : ''}
                         <button class="reply-btn" data-comment-id="${comment.id}">Reply</button>
                         <button class="upvote-btn" data-comment-id="${comment.id}">Upvote</button>
                         <button class="downvote-btn" data-comment-id="${comment.id}">Downvote</button>
                  `;

                  if (comment.replies && comment.replies.length > 0) {
                     commentHTML += '<ul class="replies-list">';
                     comment.replies.forEach((reply) => {
                        commentHTML += renderComment(reply, level + 1);
                     });
                     commentHTML += '</ul>';
                  }

                  commentHTML += '</li>';
                  return commentHTML;
               };

               comments.forEach((comment) => {
                  const commentHTML = renderComment(comment);
                  commentList.append(commentHTML);
               });
            } else {
               console.error('Failed to load comments.');
            }
         },
         error: (xhr, status, error) => {
            console.error(`AJAX Error: ${status}, ${error}`);
         },
      });
   };

   $(document).on('click', '.delete-btn', function () {
      const commentId = $(this).data('comment-id');

      if (confirm('Are you sure you want to delete this comment?')) {
         $.ajax({
            url: '/comment',
            method: 'DELETE',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({ commentId }),
            success: function (response) {
               if (response.success) {
                  loadComments();
               } else {
                  console.error('Error from server:', response.error);
               }
            },
            error: function (jqXHR, textStatus, errorThrown) {
               console.error('AJAX error:', textStatus, errorThrown);
            },
         });
      }
   });

   $('#create-reply-form').on('submit', function (e) {
      e.preventDefault();

      const threadId = $('#threadId').val();
      const parentCommentId = $('#parentCommentId').val() || null;
      const comment = createReplyQuill.root.innerHTML;

      if (!threadId || !comment.trim()) {
         alert('Thread ID and comment are required.');
         return;
      }

      $.ajax({
         url: '/comment',
         method: 'POST',
         contentType: 'application/x-www-form-urlencoded',
         dataType: 'json',
         data: {
            threadId: threadId,
            parentCommentId: parentCommentId,
            content: comment,
         },
         success: (response) => {
            if (response.success) {
               loadComments();
               createReplyQuill.root.innerHTML = '';
               $('#parentCommentId').val('');
            } else {
               console.error('Failed to submit comment:', response.error);
            }
         },
         error: (xhr, status, error) => {
            console.error(`AJAX Error: ${status}, ${error}`);
         },
      });
   });

   $(document).on('click', '.reply-btn', function () {
      const commentId = $(this).data('comment-id');
      $('#parentCommentId').val(commentId);
      createReplyQuill.root.innerHTML = '';
      $('html, body').animate({
         scrollTop: $('#create-reply-form').offset().top - 100,
      }, 500);

      createReplyQuill.focus();
   });

   $(document).on('click', '.edit-btn', function () {
      const commentId = $(this).data('comment-id');
      const comment = $(this).data('comment');

      $('#editCommentId').val(commentId);
      $('#edit-comment-section').show();
      $('#create-reply-form').hide();
      $('html, body').animate({
         scrollTop: $('#edit-comment-section').offset().top - 100,
      }, 500);

      editCommentQuill.root.innerHTML = comment;
      editCommentQuill.focus();
   });

   $('#edit-comment-form').on('submit', function (e) {
      e.preventDefault();

      const commentId = $('#editCommentId').val();
      const comment = editCommentQuill.root.innerHTML;

      if (!commentId || !comment.trim()) {
         alert('Comment ID and comment are required.');
         return;
      }

      $.ajax({
         url: '/comment/edit',
         method: 'PUT',
         contentType: 'application/json',
         dataType: 'json',
         data: JSON.stringify({ commentId, comment }),
         success: (response) => {
            if (response.success) {
               loadComments();
               $('#edit-comment-section').hide();
               $('#create-reply-form').show();
               editCommentQuill.root.innerHTML = '';
            } else {
               console.error('Failed to edit comment:', response.error);
            }
         },
         error: (xhr, status, error) => {
            console.error(`AJAX Error: ${status}, ${error}`);
         },
      });
   });

   $('#cancel-edit').click(function () {
      $('#edit-comment-section').hide();
      $('#create-reply-form').show();
      $('#editCommentId').val('');
      editCommentQuill.root.innerHTML = '';
   });

   loadComments();
});
