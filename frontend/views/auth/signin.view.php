<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <title>⛧ Red Skull Sign In ⛧</title>
<link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
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

      #signin-container {
         width: 100%;
         max-width: 600px;
         background: #111;
         border: 2px solid #960d0dff;
         border-radius: 0.8rem;
         padding: 2rem;
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

      label {
         color: #f03;
         font-weight: bold;
         font-size: clamp(0.9rem, 2vw, 1rem);
      }

      input {
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

      input:focus {
         outline: none;
         border-color: #f03;
      }

      .remember-row {
         display: flex;
         align-items: center;
         gap: 0.5rem;
         margin: 0.5rem 0;
      }

      .remember-row input {
         width: auto;
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

      button[type="submit"]:hover {
         background: #cd1616ff;
      }

      .links {
         display: flex;
         justify-content: space-between;
         margin-top: 1rem;
         font-size: 0.9rem;
      }

      .links a {
         color: #f03;
         text-decoration: none;
      }

      .links a:hover {
         text-decoration: underline;
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

      /* CAPTCHA Modal Styles (same as signup) */
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
   </style>
</head>

<body>
   <div class="left">
      <div id="signin-container">
         <h1>⛧ Enter The Void ⛧</h1>

         <form id="signinForm">
            <div>
               <label for="username">Username</label>
               <input type="text" id="username" required />
            </div>

            <div>
               <label for="password">Password</label>
               <input type="password" id="password" required />
            </div>

            <button type="submit" id="submit-btn">Sign In</button>

            <div class="links">
               <a href="/">No soul? Create one</a>
               <a href="/forgot-password">Forgotten your essence?</a>
            </div>
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
         // Initialize
         hideMessages();

         // Auto-hide messages after 5 seconds
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

         // CAPTCHA functionality
         let formDataToSubmit = null;

         // Open CAPTCHA modal
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
            $('#submit-btn').prop('disabled', false).text('Sign In');
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
               dataType: 'text', // ✅ always treat as text first
               contentType: 'application/x-www-form-urlencoded',
               data: formDataToSubmit.data,
               success: function(responseText, status, xhr) {
                  const contentType = xhr.getResponseHeader("Content-Type") || "";

                  if (contentType.includes("application/json")) {
                     const response = JSON.parse(responseText);

                     if (response.success) {
                        showSuccess('Signin successful! Redirecting...');

                        if (response.details?.totp_required) {
                           setTimeout(() => {
                              window.location.href = response.details.redirect || '/verify-totp';
                           }, 1000);
                           return;
                        }

                        if (response.details?.session) {
                           sessionStorage.setItem('token', response.details.session.token);
                           sessionStorage.setItem('userId', response.details.session.userId);
                           sessionStorage.setItem('user', JSON.stringify(response.details.session.user));

                           if (response.details.session.moderator !== undefined) {
                              sessionStorage.setItem('moderator', response.details.session.moderator);
                           }

                           let redirectUrl = '/threads';
                           const urlParams = new URLSearchParams(window.location.search);
                           const returnTo = urlParams.get('returnTo');
                           if (returnTo) redirectUrl = decodeURIComponent(returnTo);
                           if (response.details.redirect) redirectUrl = response.details.redirect;

                           setTimeout(() => {
                              window.location.href = redirectUrl;
                           }, 1000);
                        }
                     } else {
                        showError(response.message || "Sign-in failed.", response.details || []);
                     }
                  } else if (contentType.includes("text/html")) {
                     // ✅ If HTML, assume it's a redirect page
                     document.open();
                     document.write(responseText);
                     document.close();
                  }
               },
               error: function(xhr) {
                  showError("Sign-in failed. Unexpected server response.");
                  console.error("Raw response:", xhr.responseText);
               },
               complete: function() {
                  $('#submit-btn').prop('disabled', false).text('Sign In');
                  formDataToSubmit = null;
               }
            });
         }

         // Form submission
         $('#signinForm').on('submit', function(e) {
            e.preventDefault();
            hideMessages();

            const username = $('#username').val();
            const password = $('#password').val();
            const rememberMe = $('#rememberMe').is(':checked');
            const loginCode = new URLSearchParams(window.location.search).get('code');

            // Basic validation
            if (!username || !password) {
               showError('Both username and password are required.');
               return;
            }

            // Store form data for after CAPTCHA verification
            formDataToSubmit = {
               url: `signin?code=${encodeURIComponent(loginCode || '')}`,
               data: {
                  username,
                  password,
                  rememberMe
               }
            };

            // Show CAPTCHA modal
            $('#submit-btn').prop('disabled', true).text('Verifying...');
            openCaptchaModal();
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

         // Handle Enter key on CAPTCHA image
         $('#captcha-image').on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
               e.preventDefault();
               refreshCaptcha();
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
      });
   </script>
</body>

</html>