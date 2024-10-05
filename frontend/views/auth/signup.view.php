<?php require(base_path("/frontend/views/partials/header.php")); ?>
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>
<h2>User Signup Form</h2>


<form action="" method="POST" enctype="multipart/form-data">
   
   <!-- Username -->
   <label for="username">Username:</label>
   <input type="username" id="username" name="username" maxlength="25" value=<?= $username ?> readonly required>
   <button id="get_username">Generate Username</button>
   <br>
   <br>

   <!-- Email -->
   <label for="email">Email:</label>
   <input type="email" id="email" name="email" maxlength="255" autofocus required>
   <br><br>

   <!-- Full Name -->
   <label for="name">Full Name:</label>
   <input type="text" id="name" name="name" maxlength="255" required>
   <br><br>

   <!-- Password -->
   <label for="password">Password:</label>
   <input type="password" id="password" name="password" placeholder="1234567890" required>
   <br><br>

   <!-- Confirm Password -->
   <label for="confirmPassword">Confirm Password:</label>
   <input type="password" id="confirmPassword" name="confirmPassword" placeholder="0987654321" required>
   <br><br>


   <!-- Timezone -->
   <label for="timezone">Timezone:</label>
   <select id="timezone" name="timezone" required>
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
   <br><br>

   <!-- Submit -->
   <button type="submit">Sign Up</button>
</form>

<div class="error-block">
   <?= $error['msg'] ?? null ?>
</div>

<script src="/javascripts/generate_username.js"></script>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>