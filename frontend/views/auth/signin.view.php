<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-5">
   <h2 class="text-center mb-4">User Sign-In</h2>
   <div class="row justify-content-center">
      <div class="col-md-6">
         <form id="signinForm" class="border p-4 shadow-sm rounded bg-light">
            <!-- Email -->
            <div class="mb-3">
               <label for="email" class="form-label">Email:</label>
               <input type="email" id="email" name="email" class="form-control" maxlength="255" required>
            </div>

            <!-- Password -->
            <div class="mb-3">
               <label for="password" class="form-label">Password:</label>
               <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <!-- Remember Me -->
            <div class="mb-3 form-check">
               <input type="checkbox" id="rememberMe" name="rememberMe" class="form-check-input">
               <label for="rememberMe" class="form-check-label">Remember Me</label>
            </div>

            <!-- Submit -->
            <div class="d-grid">
               <button type="submit" class="btn btn-primary">Sign In</button>
            </div>
         </form>

         <!-- Error and Success Blocks -->
         <div id="error-block" class="mt-3 text-danger"></div>
         <div id="success-block" class="mt-3 text-success"></div>

         <!-- Links -->
         <div class="mt-3 text-center">
            <p>Don't have an account? <a href="/signup" class="text-primary">Sign up here</a>.</p>
            <p><a href="/forgot-password" class="text-secondary">Forgot your password?</a></p>
         </div>
      </div>
   </div>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>