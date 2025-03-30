$(document).ready(function () {
   const currentUserId = sessionStorage.getItem('userId');
   let createReplyQuill, editCommentQuill;
   let isUserInteracting = false;
   let expandedReplies = new Set();
   let pollingTimer;

   if (document.getElementById('createReplyEditor')) {
      createReplyQuill = new Quill('#createReplyEditor', {
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
   }

   if (document.getElementById('editCommentEditor')) {
      editCommentQuill = new Quill('#editCommentEditor', {
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
   }

   // Track user interaction with comments
   $(document).on('mouseenter', '#comments-list', function () {
      isUserInteracting = true;
   });

   $(document).on('mouseleave', '#comments-list', function () {
      isUserInteracting = false;
   });

   // Track expanded replies
   const updateExpandedRepliesState = () => {
      expandedReplies.clear();
      $('.show-replies-btn').each(function () {
         const commentId = $(this).data('comment-id');
         const isExpanded = $(`#replies-for-${commentId}`).is(':visible');
         if (isExpanded) {
            expandedReplies.add(commentId);
         }
      });
   };

   const loadComments = (forceRefresh = false) => {
      const threadId = $('#thread-container').data('thread-id');

      if (!threadId) {
         console.error('Thread ID not found.');
         return;
      }

      // Don't refresh if user is interacting, unless force refresh is requested
      if (isUserInteracting && !forceRefresh) {
         return;
      }

      // Save current expanded state before refreshing
      updateExpandedRepliesState();

      $.ajax({
         url: `/comments?threadId=${threadId}`,
         method: 'GET',
         dataType: 'json',
         success: (response) => {
            if (response.success) {
               const comments = response.details.comments;
               sessionStorage.setItem(`comments-thread-${threadId}`, JSON.stringify(comments));
               renderComments(comments);

               // Restore expanded replies state
               expandedReplies.forEach(commentId => {
                  const button = $(`.show-replies-btn[data-comment-id="${commentId}"]`);
                  if (button.length) {
                     const repliesList = $(`#replies-for-${commentId}`);
                     const level = button.data('level');

                     // Load replies content if not already loaded
                     if (button.data('loaded') !== true) {
                        const parentComment = findCommentById(comments, commentId);
                        if (parentComment && parentComment.replies) {
                           parentComment.replies.forEach((reply) => {
                              const replyHTML = renderComment(reply, level);
                              repliesList.append(replyHTML);
                           });
                           button.data('loaded', true);
                        }
                     }

                     // Show the replies and update button text
                     repliesList.show();
                     button.text('Hide Replies');
                  }
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
      const locked = $('#thread-container').data('thread-locked');

      let commentHTML = `
         <li id="comment-${comment.id}" style="margin-left: ${level * 20}px;" class="list-group-item">
             <p><strong>User ID ${comment.userId} Commented at:</strong> ${comment.createdAt}</p>
             <div class="mb-2">${sanitizedContent}</div>
             <p>Upvotes: <span id="upvotes-${comment.id}">${comment.upvoteCount}</span>, Downvotes: <span id="downvotes-${comment.id}">${comment.downvoteCount}</span></p>
             ${isAuthorized && !locked ? `
               <button class="btn btn-warning btn-sm edit-btn" data-comment-id="${comment.id}" data-comment="${sanitizedContent}">Edit</button>
               <button class="btn btn-danger btn-sm delete-btn" data-comment-id="${comment.id}">Delete</button>
            ` : ''}
            ${!locked ? `
               <button class="btn btn-info btn-sm reply-btn" data-comment-id="${comment.id}">Reply</button>
               <button class="btn btn-success btn-sm upvote-btn" data-comment-id="${comment.id}">Upvote</button>
               <button class="btn btn-danger btn-sm downvote-btn" data-comment-id="${comment.id}">Downvote</button>
            ` : `
               <button disabled class="btn btn-info btn-sm">Reply</button>
               <button disabled class="btn btn-success btn-sm">Upvote</button>
               <button disabled class="btn btn-danger btn-sm">Downvote</button>
            `}
         `;

      if (comment.replies && comment.replies.length > 0) {
         commentHTML += `
            <button class="btn btn-secondary btn-sm show-replies-btn" data-comment-id="${comment.id}" data-level="${level + 1}" data-loaded="false">
               Show Replies (${comment.replies.length})
            </button>
            <ul class="list-group replies-list" id="replies-for-${comment.id}" style="display: none;"></ul>
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
      const threadId = $('#thread-container').data('thread-id');

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

         // Update expanded replies set
         expandedReplies.add(commentId);
      } else {
         if (repliesList.is(':visible')) {
            repliesList.hide();
            $(this).text(`Show Replies (${repliesList.children().length})`);
            expandedReplies.delete(commentId);
         } else {
            repliesList.show();
            $(this).text('Hide Replies');
            expandedReplies.add(commentId);
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
                  loadComments(true); // Force refresh after deletion
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

      const threadId = $('#thread-container').data('thread-id');
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
               loadComments(true); // Force refresh after post
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
               loadComments(true); // Force refresh after edit
               $('#edit-comment-section').hide();
               $('#create-reply-form').show();
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
            action: 'vote',
            commentId: commentId,
            voteType: voteType,
            userId: userId,
         }),
         success: (response) => {
            if (response.success) {
               const commentEl = $(`#comment-${commentId}`);
               commentEl.find(`#upvotes-${commentId}`).text(response.details.updatedUpvotes);
               commentEl.find(`#downvotes-${commentId}`).text(response.details.updatedDownvotes);
            } else {
               console.error('Vote failed:', response.error);
            }
         },
         error: (xhr, status, error) => {
            console.error(`AJAX Error: ${status}, ${error}`);
         },
      });
   };

   // Smart polling system
   const setupPolling = () => {
      // Initial load
      loadComments();

      // Start polling with intelligent behavior
      pollingTimer = setInterval(() => {
         // Only refresh if user is not actively interacting with comments
         if (!isUserInteracting) {
            loadComments();
         }
      }, 5000);

      // Add visibility change handling to pause polling when tab is not visible
      document.addEventListener('visibilitychange', () => {
         if (document.hidden) {
            clearInterval(pollingTimer);
         } else {
            // Resume polling when tab becomes visible again
            loadComments();
            setupPolling();
         }
      });
   };

   // Initialize the smart polling
   setupPolling();
});