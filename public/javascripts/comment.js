$(document).ready(function () {
   const showEditReplyForm = (commentId, content = '') => {
      $('#edit-reply-comment-id').val(commentId);
      editReplyQuill.root.innerHTML = content;
      $('#edit-reply-section').show();
   };

   $('#edit-reply-cancel').click(() => {
      $('#edit-reply-section').hide();
      $('#edit-reply-comment-id').val('');
      editReplyQuill.root.innerHTML = '';
   });

   $('#edit-reply-form').on('submit', function (e) {
      e.preventDefault();
      const commentId = $('#edit-reply-comment-id').val();
      const content = editReplyQuill.root.innerHTML;

      $.ajax({
         url: '/comment/edit',
         method: 'PUT',
         data: {
            commentId: commentId,
            content: content,
         },
         success: () => {
            $('#edit-reply-section').hide();
            loadComments();
         },
         error: () => alert('Failed to edit the comment.'),
      });
   });

   $(document).on('click', '.edit-btn', function () {
      const commentId = $(this).data('comment-id');
      const content = $(this).data('content');
      showEditReplyForm(commentId, content);
   });


   $(document).on('click', '.reply-btn', function () {
      const commentId = $(this).data('comment-id');
      showEditReplyForm(commentId);
   });

   $(document).on('click', '.delete-btn', function () {
      const commentId = $(this).data('comment-id'); // Extract comment ID
      console.log(`Attempting to delete comment ID: ${commentId}`);

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


   $(document).on('click', '.upvote-btn', function () {
      const commentId = $(this).data('comment-id');
      $.ajax({
         url: '/comment/upvote',
         method: 'PUT',
         data: { commentId: commentId },
         success: loadComments,
         error: () => alert('Failed to upvote comment.'),
      });
   });

   $(document).on('click', '.downvote-btn', function () {
      const commentId = $(this).data('comment-id');
      $.ajax({
         url: '/comment/downvote',
         method: 'PUT',
         data: { commentId: commentId },
         success: loadComments,
         error: () => alert('Failed to downvote comment.'),
      });
   });
});
