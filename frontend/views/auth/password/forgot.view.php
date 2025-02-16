<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-5">
   <h2>Forgot Your Password?</h2>

   <div id="error-block" class="alert alert-danger" style="display: none;"></div>
   <div id="success-block" class="alert alert-success" style="display: none;"></div>

   <form id="forgot-password-form">
      <div class="form-group mb-2">
         <label for="email" class="font-weight-bold">Email Address</label>
         <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
      </div>

      <button type="submit" class="btn btn-primary mt-4">Send Reset Link</button>
   </form>
</div>

<script>
   $(document).ready(function() {
      $('#forgot-password-form').on('submit', function(e) {
         e.preventDefault();

         const email = $('#email').val();

         $.ajax({
            url: '/forgot-password',
            type: 'POST',
            contentType: 'application/x-www-form-urlencoded',
            dataType: 'json',
            data: {
               email: email
            },
            success: function(response) {
               $('#error-block').empty();
               if (response.success) {
                  $('#success-block').text(response.message).show();
               }
            },
            error: function(xhr) {
               const error = xhr.responseJSON;
               const errorMessage = error.message || 'Failed to send reset link.';
               $('#error-block').text(errorMessage).show();

               if (error.details && error.details.length > 0) {
                  const errorList = $('<ul></ul>');
                  error.details.forEach(function(message) {
                     errorList.append(`<li>${message}</li>`);
                  });
                  $('#error-block').append(errorList);
               }

               console.error('Forgot password error:', xhr.responseJSON);
            }
         });
      });
   });
</script>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>