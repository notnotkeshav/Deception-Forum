<!-- change.view.php -->
<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-5">
   <h2>Change Your Password</h2>

   <div id="error-block" class="alert alert-danger" style="display: none;"></div>
   <div id="success-block" class="alert alert-success" style="display: none;"></div>

   <form id="change-password-form" method="POST">
      <div class="form-group mb-2">
         <label for="oldPassword" class="font-weight-bold">Old Password</label>
         <input type="password" class="form-control" id="oldPassword" name="oldPassword" placeholder="Enter your old password" required>
      </div>

      <div class="form-group mb-2">
         <label for="newPassword">New Password</label>
         <input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="Enter your new password" required>
      </div>

      <div class="form-group">
         <label for="confirmPassword">Confirm New Password</label>
         <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm your new password" required>
      </div>

      <button type="submit" class="btn btn-primary mt-4">Change Password</button>
   </form>
</div>

<script>
   function deleteCookies() {
      let allCookies = document.cookie.split(';');
      for (let i = 0; i < allCookies.length; i++)
         document.cookie = allCookies[i] + "=;expires=" +
         new Date(0).toUTCString();
   }

   $(document).ready(function() {
      $('#change-password-form').on('submit', function(e) {
         e.preventDefault();

         const oldPassword = $('#oldPassword').val();
         const newPassword = $('#newPassword').val();
         const confirmPassword = $('#confirmPassword').val();

         // Validate form
         if (!oldPassword || !newPassword || !confirmPassword) {
            $('#error-block').text('All fields are required.').show();
            return;
         }


         // AJAX request to change password
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
               $('#error-block').empty();
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

<?php require(base_path("/frontend/views/partials/footer.php")); ?>