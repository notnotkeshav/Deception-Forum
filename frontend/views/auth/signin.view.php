<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>
<h2>User Sign-In</h2>
<form action="/signin" method="POST">
   <!-- Email -->
   <label for="email">Email:</label>
   <input type="email" id="email" name="email" maxlength="255" required>
   <br><br>

   <!-- Password -->
   <label for="password">Password:</label>
   <input type="password" id="password" name="password" required>
   <br><br>

   <!-- Remember Me -->
   <label for="rememberMe">Remember Me</label>
   <input type="checkbox" id="rememberMe" name="rememberMe">
   <br><br>

   <!-- Submit -->
   <button type="submit">Sign In</button>
</form>

<!-- Link to Signup -->
<p>Don't have an account? <a href="/signup">Sign up here</a>.</p>

<!-- Forgot Password Link -->
<p><a href="/forgot-password">Forgot your password?</a></p>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>