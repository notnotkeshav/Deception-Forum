<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-5">
   <h2>Reset Your Password</h2>

   <div id="error-block" class="alert alert-danger" style="display: none;"></div>
   <div id="success-block" class="alert alert-success" style="display: none;"></div>

   <form id="reset-password-form" method="POST">
      <!-- Hidden token field -->
      <input type="hidden" id="token" name="token" value="<?php echo $_GET['token']; ?>">

      <div class="form-group mb-2">
         <label for="newPassword">New Password</label>
         <input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="Enter your new password" required>
      </div>

      <div class="form-group">
         <label for="confirmPassword">Confirm New Password</label>
         <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm your new password" required>
      </div>

      <button type="submit" class="btn btn-primary mt-4">Reset Password</button>
   </form>
</div>

<script>
   $(document).ready(function() {
      $('#reset-password-form').on('submit', function(e) {
         e.preventDefault();

         const token = $('#token').val();
         const newPassword = $('#newPassword').val();
         const confirmPassword = $('#confirmPassword').val();

         $.ajax({
            url: '/reset-password',
            type: 'PATCH',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
               token: token,
               newPassword: newPassword,
               confirmPassword: confirmPassword
            }),
            success: function(response) {
               $('#error-block').empty();
               if (response.success) {
                  $('#success-block').text(response.message).show();
                  setTimeout(function() {
                     window.location.href = '/signin';
                  }, 2000);
               }
            },
            error: function(xhr) {
               const error = xhr.responseJSON;
               const errorMessage = error.message || 'Password reset failed.';
               $('#error-block').text(errorMessage).show();

               if (error.details && error.details.length > 0) {
                  const errorList = $('<ul></ul>');
                  error.details.forEach(function(message) {
                     errorList.append(`<li>${message}</li>`);
                  });
                  $('#error-block').append(errorList);
               }

               console.error('Password reset error:', xhr.responseJSON);
            }
         });
      });
   });
</script>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>