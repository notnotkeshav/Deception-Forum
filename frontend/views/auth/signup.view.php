<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<div class="container mt-5">
   <h2 class="text-center mb-4">User Signup Form</h2>
   <div class="row justify-content-center">
      <div class="col-md-8">
         <form id="signupForm" class="border p-4 shadow-sm rounded bg-white">
            
            <!-- Username -->
            <div class="mb-3">
               <label for="username" class="form-label">Username:</label>
               <div class="input-group">
                  <input type="text" id="username" name="username" class="form-control" maxlength="25" value="<?= htmlspecialchars($username) ?>" readonly required>
                  <button type="button" id="get_username" class="btn btn-secondary">Generate Username</button>
               </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
               <label for="email" class="form-label">Email:</label>
               <input type="email" id="email" name="email" class="form-control" maxlength="255" autofocus required>
            </div>

            <!-- Full Name -->
            <div class="mb-3">
               <label for="name" class="form-label">Full Name:</label>
               <input type="text" id="name" name="name" class="form-control" maxlength="255" required>
            </div>

            <!-- Password -->
            <div class="mb-3">
               <label for="password" class="form-label">Password:</label>
               <input type="password" id="password" name="password" class="form-control" placeholder="1234567890" required>
            </div>

            <!-- Confirm Password -->
            <div class="mb-3">
               <label for="confirmPassword" class="form-label">Confirm Password:</label>
               <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" placeholder="0987654321" required>
            </div>

            <!-- Timezone -->
            <div class="mb-3">
               <label for="timezone" class="form-label">Timezone:</label>
               <select id="timezone" name="timezone" class="form-select" required>
                  <option value="GMT">GMT (Greenwich Mean Time)</option>
                  <option value="EST">EST (Eastern Standard Time)</option>
                  <option value="EDT">EDT (Eastern Daylight Time)</option>
                  <option value="CST">CST (Central Standard Time)</option>
                  <option value="CDT">CDT (Central Daylight Time)</option>
                  <option value="MST">MST (Mountain Standard Time)</option>
                  <option value="MDT">MDT (Mountain Daylight Time)</option>
                  <option value="PST">PST (Pacific Standard Time)</option>
                  <option value="PDT">PDT (Pacific Daylight Time)</option>
                  <option value="AKST">AKST (Alaska Standard Time)</option>
                  <option value="AKDT">AKDT (Alaska Daylight Time)</option>
                  <option value="HST">HST (Hawaii Standard Time)</option>
                  <option value="AST">AST (Atlantic Standard Time)</option>
                  <option value="NST">NST (Newfoundland Standard Time)</option>
                  <option value="BST">BST (British Summer Time)</option>
                  <option value="CET">CET (Central European Time)</option>
                  <option value="CEST">CEST (Central European Summer Time)</option>
                  <option value="EET">EET (Eastern European Time)</option>
                  <option value="EEST">EEST (Eastern European Summer Time)</option>
                  <option value="IST">IST (Indian Standard Time)</option>
                  <option value="CST-Asia">CST (China Standard Time)</option>
                  <option value="JST">JST (Japan Standard Time)</option>
                  <option value="KST">KST (Korea Standard Time)</option>
                  <option value="AEST">AEST (Australian Eastern Standard Time)</option>
                  <option value="AEDT">AEDT (Australian Eastern Daylight Time)</option>
                  <option value="ACST">ACST (Australian Central Standard Time)</option>
                  <option value="ACDT">ACDT (Australian Central Daylight Time)</option>
                  <option value="AWST">AWST (Australian Western Standard Time)</option>
                  <option value="NZST">NZST (New Zealand Standard Time)</option>
                  <option value="NZDT">NZDT (New Zealand Daylight Time)</option>
                  <option value="ART">ART (Argentina Time)</option>
                  <option value="BRT">BRT (Brasília Time)</option>
                  <option value="CLT">CLT (Chile Standard Time)</option>
                  <option value="WET">WET (Western European Time)</option>
                  <option value="WEST">WEST (Western European Summer Time)</option>
                  <option value="SAST">SAST (South Africa Standard Time)</option>
               </select>
            </div>

            <!-- Submit -->
            <div class="d-grid">
               <button type="submit" class="btn btn-primary">Sign Up</button>
            </div>
         </form>

         <!-- Error and Success Blocks -->
         <div id="error-block" class="mt-3 text-danger"></div>
         <div id="success-block" class="mt-3 text-success"></div>
      </div>
   </div>
</div>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>
