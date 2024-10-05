document.addEventListener("DOMContentLoaded", function () {
   const generateButton = document.querySelector('#get_username');
   const usernameField = document.querySelector('#username');

   generateButton.addEventListener('click', function (event) {
      event.preventDefault();
      fetch('/get_username', {
         method: 'POST',
         headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
         }
      })
         .then(response => response.text())
         .then(data => {
            if (data) {
               console.log(data, data.length);
               usernameField.value = data;
            } else {
               console.error('Failed to regenerate username');
            }
         })
         .catch(error => console.error('Error:', error));
   });
});
