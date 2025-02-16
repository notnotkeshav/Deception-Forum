$(document).ready(function () {
   $('#lock-unlock-btn').on('click', function () {
      const threadId = $(this).data('thread-id');
      const isLocked = $(this).hasClass('btn-danger');
      const action = isLocked ? 'unlock' : 'lock';

      $.ajax({
         url: '/thread/lock',
         type: 'PUT',
         contentType: 'application/json',
         dataType: 'json',
         data: JSON.stringify({
            threadId: threadId
         }),
         success: function (response) {
            if (response.success) {
               if (response.locked) {
                  $('#lock-unlock-btn').removeClass('btn-success').addClass('btn-danger').text('Unlock Thread');
               } else {
                  $('#lock-unlock-btn').removeClass('btn-danger').addClass('btn-success').text('Lock Thread');
               }
               setTimeout(() => {
                  window.location.href = `/thread?id=${threadId}`;
               }, 500);
            } else {
               console.log('Failed to ' + action + ' thread.');
            }
         },
         error: function () {
            console.log('Error occurred while processing the request.');
         }
      });
   });
});
