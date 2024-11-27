const API_BASE_URL = 'http://localhost:8888'; 
let token = sessionStorage.getItem('token');

$(document).ready(() => {
   /**
    * Utility function to handle AJAX errors
    */
   const handleAjaxError = (xhr, status, error) => {
      console.error(`[${status}] AJAX Error:`, error);
      $('#error-block').text('An unexpected error occurred. Please try again.');
   };

   /**
    * Utility function to handle form submission
    */
   const submitForm = async (url, method, formData, successCallback) => {
      try {
         const response = await $.ajax({
            url,
            method,
            contentType: 'application/x-www-form-urlencoded',
            dataType: 'json',
            data: $.param(formData),
            headers: {
               'Authorization': `Bearer ${token}`,
            },
         });

         if (response) {
            successCallback(response);
         }
      } catch (error) {
         handleAjaxError(null, 'error', error);
      }
   };

   /**
    * Username generation
    */
   $('#get_username').on('click', async (event) => {
      event.preventDefault();
      try {
         const response = await fetch(`${API_BASE_URL}/username`, {
            method: 'GET',
            contentType: 'application/x-www-form-urlencoded',
            headers: {
               'Authorization': `Bearer ${token}`,
            },
         });
         const data = await response.text();

         if (data) {
            $('#username').val(data || '');
         } else {
            console.error('Failed to regenerate username');
         }
      } catch (error) {
         console.error('Error:', error);
         $('#error-block').text('Failed to generate username.');
      }
   });

   /**
    * Logout submission
    */
   $('#logout').on('click', async () => {
      try {
         const response = await $.ajax({
            url: `${API_BASE_URL}/signout`,
            method: 'POST',
            headers: {
               'Authorization': `Bearer ${token}`,
            },
         });

         if (response.message) {
            sessionStorage.removeItem('token');
            window.location.href = '/signin';
         } else {
            $('#error-block').text(response.error || 'Logout failed. Please try again.');
         }
      } catch (error) {
         handleAjaxError(null, 'error', error);
      }
   });

   /**
    * Sign-in form submission
    */
   $('#signinForm').on('submit', (event) => {
      event.preventDefault();

      const email = $('#email').val();
      const password = $('#password').val();
      const logincode = new URLSearchParams(window.location.search).get('code');

      if (!email || !password) {
         $('#error-block').text('Email and password are required.');
         return;
      }

      submitForm(
         `${API_BASE_URL}/signin?code=${encodeURIComponent(logincode)}`,
         'POST',
         { email, password },
         (response) => {
            if (response.session) {
               sessionStorage.setItem('token', response.session.token);
               $('#success-block').text('Redirecting to login...');
               setTimeout(() => (window.location.href = `/signin`), 2000);
            } else {
               $('#error-block').text(response.error || 'Sign-in failed.');
            }
         }
      );
   });

   /**
    * Sign-up form submission
    */
   $('#signupForm').on('submit', (event) => {
      event.preventDefault();

      const email = $('#email').val();
      const username = $('#username').val();
      const password = $('#password').val();
      const confirmPassword = $('#confirmPassword').val();
      const name = $('#name').val();
      const timezone = $('#timezone').val();
      const inviteCode = new URLSearchParams(window.location.search).get('invite');

      if (!email || !username || !password || !confirmPassword) {
         $('#error-block').text('All fields are required.');
         return;
      }

      if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
         $('#error-block').text('Invalid email format.');
         return;
      }

      if (password.length < 8) {
         $('#error-block').text('Password must be at least 8 characters long.');
         return;
      }

      if (password !== confirmPassword.split('').reverse().join('')) {
         $('#error-block').text('Passwords do not match.');
         return;
      }

      submitForm(
         `${API_BASE_URL}/signup?invite=${encodeURIComponent(inviteCode)}`,
         'POST',
         { email, username, password, confirmPassword, name, timezone, inviteCode },
         (response) => {
            if (response.success) {
               $('#success-block').text('Registration successful! Redirecting to login...');
               setTimeout(() => (window.location.href = `/signin?code=${response.loginurl}`), 2000);
            } else {
               $('#error-block').text(response.error || 'Sign-up failed.');
               if (response.messages && response.messages.length > 0) {
                  const errorList = $('<ul></ul>');
                  response.messages.forEach((message) => {
                     errorList.append(`<li>${message}</li>`);
                  });
                  $('#error-block').append(errorList);
               }
            }
         }
      );
   });
});
