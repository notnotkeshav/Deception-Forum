<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <title>⛧ Forge New Essence ⛧</title>
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
         display: flex;
         flex-direction: column;
         justify-content: center;
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

      #reset-container {
         width: 100%;
         max-width: 600px;
         background: #111;
         border: 2px solid #960d0dff;
         border-radius: 0.8rem;
         padding: 2rem;
         position: relative;
         margin: 0 auto;
      }

      h1 {
         font-family: 'vamp', sans-serif;
         font-size: clamp(1.5rem, 4vw, 2.2rem);
         text-align: center;
         color: #f03;
         margin-bottom: 1.5rem;
      }

      form {
         display: flex;
         flex-direction: column;
         gap: 1.5rem;
      }

      label {
         color: #f03;
         font-weight: bold;
         font-size: clamp(0.9rem, 2vw, 1rem);
         display: block;
         margin-bottom: 0.5rem;
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
         margin-top: 1rem;
      }

      button[type="submit"]:hover {
         background: #cd1616ff;
      }

      .success,
      .error {
         padding: 1rem;
         border-radius: 4px;
         margin-bottom: 1.5rem;
         font-weight: bold;
      }

      .success {
         color: #0f0;
         border: 1px solid #0f0;
         background: rgba(0, 255, 0, 0.1);
      }

      .error {
         color: #f00;
         border: 1px solid #f00;
         background: rgba(255, 0, 0, 0.1);
      }

      .error ul {
         margin-top: 0.5rem;
         padding-left: 1.5rem;
      }
   </style>
</head>

<body>
   <div class="left">
      <div id="reset-container">
         <h1>⛧ Forge New Essence ⛧</h1>

         <div id="error-block" class="error" style="display: none;"></div>
         <div id="success-block" class="success" style="display: none;"></div>

         <form id="reset-password-form">
            <input type="hidden" id="token" name="token" value="<?php echo $_GET['token']; ?>">

            <div>
               <label for="newPassword">New Password</label>
               <input type="password" id="newPassword" name="newPassword" placeholder="Conjure a new password" required>
            </div>

            <div>
               <label for="confirmPassword">Confirm New Password</label>
               <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Reaffirm your new password" required>
            </div>

            <button type="submit">Reshape Your Essence</button>
         </form>
      </div>
   </div>

   <div class="right">
      <img src="/public/images/logo.svg" alt="Red Skull Logo" />
   </div>

   <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
   <script>
      $(document).ready(function() {
         $('#reset-password-form').on('submit', function(e) {
            e.preventDefault();
            $('#error-block').hide().empty();
            $('#success-block').hide().empty();

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
                  if (response.success) {
                     $('#success-block').text(response.message).show();
                     setTimeout(function() {
                        window.location.href = '/signin';
                     }, 2000);
                  }
               },
               error: function(xhr) {
                  const error = xhr.responseJSON;
                  const errorMessage = error?.message || 'The ritual failed.';
                  const $errorDiv = $('#error-block').text(errorMessage).show();

                  if (error?.details?.length > 0) {
                     const $errorList = $('<ul></ul>');
                     error.details.forEach(detail => {
                        $errorList.append(`<li>${detail}</li>`);
                     });
                     $errorDiv.append($errorList);
                  }
               }
            });
         });
      });
   </script>
</body>

</html>