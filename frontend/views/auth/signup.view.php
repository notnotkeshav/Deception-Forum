<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <title>⛧ Red Skull Sign Up ⛧</title>
   <style>
      @font-face {
         font-family: 'vamp';
         src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
      }
      * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
      }
      html,
      body {
         height: 100%;
         overflow: hidden;
      }
      body {
         background: #000;
         color: #fff;
         font-family: 'Courier New', monospace;
         display: flex;
         flex-direction: row-reverse;
         height: 100vh;
         position: relative;
      }
      .left,
      .right {
         width: 50%;
         height: 100vh;
      }
      .left {
         padding: 2rem;
         overflow-y: auto;
         overflow-x: hidden;
      }
      .right {
         display: flex;
         justify-self: center;
         align-items: center;
         background: #0a0a0a;
         overflow: hidden;
      }
      .right img {
         width: 100%;
         height: 100%;
         object-fit: cover;
      }
      #signup-container {
         width: 100%;
         max-width: 600px;
         height: 70vh;
         background: #111;
         border: 2px solid #960d0dff;
         border-radius: 0.8rem;
         padding: 0 2rem;
         position: relative;
         margin: auto;
         overflow: hidden;
      }
      h1 {
         font-family: 'vamp', sans-serif;
         font-size: clamp(1.5rem, 4vw, 2.2rem);
         text-align: center;
         color: #f03;
         margin-bottom: 1rem;
      }
      form {
         display: flex;
         flex-direction: column;
         gap: 1rem;
         height: 100%;
      }
      .username-row {
         display: flex;
         flex-direction: column;
         gap: 0;
         border-radius: 4px;
      }
      .username-row > div {
         display: flex;
         gap: 0;
         border-radius: 4px;
         overflow: hidden;
         border: 1px solid #444;
      }
      .username-row select {
         border: none;
         flex: 1;
         border-right: 1px solid #333;
         padding: 0.6rem;
         background: #000;
         color: #fff;
         font-family: inherit;
         font-size: clamp(0.85rem, 2vw, 1rem);
         appearance: none;
         -webkit-appearance: none;
         -moz-appearance: none;
      }
      .username-row select:focus {
         outline: none;
         border-color: #f03;
      }
      .username-row button {
         border: none;
         background: #c40303ff;
         padding: 0 1rem;
         font-weight: bold;
         color: white;
         cursor: pointer;
         transition: background 0.3s;
         white-space: nowrap;
         min-width: 80px;
      }
      .username-row button:hover:not(:disabled) {
         background: #cd1616ff;
      }
      .username-row button:disabled {
         background: #666;
         cursor: not-allowed;
         opacity: 0.6;
      }
      .inline-group {
         display: flex;
         justify-content: space-between;
         gap: 1rem;
      }
      label {
         color: #f03;
         font-weight: bold;
         font-size: clamp(0.9rem, 2vw, 1rem);
      }
      input,
      select {
         font-family: 'Times New Roman', Times, serif;
         padding: 0.6rem;
         background: #000;
         border: 1px solid #444;
         color: #fff;
         border-radius: 4px;
         font-family: inherit;
         width: 100%;
         font-size: clamp(0.85rem, 2vw, 1rem);
      }
      input:focus,
      select:focus {
         outline: none;
         border-color: #f03;
      }
      button[type="submit"] {
         background: #c40303ff;
         padding: 0.75rem;
         font-weight: bold;
         border: none;
         border-radius: 4px;
         color: white;
         cursor: pointer;
         transition: background 0.3s;
         font-size: clamp(0.9rem, 2vw, 1rem);
      }
      button[type="submit"]:hover:not(:disabled) {
         background: #cd1616ff;
      }
      button[type="submit"]:disabled {
         background: #666;
         cursor: not-allowed;
         opacity: 0.6;
      }
      .note {
         font-size: clamp(0.8rem, 1.5vw, 0.9rem);
         color: #cecdcdff;
         margin-bottom: 0.5rem;
      }
      .success,
      .error {
         position: fixed;
         bottom: 10px;
         left: 70%;
         transform: translateX(-50%);
         z-index: 10000;
         max-width: 90%;
         width: auto;
         min-width: 300px;
         text-align: center;
         display: none;
         padding: 1rem 1.5rem;
         border-radius: 8px;
         font-weight: bold;
         box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
         backdrop-filter: blur(10px);
      }
      .success {
         color: #0f0;
         border: 2px solid #0f0;
         background: rgba(0, 255, 0, 0.15);
      }
      .error {
         color: #f00;
         border: 2px solid #f00;
         background: rgba(255, 0, 0, 0.15);
      }
      .modal-overlay {
         display: none;
         position: fixed;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
         background: rgba(0, 0, 0, 0.8);
         z-index: 1000;
         backdrop-filter: blur(0.2rem);
      }
      .modal-content {
         position: absolute;
         top: 50%;
         left: 50%;
         transform: translate(-50%, -50%);
         background: #1a1a1a;
         border: 0.125rem solid #333;
         border-radius: 0.5rem;
         max-width: 25rem;
         width: 90%;
         color: #fff;
         box-shadow: 0 0 1.25rem rgba(255, 0, 0, 0.3);
      }
      .modal-header {
         padding: 1rem 1.25rem;
         border-bottom: 0.0625rem solid #333;
         display: flex;
         justify-content: center;
         align-items: center;
      }
      .modal-header h3 {
         margin: 0;
         color: #ff0000;
         font-size: 1rem;
         text-align: center;
      }
      .close-btn {
         margin-left: 2rem;
         background: none;
         border: none;
         color: #fff;
         font-size: 1.5rem;
         cursor: pointer;
         width: 1.875rem;
         height: 1.875rem;
         display: flex;
         align-items: center;
         justify-content: center;
      }
      .close-btn:hover {
         color: #ff0000;
      }
      .modal-body {
         padding: 1.25rem;
         text-align: center;
      }
      .modal-body p {
         margin: 0 0 1rem 0;
         font-size: 0.875rem;
      }
      .modal-loading .modal-content {
         opacity: 0.8;
      }
      .modal-loading .modal-btn {
         background: #666;
         cursor: not-allowed;
      }
      .captcha-container {
         position: relative;
         display: inline-block;
         margin: 1rem 0;
      }
      #captcha-image {
         border: 0.125rem solid #666;
         border-radius: 0.25rem;
         cursor: pointer;
         transition: border-color 0.3s ease;
         max-width: 12.5rem;
         height: 5rem;
      }
      #captcha-image:hover {
         border-color: #ff0000;
      }
      .captcha-refresh {
         position: absolute;
         top: -0.5rem;
         right: -0.5rem;
         background: #333;
         color: #fff;
         border: none;
         border-radius: 50%;
         width: 1.5rem;
         height: 1.5rem;
         cursor: pointer;
         font-size: 0.75rem;
         transition: background-color 0.3s ease;
      }
      .captcha-refresh:hover {
         background: #ff0000;
      }
      #captcha-input {
         width: 100%;
         max-width: 12.5rem;
         padding: 0.5rem 0.75rem;
         margin: 0.625rem 0;
         border: 0.0625rem solid #666;
         border-radius: 0.25rem;
         background: #333;
         color: #fff;
         text-align: center;
         text-transform: uppercase;
         letter-spacing: 0.125rem;
         font-weight: bold;
         font-size: 0.875rem;
      }
      #captcha-input:focus {
         outline: none;
         border-color: #ff0000;
      }
      .captcha-hint {
         font-size: 0.6875rem;
         color: #666;
         margin-top: 0.3125rem;
      }
      .modal-footer {
         padding: 1rem 1.25rem;
         border-top: 0.0625rem solid #333;
         display: flex;
         gap: 0.625rem;
         justify-content: center;
      }
      .modal-btn {
         padding: 0.5rem 1rem;
         border: none;
         border-radius: 0.25rem;
         cursor: pointer;
         font-size: 0.875rem;
         transition: background-color 0.3s ease;
         background: #ff0000;
         color: #fff;
      }
      .modal-btn:hover {
         background: #cc0000;
      }
      .modal-btn.cancel {
         background: #666;
      }
      .modal-btn.cancel:hover {
         background: #555;
      }
      #captcha-message {
         position: absolute;
         left: 50%;
         bottom: -2.5rem;
         padding: 0.625rem;
         border-radius: 0.25rem;
         font-size: 0.75rem;
         font-weight: bold;
         text-align: center;
         display: none;
      }
      #captcha-message.success {
         background: rgba(0, 255, 0, 0.1);
         color: #00ff00;
         border: 0.0625rem solid #00ff00;
         display: block;
      }
      #captcha-message.error {
         background: rgba(255, 0, 0, 0.1);
         color: #ff0000;
         border: 0.0625rem solid #ff0000;
         display: block;
      }
      .username-select-container {
         display: flex;
         gap: 0;
         border-radius: 4px;
         overflow: hidden;
         border: 1px solid #444;
      }
      .username-select-container select {
         flex: 1;
         padding: 0.6rem;
         background: #000;
         border: none;
         border-right: 1px solid #333;
         color: #fff;
         font-family: inherit;
         font-size: clamp(0.85rem, 2vw, 1rem);
         appearance: none;
         -webkit-appearance: none;
         -moz-appearance: none;
      }
      .username-select-container button {
         border: none;
         background: #c40303ff;
         padding: 0 1rem;
         font-weight: bold;
         color: white;
         cursor: pointer;
         transition: background 0.3s;
         white-space: nowrap;
         min-width: 100px;
      }
      .username-select-container button:hover:not(:disabled) {
         background: #cd1616ff;
      }
      .username-select-container button:disabled {
         background: #666;
         cursor: not-allowed;
         opacity: 0.6;
      }
      .rate-limit-info {
         font-size: 0.8rem;
         color: #cecdcdff;
         margin-top: 0.3rem;
      }
      .rate-limit-warning {
         color: #f03;
         font-weight: bold;
      }
      .loading {
         opacity: 0.7;
         pointer-events: none;
      }
      
      /* Ensure select dropdown shows all usernames */
      #usernameSelect {
         min-height: 2.5rem;
      }
      
      #usernameSelect option {
         background: #000;
         color: #fff;
         padding: 0.3rem;
      }
   </style>
