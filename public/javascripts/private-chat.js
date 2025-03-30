$(document).ready(() => {
   const chatId = $('#chatId').val();

   let oldestMessageTime = null;
   let isLoading = false;
   let initialLoad = true;

   const fetchMessages = (append = false) => {
      if (isLoading) return;
      isLoading = true;

      let url = `${API_BASE_URL}/private-chat/messages?id=${chatId}&_=${new Date().getTime()}`;
      
      // If appending messages (loading older ones), add the oldestTimestamp parameter
      if (append && oldestMessageTime) {
         url += `&oldestTimestamp=${encodeURIComponent(oldestMessageTime)}`;
      }
      
      // For initial load, we'll update newestMessageTime after loading

      $.ajax({
         url: url,
         method: 'GET',
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: (response) => {
            if (response.success) {
               renderMessages(response.message.messages, append);
               
               // If this is the initial load, scroll to the bottom
               if (initialLoad) {
                  scrollToBottom();
                  initialLoad = false;
               }
            } else {
               console.error('Error fetching messages:', response.message);
            }
            isLoading = false;
         },
         error: (xhr) => {
            console.error(`AJAX Error: ${xhr.status} ${xhr.statusText}`);
            isLoading = false;
         }
      });
   };

   const renderMessages = (messages, append = false) => {
      const messageList = $('#message-list');
      
      // If not appending, clear the list
      if (!append) {
         messageList.empty();
      }
      
      // If no messages returned, return early
      if (messages.length === 0) return;
      
      // Update the oldestMessageTime with the timestamp of the oldest message
      if (messages.length > 0 && append) {
         oldestMessageTime = messages[0].sentAt;
      } else if (messages.length > 0 && !append) {
         // For initial load, set the oldestMessageTime to the first message in the list
         oldestMessageTime = messages[0].sentAt;
      }

      // Create a document fragment to improve performance
      const fragment = document.createDocumentFragment();

      messages.forEach((msg) => {
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
            <span class="badge bg-secondary">${messageContent}</span>
            <small class="text-muted d-block">${msg.sentAt}</small>
            ${controls}
         `;
         fragment.appendChild(li);
      });

      // If appending (loading older messages), add to the beginning
      if (append) {
         messageList.prepend(fragment);
      } else {
         // Otherwise, add to the end (newer messages)
         messageList.append(fragment);
         scrollToBottom();
      }
   };

   const scrollToBottom = () => {
      const chatWindow = $('#chat-window');
      chatWindow.scrollTop(chatWindow[0].scrollHeight);
   };

   // Scroll event handler for loading older messages
   $('#chat-window').on('scroll', function() {
      if ($(this).scrollTop() === 0 && !isLoading) {
         // User has scrolled to the top, load more messages
         fetchMessages(true);
      }
   });

   $('#sendMessageForm').on('submit', function (event) {
      event.preventDefault();
      const messageInput = $('#messageInput');
      const message = messageInput.val().trim();
      if (!message) return;

      $.ajax({
         url: `${API_BASE_URL}/private-chat/message`,
         method: 'POST',
         data: { chatId: chatId, message: message },
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: (response) => {
            if (response.success) {
               messageInput.val('');
               // Only get latest messages, don't append
               fetchMessages(false);
            }
         },
         error: (xhr) => {
            console.error(`Message send error: ${xhr.responseText}`);
         }
      });
   });

   $(document).on('click', '.edit-message', function () {
      const messageId = $(this).data('id');
      const currentMessage = $(this).data('message');
      const newMessage = prompt('Edit your message:', currentMessage);
      if (!newMessage || newMessage.trim() === '') return;

      $.ajax({
         url: `${API_BASE_URL}/private-chat/message/edit`,
         method: 'PUT',
         data: { messageId: messageId, message: newMessage },
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: (response) => {
            if (response.success) {
               fetchMessages(false);
            }
         },
         error: (xhr) => {
            console.error(`Edit error: ${xhr.responseText}`);
         }
      });
   });

   $(document).on('click', '.delete-message', function () {
      const messageId = $(this).data('id');
      if (!confirm('Are you sure you want to delete this message?')) return;

      $.ajax({
         url: `${API_BASE_URL}/private-chat/message`,
         method: 'DELETE',
         data: { messageId: messageId },
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: (response) => {
            if (response.success) {
               fetchMessages(false);
            }
         },
         error: (xhr) => {
            console.error(`Delete error: ${xhr.responseText}`);
         }
      });
   });

   // Initial fetch
   fetchMessages();
   
   // Store the newest message timestamp for polling
   let newestMessageTime = null;
   
   // Smart polling function to only fetch new messages without disrupting scroll
   const pollNewMessages = () => {
      if (isLoading) return;
      
      // Don't refresh if user is scrolled up viewing older messages
      const chatWindow = $('#chat-window');
      const isAtBottom = chatWindow.scrollTop() + chatWindow.innerHeight() >= chatWindow[0].scrollHeight - 50;
      
      // Only fetch new messages
      $.ajax({
         url: `${API_BASE_URL}/private-chat/messages/new?id=${chatId}&newestTimestamp=${encodeURIComponent(newestMessageTime || '')}`,
         method: 'GET',
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: (response) => {
            if (response.success && response.message.messages && response.message.messages.length > 0) {
               // Sort messages by sentAt timestamp
               const newMessages = response.message.messages.sort((a, b) => new Date(a.sentAt) - new Date(b.sentAt));
               
               // Update the newest timestamp
               if (newMessages.length > 0) {
                  newestMessageTime = newMessages[newMessages.length - 1].sentAt;
               }
               
               // Append the new messages
               appendNewMessages(newMessages);
               
               // Only auto-scroll if the user was already at the bottom
               if (isAtBottom) {
                  scrollToBottom();
               } else {
                  // Show a "new messages" indicator if user is scrolled up
                  showNewMessagesIndicator(newMessages.length);
               }
            }
         },
         error: (xhr) => {
            console.error(`Polling Error: ${xhr.status} ${xhr.statusText}`);
         }
      });
   };
   
   // Function to append only new messages
   const appendNewMessages = (messages) => {
      const messageList = $('#message-list');
      const fragment = document.createDocumentFragment();
      
      messages.forEach((msg) => {
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
            <span class="badge bg-secondary">${messageContent}</span>
            <small class="text-muted d-block">${msg.sentAt}</small>
            ${controls}
         `;
         fragment.appendChild(li);
      });
      
      messageList.append(fragment);
   };
   
   // Function to show new messages indicator
   const showNewMessagesIndicator = (count) => {
      // Remove existing indicator if any
      $('#new-messages-indicator').remove();
      
      // Create new indicator
      const indicator = $(`<div id="new-messages-indicator" class="alert alert-info text-center" 
                          style="position: fixed; bottom: 70px; left: 50%; transform: translateX(-50%); cursor: pointer;">
                          ${count} new message${count > 1 ? 's' : ''}</div>`);
      
      // Add click handler to scroll to bottom
      indicator.on('click', () => {
         scrollToBottom();
         indicator.remove();
      });
      
      // Add to body
      $('body').append(indicator);
      
      // Auto-hide after 5 seconds
      setTimeout(() => indicator.fadeOut('slow', () => indicator.remove()), 5000);
   };
   
   // Set up polling
   setInterval(pollNewMessages, 5000);
});