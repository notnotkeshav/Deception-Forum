$(document).ready(function () {

   // Handle the comment submission form
   $('#comment-form').on('submit', function (event) {
      event.preventDefault();

      // Capture the form data using JQuery
      const formData = $(this).serialize();

      $.ajax({
         url: '/comment',
         method: 'POST',
         data: formData,
         success: function (data) {
            if (data.success) {
               const newComment = data.comment;
               const commentList = $('#comments-list');
               const newCommentHTML = `
                  <li id="comment-${newComment.id}">
                      <strong>User ID ${newComment.userId}</strong>
                      <p><strong>Commented at:</strong> ${newComment.createdAt}</p>
                      <p>${newComment.content}</p>
                      <button class="edit-btn" data-comment-id="${newComment.id}">Edit</button>
                      <button class="delete-btn" data-comment-id="${newComment.id}">Delete</button>
                  </li>`;

               // Append new comment to the list
               commentList.append(newCommentHTML);

               // Re-attach edit and delete handlers to the new comment
               $(`#comment-${newComment.id} .edit-btn`).on('click', function () {
                  const commentId = $(this).data('comment-id');
                  const newContent = prompt('Edit your comment:', '');
                  if (newContent !== null && newContent !== '') {
                     handleEditComment(commentId, newContent);
                  }
               });

               $(`#comment-${newComment.id} .delete-btn`).on('click', function () {
                  const commentId = $(this).data('comment-id');
                  handleDeleteComment(commentId);
               });

            } else {
               alert(data.error);
            }
         },
         error: function (xhr, status, error) {
            console.error(`Error: ${status} - ${error}`);
            alert('An error occurred while submitting your comment.');
         }
      });
   });

});
