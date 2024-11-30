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

   const currentUserId = sessionStorage.getItem('userId');

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
               sessionStorage.setItem(`comments-thread-${threadId}`, JSON.stringify(comments));

               renderComments(comments);
            } else {
               console.error('Failed to load comments.');
            }
         },
         error: (xhr, status, error) => {
            console.error(`AJAX Error: ${status}, ${error}`);
         },
      });
   };

   const findCommentById = (comments, commentId) => {
      for (let comment of comments) {
         if (comment.id === commentId) {
            return comment;
         }
         if (comment.replies && comment.replies.length > 0) {
            const found = findCommentById(comment.replies, commentId);
            if (found) {
               return found;
            }
         }
      }
      return null;
   };

   const renderComments = (comments) => {
      const commentList = $('#comments-list');
      commentList.empty();

      comments.forEach((comment) => {
         const commentHTML = renderComment(comment);
         commentList.append(commentHTML);
      });
   };

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
         commentHTML += `
            <button class="show-replies-btn" data-comment-id="${comment.id}" data-level="${level + 1}" data-loaded="false">
               Show Replies (${comment.replies.length})
            </button>
            <ul class="replies-list" id="replies-for-${comment.id}" style="display: none;"></ul>
         `;
      }

      commentHTML += '</li>';
      return commentHTML;
   };

   $(document).on('click', '.show-replies-btn', function () {
      const commentId = $(this).data('comment-id');
      const level = $(this).data('level');
      const loaded = $(this).data('loaded');
      const repliesList = $(`#replies-for-${commentId}`);
      const threadId = $('#threadId').val();

      const allComments = JSON.parse(sessionStorage.getItem(`comments-thread-${threadId}`));
      const parentComment = findCommentById(allComments, commentId);

      if (!parentComment) {
         console.error('Parent comment not found in storage.');
         return;
      }

      if (!loaded) {
         const replies = parentComment.replies || [];
         replies.forEach((reply) => {
            const replyHTML = renderComment(reply, level);
            repliesList.append(replyHTML);
         });

         $(this).data('loaded', true);
         repliesList.show();
         $(this).text('Hide Replies');
      } else {
         if (repliesList.is(':visible')) {
            repliesList.hide();
            $(this).text(`Show Replies (${repliesList.children().length})`);
         } else {
            repliesList.show();
            $(this).text('Hide Replies');
         }
      }
   });


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

   $(document).on('click', '.upvote-btn', function () {
      const commentId = $(this).data('comment-id');
      handleVote(commentId, 'upvote');
   });

   $(document).on('click', '.downvote-btn', function () {
      const commentId = $(this).data('comment-id');
      handleVote(commentId, 'downvote');
   });

   const handleVote = (commentId, voteType) => {
      const userId = sessionStorage.getItem('userId');

      if (!userId) {
         alert("You must be logged in to vote.");
         return;
      }

      $.ajax({
         url: '/comment/vote',
         method: 'PUT',
         contentType: 'application/json',
         dataType: 'json',
         data: JSON.stringify({
            action:'vote',
            commentId: commentId,
            voteType: voteType,
            userId: userId,
         }),
         success: (response) => {
            if (response.success) {
               const commentEl = $(`#comment-${commentId}`);
               commentEl.find('.upvotes').text(response.updatedUpvotes);
               commentEl.find('.downvotes').text(response.updatedDownvotes);

               console.log(response.message);
            } else {
               console.error('Vote failed:', response.error);
            }
         },
         error: (xhr, status, error) => {
            console.error(`AJAX Error: ${status}, ${error}`);
         },
      });
   };


   loadComments();
});
