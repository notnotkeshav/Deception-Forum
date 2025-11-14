<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>⛧ Update Your Essence ⛧</title>
   <style>
      @font-face {
         font-family: 'vamp';
         src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
      }

      body {
         background: #000;
         color: #f8f8f8;
         font-family: 'Courier New', monospace;
         margin: 0;
         padding: 0;
      }

      .hacker-container {
         max-width: 600px;
         margin: 3rem auto 2rem auto;
         background: #111;
         border: 2px solid #960d0d;
         box-shadow: 0 0 22px #a00a, 0 0 30px #a00a0a11 inset;
         padding: 2.3rem 2.5rem 2rem 2.5rem;
         border-radius: 6px;
      }

      .hacker-title {
         text-align: center;
         color: #f03;
         font-size: 2.1rem;
         font-weight: bold;
         letter-spacing: 1.2px;
         margin-bottom: 2rem;
         text-shadow: 0 0 14px rgba(255, 0, 0, 0.5);
         border-bottom: 1.5px solid #960d0d;
         padding-bottom: .8rem;
      }

      .form-group {
         margin-bottom: 1.5rem;
      }

      .form-label {
         display: block;
         color: #f03;
         font-weight: bold;
         font-size: 1.05rem;
         margin-bottom: 0.4rem;
         letter-spacing: 1px;
      }

      .form-input {
         width: 100%;
         background: #1a0000;
         border: 1.5px solid #960d0d;
         color: #fff;
         padding: 0.7rem 1rem;
         font-size: 1rem;
         font-family: 'Courier New', monospace;
         border-radius: 4px;
         transition: border 0.2s, box-shadow 0.2s;
         box-sizing: border-box;
      }

      .form-input:focus {
         outline: none;
         border-color: #f03;
         box-shadow: 0 0 12px rgba(255, 0, 51, 0.4);
      }

      .form-input::placeholder {
         color: #777;
      }

      .submit-btn {
         width: 100%;
         background: #1a0000;
         border: 2px solid #960d0d;
         color: #f03;
         font-family: 'Courier New', monospace;
         font-size: 1.3rem;
         font-weight: bold;
         padding: 0.8rem 1.5rem;
         margin-top: 1.5rem;
         border-radius: 5px;
         cursor: pointer;
         letter-spacing: 2px;
         text-transform: uppercase;
         transition: background 0.25s, color 0.25s, box-shadow 0.25s;
         box-shadow: 0 0 18px #a00a;
      }

      .submit-btn:hover {
         background: #960d0d;
         color: #fff;
         box-shadow: 0 0 22px #f03;
      }

      .alert-box {
         padding: 1rem 1.2rem;
         margin-bottom: 1.5rem;
         border-radius: 4px;
         font-size: 0.95rem;
         display: none;
         font-weight: bold;
      }

      .alert-error {
         background: #1a0000;
         border: 1.5px solid #f03;
         color: #ff3535;
      }

      .alert-success {
         background: #0a1a0a;
         border: 1.5px solid #00ff00;
         color: #00ff00;
      }

      .alert-box ul {
         margin: 0.5rem 0 0 1.2rem;
         padding: 0;
      }

      .alert-box li {
         margin-bottom: 0.3rem;
      }
   </style>
</head>
<body>

<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="hacker-container">
   <div class="hacker-title">⛧ Change Your Password</div>

   <div id="error-block" class="alert-box alert-error"></div>
   <div id="success-block" class="alert-box alert-success"></div>

   <form id="change-password-form" method="POST">
      <div class="form-group">
         <label for="oldPassword" class="form-label">Old Password</label>
         <input type="password" class="form-input" id="oldPassword" name="oldPassword" placeholder="Enter your old password" required>
      </div>

      <div class="form-group">
         <label for="newPassword" class="form-label">New Password</label>
         <input type="password" class="form-input" id="newPassword" name="newPassword" placeholder="Enter your new password" required>
      </div>

      <div class="form-group">
         <label for="confirmPassword" class="form-label">Confirm New Password</label>
         <input type="password" class="form-input" id="confirmPassword" name="confirmPassword" placeholder="Confirm your new password" required>
      </div>

      <button type="submit" class="submit-btn">Change Password</button>
   </form>
</div>

<script src="/public/javascripts/jquery-3.7.1.min.js"></script>
<script>
   function deleteCookies() {
      let allCookies = document.cookie.split(';');
      for (let i = 0; i < allCookies.length; i++)
         document.cookie = allCookies[i] + "=;expires=" + new Date(0).toUTCString();
   }

   $(document).ready(function() {
      $('#change-password-form').on('submit', function(e) {
         e.preventDefault();

         const oldPassword = $('#oldPassword').val();
         const newPassword = $('#newPassword').val();
         const confirmPassword = $('#confirmPassword').val();

         $('#error-block').hide().empty();
         $('#success-block').hide().empty();

         if (!oldPassword || !newPassword || !confirmPassword) {
            $('#error-block').text('All fields are required.').show();
            return;
         }

         $.ajax({
            url: '/change-password',
            type: 'PUT',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
               oldPassword: oldPassword,
               newPassword: newPassword,
               confirmPassword: confirmPassword
            }),
            success: function(response) {
               if (response.success) {
                  $('#success-block').text(response.message).show();
                  let loginCode = JSON.parse(sessionStorage.getItem('user'))['loginUrl'];
                  sessionStorage.clear();
                  deleteCookies();
                  setTimeout(function() {
                     window.location.href = '/signin?code=' + loginCode;
                  }, 2000);
               }
            },
            error: function(xhr) {
               const error = xhr.responseJSON;
               const errorMessage = error.message || 'Password change failed.';
               $('#error-block').text(errorMessage).show();

               if (error.details && error.details.length > 0) {
                  const errorList = $('<ul></ul>');
                  error.details.forEach(function(message) {
                     errorList.append(`<li>${message}</li>`);
                  });
                  $('#error-block').append(errorList);
               }

               console.error('Password change error:', xhr.responseJSON);
            }
         });
      });
   });
</script>

</body>
</html>
