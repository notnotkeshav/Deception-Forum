// const API_BASE_URL = ''; 
token = sessionStorage.getItem('token');

$(document).ready(() => {
   /**
    * Utility function to handle AJAX errors
    */
   const handleAjaxError = (xhr, status, error) => {
      console.error(`[${status}] AJAX Error:`, error);
      $('#error-block').text('An unexpected error occurred. Please try again.');
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
   $('#createThread').on('submit', (event) => {
      event.preventDefault();

      const formData = $(event.currentTarget).serialize();
      console.log(formData);

      sendRequest(
         `${API_BASE_URL}/threads`,
         'POST',
         formData,
         'application/x-www-form-urlencoded',
         (response) => {
            if (response.success) {
               $('#success-block').text('Redirecting to threads...');
               setTimeout(() => (window.location.href = `/threads`), 2000);
               // console.log('Redirect suppressed for debugging.');
               // console.log(response);
            } else {
               $('#error-block').text(response.error || 'Failed to create thread.');
            }
         }
      );
   });

   /**
    * Edit Thread submission
    */
   $('#editThread').on('submit', (event) => {
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
               window.location.href = `/thread?id=${threadId}`;
            } else {
               $('#error-block').text(response.error || 'Failed to update thread.');
            }
         }
      );
   });
});
