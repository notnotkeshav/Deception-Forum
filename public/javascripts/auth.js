$(function () {
   // Hide the error and success blocks before each submission
   $('#error-block').empty().hide();
   $('#success-block').empty().hide();
   $('#error-block').on('click', function () {
      $('#error-block').empty().hide();
   })
   $('#success-block').on('click', function () {
      $('#success-block').empty().hide();
   })

   function deleteCookies() {
      let allCookies = document.cookie.split(';');
      for (let i = 0; i < allCookies.length; i++)
         document.cookie = allCookies[i] + "=;expires=" +
            new Date(0).toUTCString();
   }
   const token = sessionStorage.getItem('token');

   // Username generation
   $('#get_username').on('click', function (event) {
      event.preventDefault();
      $.ajax({
         url: '/username',
         method: 'GET',
         contentType: 'application/x-www-form-urlencoded',
         success(data) {
            if (data) {
               $('#username').val(data);
            }
         },
         error(xhr) {
            const errorMessage = xhr.responseJSON?.error || 'Error fetching username. Please try again.';
            $('#error-block').text(errorMessage);
            console.error('Username generation error:', xhr);
         }
      });
   });

   // Sign-out
   $('#logout').on('click', function () {
      $.ajax({
         url: '/signout',
         method: 'POST',
         contentType: 'application/x-www-form-urlencoded',
         dataType: 'json',
         headers: {
            'Authorization': `Bearer ${token}`
         },
         success(response) {
            if (response.message) {
               sessionStorage.removeItem('token');
               sessionStorage.removeItem('userId');
               sessionStorage.removeItem('user');
               deleteCookies();
               window.location.href = '/signin';
            }
         },
         error(xhr) {
            const errorMessage = xhr.responseJSON?.error || 'An error occurred during logout. Please try again.';
            $('#error-block').text(errorMessage);
            console.error('Logout error:', xhr);
         }
      });
   });

   // Sign-in form submission
   $('#signinForm').on('submit', function (e) {

      $('#error-block').empty().hide();
      $('#success-block').empty().hide();
      e.preventDefault();
      const email = $('#email').val();
      const password = $('#password').val();
      const logincode = new URLSearchParams(window.location.search).get('code');

      $.ajax({
         url: `signin?code=${encodeURIComponent(logincode)}`,
         method: 'POST',
         contentType: 'application/x-www-form-urlencoded',
         dataType: 'json',
         headers: {
            'Authorization': `Bearer ${token}`
         },
         data: {
            email,
            password
         },
         success(response) {
            if (response.success) {
               $('#success-block').text('Signin successful!')
                  .show(); // Show success block
               if (response.details.session) {
                  sessionStorage.setItem('token', response.details.session.token);
                  sessionStorage.setItem('userId', response.details.session.userId);
                  sessionStorage.setItem('user', JSON.stringify(response.details.session.user));
                  setTimeout(() => {
                     window.location.href = '/threads';
                  }, 1000);
               }
               setTimeout(() => {
                  window.location.href = '/threads';
               }, 1000);
            }
         },
         error(xhr) {
            const error = xhr.responseJSON;
            const errorMessage = error.message || 'Sign-up failed.';
            $('#error-block').text(errorMessage).show();

            console.error('Sign-up error:', error);
         }
      });
   });

   // Sign-up form submission
   $('#signupForm').on('submit', function (e) {
      e.preventDefault();

      $('#error-block').empty().hide();
      $('#success-block').empty().hide();

      const email = $('#email').val();
      const username = $('#username').val();
      const password = $('#password').val();
      const confirmPassword = $('#confirmPassword').val();
      const name = $('#name').val();
      const timezone = $('#timezone').val();
      const inviteCode = new URLSearchParams(window.location.search).get('invite');

      $.ajax({
         url: `signup?invite=${encodeURIComponent(inviteCode)}`,
         method: 'POST',
         dataType: 'json',
         contentType: 'application/x-www-form-urlencoded',
         headers: {
            'Authorization': `Bearer ${token}`
         },
         data: {
            email,
            username,
            password,
            confirmPassword,
            name,
            timezone,
            inviteCode
         },
         success(response) {
            if (response.success) {
               $('#success-block').text('Signup successful! Kindly check your mailbox.')
                  .show(); // Show success block
               setTimeout(() => {
                  window.location.href = '/threads';
               }, 1000);
            }
         },
         error(xhr) {
            const error = xhr.responseJSON;
            const errorMessage = error.message || 'Sign-up failed.';
            $('#error-block').text(errorMessage).show(); // Show error block

            if (error.details?.length > 0) {
               const errorList = $('<ul></ul>');
               error.details.forEach((message) => {
                  errorList.append(`<li>${message}</li>`);
               });
               $('#error-block').append(errorList);
            }

            console.error('Sign-up error:', xhr.responseJSON);
         }
      });
   });
});
