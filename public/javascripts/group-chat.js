$(document).ready(() => {
   const groupId = $('#groupId').val();

   let oldestMessageTime = null;
   let newestMessageTime = null;
   let isLoading = false;
   let initialLoad = true;

   const fetchMessages = (append = false) => {
      if (isLoading) return;
      isLoading = true;

      let url = `${API_BASE_URL}/group-chat/messages?id=${groupId}&_=${new Date().getTime()}`;
      if (append && oldestMessageTime) {
         url += `&oldestTimestamp=${encodeURIComponent(oldestMessageTime)}`;
      }

      $.ajax({
         url,
         method: 'GET',
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: (response) => {
            if (response.success) {
               renderMessages(response.message.messages, append);
               if (initialLoad) {
                  scrollToBottom();
                  initialLoad = false;
               }
            }
            isLoading = false;
         },
         error: (xhr) => {
            console.error(`Error loading messages: ${xhr.status} ${xhr.statusText}`);
            isLoading = false;
         }
      });
   };

   const renderMessages = (messages, append = false) => {
      const messageList = $('#message-list');
      if (!append) messageList.empty();

      if (messages.length === 0) return;

      if (append) oldestMessageTime = messages[0].sentAt;
      else oldestMessageTime = messages[0].sentAt;

      const fragment = document.createDocumentFragment();

      messages.forEach(msg => {
         const isOwner = msg.userId === sessionStorage.getItem('userId');
         const messageClass = isOwner ? 'text-end' : 'text-start';
         const messageContent = msg.isDeleted ? '<em>This message was deleted.</em>' : msg.message;
         const controls = isOwner && !msg.isDeleted ? `
             <button class="btn btn-sm btn-warning edit-message" data-id="${msg.id}" data-message="${msg.message}">Edit</button>
             <button class="btn btn-sm btn-danger delete-message" data-id="${msg.id}">Delete</button>
          ` : '';

         // Vote buttons and counts
         const upvoteButton = `
            <button class="btn btn-sm btn-success upvote-button" data-message-id="${msg.id}" id="upvote-btn-${msg.id}">
               👍 ${msg.upvoteCount || 0}
            </button>
         `;
         const downvoteButton = `
            <button class="btn btn-sm btn-danger downvote-button" data-message-id="${msg.id}" id="downvote-btn-${msg.id}">
               👎 ${msg.downvoteCount || 0}
            </button>
         `;

         const li = document.createElement('li');
         li.className = `mb-2 ${messageClass}`;
         li.setAttribute('data-id', msg.id);
         li.innerHTML = `
             <strong>${msg.userId}</strong><br>
             <span class="badge bg-secondary">${messageContent}</span>
             <small class="text-muted d-block">${msg.sentAt}</small>
             ${controls}
             <div class="message-vote">
                ${upvoteButton} ${downvoteButton}
             </div>
          `;
         fragment.appendChild(li);
      });

      if (append) messageList.prepend(fragment);
      else {
         messageList.append(fragment);
         scrollToBottom();
      }
   };

   const scrollToBottom = () => {
      const chatWindow = $('#chat-window');
      chatWindow.scrollTop(chatWindow[0].scrollHeight);
   };

   $('#chat-window').on('scroll', function () {
      if ($(this).scrollTop() === 0 && !isLoading) {
         fetchMessages(true);
      }
   });

   $('#sendMessageForm').on('submit', function (event) {
      event.preventDefault();
      const message = $('#messageInput').val().trim();
      if (!message) return;

      $.ajax({
         url: `${API_BASE_URL}/group-chat/message`,
         method: 'POST',
         data: { groupId, message },
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: (response) => {
            if (response.success) {
               $('#messageInput').val('');
               fetchMessages(false);
            }
         },
         error: (xhr) => {
            console.error(`Send error: ${xhr.responseText}`);
         }
      });
   });

   $(document).on('click', '.edit-message', function () {
      const messageId = $(this).data('id');
      const currentMessage = $(this).data('message');
      const newMessage = prompt('Edit your message:', currentMessage);
      if (!newMessage?.trim()) return;

      $.ajax({
         url: `${API_BASE_URL}/group-chat/message`,
         method: 'PUT',
         data: { messageId, message: newMessage },
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: () => fetchMessages(false),
         error: (xhr) => console.error(`Edit error: ${xhr.responseText}`)
      });
   });

   $(document).on('click', '.delete-message', function () {
      const messageId = $(this).data('id');
      if (!confirm('Delete this message?')) return;

      $.ajax({
         url: `${API_BASE_URL}/group-chat/message`,
         method: 'DELETE',
         data: { messageId },
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: () => fetchMessages(false),
         error: (xhr) => console.error(`Delete error: ${xhr.responseText}`)
      });
   });

   $(document).on('click', '.upvote-button', function () {
      const messageId = $(this).data('message-id');
      $.ajax({
         url: `${API_BASE_URL}/group-chat/message/vote`,
         method: 'PUT',
         dataType: 'json',
         data: {
            messageId: messageId,
            voteType: 'upvote',
            action: 'vote',
            userId: sessionStorage.getItem('userId')
         },
         success: (response) => {
            if (response.success) {
               updateVoteCount(messageId, response.details);
            } else {
               alert('Failed to upvote');
            }
         },
         error: () => {
            alert('Error while voting');
         }
      });
   });

   $(document).on('click', '.downvote-button', function () {
      const messageId = $(this).data('message-id');
      $.ajax({
         url: `${API_BASE_URL}/group-chat/message/vote`,
         method: 'PUT',
         dataType: 'json',
         data: {
            messageId: messageId,
            voteType: 'downvote',
            action: 'vote',
            userId: sessionStorage.getItem('userId')
         },
         success: (response) => {
            if (response.success) {
               updateVoteCount(messageId, response.details);
            } else {
               alert('Failed to downvote');
            }
         },
         error: () => {
            alert('Error while voting');
         }
      });
   });

   const updateVoteCount = (messageId, voteCountObj) => {
      $(`#upvote-btn-${messageId}`).html(`👍 ${voteCountObj.updatedUpvotes}`);
      $(`#downvote-btn-${messageId}`).html(`👎 ${voteCountObj.updatedDownvotes}`);
   };

   const pollNewMessages = () => {
      const chatWindow = $('#chat-window');
      const isAtBottom = chatWindow.scrollTop() + chatWindow.innerHeight() >= chatWindow[0].scrollHeight - 50;

      $.ajax({
         url: `${API_BASE_URL}/group-chat/messages/new?id=${groupId}&newestTimestamp=${encodeURIComponent(newestMessageTime || '')}`,
         method: 'GET',
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: (response) => {
            if (response.success && response.message.messages?.length) {
               const newMessages = response.message.messages.sort((a, b) => new Date(a.sentAt) - new Date(b.sentAt));
               newestMessageTime = newMessages[newMessages.length - 1].sentAt;
               appendNewMessages(newMessages);
               if (isAtBottom) scrollToBottom();
               else showNewMessagesIndicator(newMessages.length);
            }
         }
      });
   };

   const appendNewMessages = (messages) => {
      const messageList = $('#message-list');
      const fragment = document.createDocumentFragment();

      messages.forEach(msg => {
         const isOwner = msg.userId === sessionStorage.getItem('userId');
         const messageClass = isOwner ? 'text-end' : 'text-start';
         const messageContent = msg.isDeleted ? '<em>This message was deleted.</em>' : msg.message;
         const controls = isOwner && !msg.isDeleted ? `
             <button class="btn btn-sm btn-warning edit-message" data-id="${msg.id}" data-message="${msg.message}">Edit</button>
             <button class="btn btn-sm btn-danger delete-message" data-id="${msg.id}">Delete</button>
          ` : '';

         const li = document.createElement('li');
         li.className = `mb-2 ${messageClass}`;
         li.setAttribute('data-id', msg.id);
         li.innerHTML = `
             <strong>${msg.username}</strong><br>
             <span class="badge bg-secondary">${messageContent}</span>
             <small class="text-muted d-block">${msg.sentAt}</small>
             ${controls}
             <div class="message-vote">
                ${upvoteButton} ${downvoteButton}
             </div>
          `;
         fragment.appendChild(li);
      });

      messageList.append(fragment);
   };

   const showNewMessagesIndicator = (count) => {
      $('#new-messages-indicator').remove();

      const indicator = $(`<div id="new-messages-indicator" class="alert alert-info text-center"
          style="position: fixed; bottom: 70px; left: 50%; transform: translateX(-50%); cursor: pointer;">
          ${count} new message${count > 1 ? 's' : ''}
       </div>`);

      indicator.on('click', () => {
         scrollToBottom();
         indicator.remove();
      });

      $('body').append(indicator);
      setTimeout(() => indicator.fadeOut('slow', () => indicator.remove()), 5000);
   };

   fetchMessages();
   setInterval(pollNewMessages, 5000);
});