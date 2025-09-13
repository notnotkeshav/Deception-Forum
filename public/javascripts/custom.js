$(function() {
   // Refresh CAPTCHA on click
   function refreshCaptcha() {
      $('#captcha-img').attr('src', '/captcha?' + Date.now());
   }

   $('#refresh-captcha, #captcha-img').on('click', refreshCaptcha);
});