</head>
<body>
   <div class="left">
      <div id="signup-container">
         <h1>⛧ Join Red Skull ⛧</h1>
         <form id="signupForm">
            <div class="username-row">
               <label for="usernameSelect">Username</label>
               <div class="username-select-container">
                  <select id="usernameSelect" name="username" required>
                     <option value="">Generating your first username...</option>
                  </select>
                  <button type="button" id="generateBtn">Initializing...</button>
               </div>
               <div id="rate-limit-message" class="rate-limit-info">
                  Initializing username system...
               </div>
            </div>
            <div class="inline-group">
               <div>
                  <label for="email">Email</label>
                  <input type="email" id="email" name="email" required />
                  <div class="note">Real email required – login link will be sent.</div>
               </div>
               <div>
                  <label for="name">Name (Not Real)</label>
                  <input type="text" id="name" name="name" required />
                  <div class="note">⚠️ Do not use your real name. Stay anonymous.</div>
               </div>
            </div>
            <div class="inline-group">
               <div>
                  <label for="password">Password</label>
                  <input type="password" id="password" name="password" required />
                  <div class="note">Password must be at least 25 characters long.</div>
               </div>
               <div>
                  <label for="confirm">Confirm Password</label>
                  <input type="password" id="confirm" name="confirmPassword" required />
                  <div class="note">Confirm password must be the reverse of the password.</div>
               </div>
            </div>
            <label for="timezone">Timezone</label>
            <select id="timezone" name="timezone" required>
               <option value="">Select your region</option>
               <option value="APAC">APAC (Asia-Pacific)</option>
               <option value="EMEA">EMEA (Europe, Middle East, Africa)</option>
               <option value="AMER">AMER (North & South America)</option>
               <option value="ANZ">ANZ (Australia & New Zealand)</option>
            </select>
            <button type="submit" id="submit-btn">Sign Up</button>
         </form>
         <div id="form-success" class="success"></div>
         <div id="form-error" class="error"></div>
      </div>
   </div>
   <div class="right">
      <img src="/public/images/logo.svg" alt="Red Skull Logo" />
   </div>
   
   <!-- CAPTCHA Modal -->
   <div id="captcha-modal" class="modal-overlay">
      <div class="modal-content">
         <div class="modal-header">
            <h3>⛧ PROVE YOUR MORTALITY ⛧</h3>
            <button type="button" class="close-btn" onclick="closeCaptchaModal()">&times;</button>
         </div>
         <div class="modal-body">
            <p>The void demands proof you are not a digital wraith:</p>
            <div class="captcha-container">
               <img id="captcha-image"
                  src=""
                  alt="CAPTCHA Challenge"
                  onclick="refreshCaptcha()"
                  title="Click to refresh">
               <button type="button" class="captcha-refresh" onclick="refreshCaptcha()">
                  <span>⟲</span>
               </button>
            </div>
            <input type="text"
               id="captcha-input"
               placeholder="Enter the cipher"
               autocomplete="off"
               maxlength="10">
            <div class="captcha-hint">Click image to refresh if unclear</div>
         </div>
         <div class="modal-footer">
            <button type="button" class="modal-btn" id="verify-captcha-btn" onclick="verifyCaptcha()">Verify & Continue</button>
            <button type="button" class="modal-btn cancel" onclick="closeCaptchaModal()">Cancel</button>
         </div>
         <div id="captcha-message"></div>
      </div>
   </div>

   <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
   <script>
      $(document).ready(function() {
         // State variables
         let currentUsernames = [];
         let remainingRequests = 7; // Start with 7 attempts
         let totalAttempts = 7;
         let isRateLimited = false;
         let formDataToSubmit = null;
         let isInitialized = false;
         
         // Auto-hide messages after 10 seconds
         function autoHideMessage(element) {
            setTimeout(() => {
               $(element).fadeOut();
            }, 10000);
         }
         
         // Hide all messages
         function hideMessages() {
            $('#form-error, #form-success').hide();
         }
         
         // Show success message
         function showSuccess(message) {
            hideMessages();
            $('#form-success').text(message).show();
            autoHideMessage('#form-success');
         }
         
         // Show error message
         function showError(message, details = []) {
            hideMessages();
            const $errorDiv = $('#form-error');
            $errorDiv.empty().text(message);
            if (details && details.length > 0) {
               const $errorList = $('<ul></ul>');
               details.forEach(detail => {
                  $errorList.append(`<li>${detail}</li>`);
               });
               $errorDiv.append($errorList);
            }
            $errorDiv.show();
            autoHideMessage('#form-error');
         }
         
         // Update username dropdown and UI
         function updateUsernameDropdown() {
            const $select = $('#usernameSelect');
            const $button = $('#generateBtn');
            const $container = $('.username-select-container');
            
            console.log('Updating dropdown with usernames:', currentUsernames, 'remaining:', remainingRequests);
            
            // Remove loading state
            $container.removeClass('loading');
            
            // Clear and populate select dropdown
            $select.empty();
            
            if (currentUsernames.length === 0) {
               // No usernames generated yet
               $select.append('<option value="">No username generated yet</option>');
               $select.prop('disabled', true);
            } else {
               // Add default option
               $select.append('<option value="">Choose a username</option>');
               
               // Add all generated usernames as options
               currentUsernames.forEach((username, index) => {
                  const option = $('<option></option>').val(username).text(`${index + 1}. ${username}`);
                  // Select the most recent username by default
                  if (index === currentUsernames.length - 1) {
                     option.prop('selected', true);
                  }
                  $select.append(option);
               });
               
               $select.prop('disabled', false);
            }
            
            // Update button state
            if (remainingRequests <= 0) {
               $button.text('All attempts used').prop('disabled', true);
               isRateLimited = true;
            } else {
               $button.text(`Generate (${remainingRequests} left)`).prop('disabled', false);
               isRateLimited = false;
            }
            
            // Update rate limit message
            updateRateLimitMessage();
         }
         
         // Update rate limit message
         function updateRateLimitMessage() {
            const $message = $('#rate-limit-message');
            const usedAttempts = totalAttempts - remainingRequests;
            
            console.log('Updating message - used:', usedAttempts, 'remaining:', remainingRequests, 'total usernames:', currentUsernames.length);
            
            if (!isInitialized) {
               $message.text('Initializing username system...');
               return;
            }
            
            if (currentUsernames.length === 0) {
               $message.text(`You have ${totalAttempts} attempts to generate usernames.`);
               return;
            }
            
            if (remainingRequests <= 0) {
               $message.html(
                  `<span class="rate-limit-warning">⚠️ All ${totalAttempts} attempts used.</span> Choose from your ${currentUsernames.length} generated username(s) above.`
               );
            } else {
               $message.text(`Generated ${currentUsernames.length} username(s). You have ${remainingRequests} more attempt(s) remaining.`);
            }
         }
         
         // Initialize username on page load
         function initializeUsername() {
            if (isInitialized) return;
            
            const $container = $('.username-select-container');
            const $button = $('#generateBtn');
            
            $container.addClass('loading');
            $button.text('Generating...').prop('disabled', true);
            updateRateLimitMessage();
            
            $.ajax({
               url: '/username',
               method: 'GET',
               dataType: 'json',
               timeout: 15000,
               success: function(data) {
                  console.log('Initial response:', data);
                  
                  if (data.success) {
                     // Update state from server response
                     currentUsernames = data.details.usernames || [];
                     remainingRequests = data.details.remaining_requests || 0;
                     isRateLimited = data.details.rate_limited || false;
                     isInitialized = true;
                     
                     // Always update UI after successful response
                     updateUsernameDropdown();
                     
                     if (data.is_initial && data.new_username) {
                        console.log('Initial username generated:', data.new_username);
                        showSuccess('First username generated! Choose it or generate more.');
                     }
                  } else {
                     showError(data.message || 'Failed to initialize username system');
                     $button.text('Retry').prop('disabled', false);
                     isInitialized = false;
                  }
               },
               error: function(xhr) {
                  console.error('Username initialization error:', xhr);
                  let errorMsg = 'Failed to initialize username system.';
                  
                  if (xhr.responseJSON?.message) {
                     errorMsg = xhr.responseJSON.message;
                  } else if (xhr.status === 0) {
                     errorMsg += ' Please check your internet connection.';
                  } else if (xhr.status >= 500) {
                     errorMsg += ' Server error occurred.';
                  } else {
                     errorMsg += ' Please try again.';
                  }
                  
                  showError(errorMsg);
                  $button.text('Retry').prop('disabled', false);
                  isInitialized = false;
               },
               complete: function() {
                  $container.removeClass('loading');
               }
            });
         }
         
         // Generate username function
         $('#generateBtn').on('click', function() {
            if (remainingRequests <= 0) {
               console.log('Cannot generate - no attempts left');
               showError('All 7 username generation attempts have been used.');
               return;
            }
            
            const $button = $(this);
            const $container = $('.username-select-container');
            
            // Add loading state
            $container.addClass('loading');
            $button.prop('disabled', true).text('Generating...');
            
            $.ajax({
               url: '/username',
               method: 'GET',
               dataType: 'json',
               timeout: 10000,
               success: function(data) {
                  console.log('Generation response:', data);
                  
                  if (data.success) {
                     // Update state with server response
                     currentUsernames = data.details.usernames || [];
                     remainingRequests = data.details.remaining_requests || 0;
                     isRateLimited = data.details.rate_limited || false;
                     
                     // Always update UI after successful generation
                     updateUsernameDropdown();
                     
                     // Show success message with new username
                     if (data.new_username) {
                        const attemptsLeft = remainingRequests > 0 ? ` (${remainingRequests} attempts left)` : ' (all attempts used)';
                        showSuccess(`New username generated: "${data.new_username}"${attemptsLeft}`);
                        console.log('Username generated:', data.new_username);
                     }
                  } else {
                     showError(data.message || 'Failed to generate username');
                     // Reset button state on error if attempts remain
                     if (remainingRequests > 0) {
                        $button.prop('disabled', false).text(`Generate (${remainingRequests} left)`);
                     }
                  }
               },
               error: function(xhr) {
                  console.error('Username generation error:', xhr);
                  const errorMessage = xhr.responseJSON?.message || 'Error generating username. Please try again.';
                  showError(errorMessage);
                  // Reset button state on error if attempts remain
                  if (remainingRequests > 0) {
                     $button.prop('disabled', false).text(`Generate (${remainingRequests} left)`);
                  }
               },
               complete: function() {
                  $container.removeClass('loading');
               }
            });
         });
         
         // Form submission
         $('#signupForm').on('submit', function(e) {
            e.preventDefault();
            hideMessages();
            
            const email = $('#email').val();
            const username = $('#usernameSelect').val();
            const password = $('#password').val();
            const confirmPassword = $('#confirm').val();
            const name = $('#name').val();
            const timezone = $('#timezone').val();
            const inviteCode = new URLSearchParams(window.location.search).get('invite');
            
            // Basic validation
            if (!email || !username || !password || !confirmPassword || !name || !timezone) {
               showError('Please fill in all required fields.');
               return;
            }
            
            if (password.length < 25) {
               showError('Password must be at least 25 characters long.');
               return;
            }
            
            if (confirmPassword !== password.split('').reverse().join('')) {
               showError('Confirm password must be the reverse of the password.');
               return;
            }
            
            // Check if selected username is valid (from generated list)
            if (!currentUsernames.includes(username)) {
               showError('Please select a valid generated username from the dropdown.');
               return;
            }
            
            // Store form data for after CAPTCHA verification
            formDataToSubmit = {
               url: `/signup?invite=${encodeURIComponent(inviteCode || '')}`,
               data: {
                  email,
                  username,
                  password,
                  confirmPassword,
                  name,
                  timezone,
                  inviteCode: inviteCode || ''
               }
            };
            
            // Show CAPTCHA modal
            $('#submit-btn').prop('disabled', true).text('Verifying...');
            openCaptchaModal();
         });
         
         // CAPTCHA functionality
         function openCaptchaModal() {
            const modal = $('#captcha-modal');
            const captchaInput = $('#captcha-input');
            modal.show();
            refreshCaptcha();
            setTimeout(() => {
               captchaInput.focus();
            }, 300);
         }
         
         // Close CAPTCHA modal
         window.closeCaptchaModal = function() {
            const modal = $('#captcha-modal');
            const captchaInput = $('#captcha-input');
            const messageDiv = $('#captcha-message');
            modal.hide();
            captchaInput.val('');
            messageDiv.hide().removeClass('success error');
            $('#submit-btn').prop('disabled', false).text('Sign Up');
         }
         
         // Refresh CAPTCHA
         window.refreshCaptcha = function() {
            const img = $('#captcha-image');
            const input = $('#captcha-input');
            const messageDiv = $('#captcha-message');
            input.val('');
            messageDiv.hide().removeClass('success error');
            img.attr('src', '/captcha?' + new Date().getTime());
            img.on('error', function() {
               showCaptchaMessage('CAPTCHA service unavailable. Enable GD extension in PHP.', 'error');
            });
         }
         
         // Verify CAPTCHA
         window.verifyCaptcha = async function() {
            const captchaInput = $('#captcha-input');
            const modal = $('#captcha-modal');
            const verifyBtn = $('#verify-captcha-btn');
            
            if (!captchaInput.val().trim()) {
               showCaptchaMessage('Enter the cipher to proceed.', 'error');
               captchaInput.focus();
               return;
            }
            
            // Show loading state
            modal.addClass('modal-loading');
            verifyBtn.text('Verifying...');
            
            try {
               const response = await $.ajax({
                  url: '/captcha',
                  method: 'POST',
                  data: {
                     captcha: captchaInput.val()
                  }
               });
               
               if (response.success) {
                  showCaptchaMessage('Challenge accepted. Processing...', 'success');
                  setTimeout(() => {
                     closeCaptchaModal();
                     submitFormData();
                  }, 1500);
               } else {
                  showCaptchaMessage(response.message || 'The cipher was rejected. Try again.', 'error');
                  setTimeout(refreshCaptcha, 1500);
               }
            } catch (error) {
               console.error('CAPTCHA verification error:', error);
               showCaptchaMessage('The void consumed your attempt. Try again.', 'error');
               setTimeout(refreshCaptcha, 1500);
            } finally {
               modal.removeClass('modal-loading');
               verifyBtn.text('Verify & Continue');
            }
         }
         
         // Show CAPTCHA message
         function showCaptchaMessage(text, type) {
            const messageDiv = $('#captcha-message');
            messageDiv.text(text).removeClass('success error').addClass(type).show();
         }
         
         // Submit form data after CAPTCHA verification
         function submitFormData() {
            if (!formDataToSubmit) return;
            
            $('#submit-btn').prop('disabled', true).text('Processing...');
            
            $.ajax({
               url: formDataToSubmit.url,
               method: 'POST',
               dataType: 'json',
               contentType: 'application/x-www-form-urlencoded',
               data: formDataToSubmit.data,
               success: function(response) {
                  if (response.success) {
                     showSuccess('Signup successful! Kindly check your mailbox.');
                     setTimeout(() => {
                        window.location.href = '/';
                     }, 2000);
                  }
               },
               error: function(xhr) {
                  const error = xhr.responseJSON;
                  const errorMessage = error?.message || 'Sign-up failed.';
                  const errorDetails = error?.details || [];
                  showError(errorMessage, errorDetails);
                  console.error('Sign-up error:', xhr.responseJSON);
               },
               complete: function() {
                  $('#submit-btn').prop('disabled', false).text('Sign Up');
                  formDataToSubmit = null;
               }
            });
         }
         
         // Initialize username on page load
         initializeUsername();
         
         // Click to hide messages
         $('#form-error, #form-success').on('click', function() {
            $(this).fadeOut();
         });
         
         // Auto-uppercase CAPTCHA input
         $('#captcha-input').on('input', function() {
            this.value = this.value.toUpperCase();
         });
         
         // Handle Enter key in CAPTCHA input
         $('#captcha-input').on('keydown', function(e) {
            if (e.key === 'Enter') {
               e.preventDefault();
               verifyCaptcha();
            }
         });
         
         // Close modal on Escape key
         $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
               closeCaptchaModal();
            }
         });
         
         // Close modal when clicking outside
         $('#captcha-modal').on('click', function(e) {
            if (e.target === this) {
               closeCaptchaModal();
            }
         });
         
         // Handle retry button click if initialization failed
         $(document).on('click', '#generateBtn:contains("Retry")', function() {
            isInitialized = false;
            initializeUsername();
         });
      });
   </script>
</body>
</html>