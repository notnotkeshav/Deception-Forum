const API_BASE_URL = '';
let token = sessionStorage.getItem('token');

$(document).ready(() => {
   /**
    * Utility function to handle AJAX errors
    */
   const handleAjaxError = (xhr, status, error) => {
      console.error(`[${status}] AJAX Error:`, error);
      if (xhr.status === 401) {
         $('#error-block').text('Unauthorized access. Please log in.');
         sessionStorage.removeItem('token'); // Clear the token on unauthorized access
         setTimeout(() => window.location.href = '/signin', 1000); // Redirect to login page
      } else {
         $('#error-block').text(xhr.responseJSON?.message || 'An unexpected error occurred. Please try again.');
      }
   };

   /**
    * Utility function to handle AJAX requests
    */
   const sendRequest = async (url, method, data, contentType, successCallback) => {
      try {
         const response = await $.ajax({
            url,
            method,
            contentType,
            dataType: 'json',
            data,
            headers: {
               'Authorization': `Bearer ${token}`,
            },
         });

         if (response) {
            successCallback(response);
         }
      } catch (xhr) {
         handleAjaxError(xhr, 'error', xhr.responseText || xhr.statusText);
      }
   };

   /**
    * Create Thread submission
    */
   $('#createThread').on('submit', function (event) {
      event.preventDefault();

      const formData = $(this).serialize();
      sendRequest(
         `${API_BASE_URL}/threads`,
         'POST',
         formData,
         'application/x-www-form-urlencoded',
         (response) => {
            if (response.success) {
               $('#success-block').text('Redirecting to threads...');
               setTimeout(() => (window.location.href = `/threads`), 1000);
            }
         }
      );
   });

   /**
    * Edit Thread submission
    */
   $('#editThread').on('submit', function (event) {
      event.preventDefault();

      const threadId = $('#threadId').val();
      const title = $('#title').val();
      const content = $('#content').val();
      const category = $('#category').val();

      const images = [];
      $('.imageUrl').each(function () {
         const imageUrl = $(this).val();
         if (imageUrl) images.push(imageUrl);
      });

      if (!title || !content || !category) {
         $('#error-block').text('Title, content, and category are required.');
         return;
      }

      const jsonData = JSON.stringify({
         title,
         content,
         category,
         images,
      });

      sendRequest(
         `${API_BASE_URL}/thread?id=${threadId}`,
         'PUT',
         jsonData,
         'application/json',
         (response) => {
            if (response.success) {
               $('#success-block').text('Refreshing to thread...');
               setTimeout(() => (window.location.href = `/thread?id=${threadId}`), 2000);
            } else {
               $('#error-block').text(response.error || 'Failed to update thread.');
            }
         }
      );
   });

   /**
    * Delete Thread submission
    */
   $('#deleteThread').on('click', function () {
      const threadId = $('#threadId').val();

      sendRequest(
         `${API_BASE_URL}/thread?id=${threadId}`,
         'DELETE',
         null,
         'application/json',
         (response) => {
            if (response.success) {
               $('#success-block').text('Redirecting to threads...');
               setTimeout(() => (window.location.href = `/threads`), 2000);
            }
         }
      );
   });

   /**
    * Vote Thread submission
    */
   $(document).on('click', '#upvote-thread', function () {
      const threadId = $(this).data('thread-id');
      handleVote(threadId, 'upvote');
   });

   $(document).on('click', '#downvote-thread', function () {
      const threadId = $(this).data('thread-id');
      handleVote(threadId, 'downvote');
   });

   const handleVote = (threadId, voteType) => {
      const userId = sessionStorage.getItem('userId');

      if (!userId) {
         alert("You must be logged in to vote.");
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
               const threadEl = $(`#thread-${threadId}`);
               threadEl.find('.upvotes').text(response.details.updatedUpvotes);
               threadEl.find('.downvotes').text(response.details.updatedDownvotes);
            } else {
               alert(response.error || 'Vote failed.');
               console.error('Vote failed:', response.error);
            }
         }
      );
   };

});
