$(function () {
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
            if (response.session) {
               sessionStorage.setItem('token', response.session.token);
               sessionStorage.setItem('userId', response.session.userId);
               sessionStorage.setItem('user', response.user);
               setTimeout(() => {
                  window.location.href = '/';
               }, 2000);
            }
         },
         error(xhr) {
            const errorMessage = xhr.responseJSON?.error || 'An error occurred during sign-in.';
            $('#error-block').text(errorMessage);
            console.error('Sign-in error:', xhr);
         }
      });
   });

   // Sign-up form submission
   $('#signupForm').on('submit', function (e) {
      e.preventDefault();
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
            $('#error-block').empty();
            if (response.success) {
               $('#success-block').text(`Signup successful! Login URL: ${response.loginurl}`);
            }
         },
         error(xhr) {
            $('#error-block').empty();
            const errorMessage = xhr.responseJSON?.error || 'Sign-up failed.';
            $('#error-block').text(errorMessage);

            if (xhr.responseJSON?.messages?.length > 0) {
               const errorList = $('<ul></ul>');
               xhr.responseJSON.messages.forEach((message) => {
                  errorList.append(`<li>${message}</li>`);
               });
               $('#error-block').append(errorList);
            }

            console.error('Sign-up error:', xhr);
         }
      });
   });
});
