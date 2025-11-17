$(document).ready(() => {
   const groupId = $('#groupId').val();
   const API_BASE_URL = '';
   let token = sessionStorage.getItem('token');
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
               const sortedMessages = response.details.messages.sort((a, b) => 
                  new Date(a.sentAt) - new Date(b.sentAt)
               );
               renderMessages(sortedMessages, append);
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

   const formatDate = (dateString) => {
      const date = new Date(dateString);
      const today = new Date();
      const yesterday = new Date(today);
      yesterday.setDate(yesterday.getDate() - 1);

      const isToday = date.toDateString() === today.toDateString();
      const isYesterday = date.toDateString() === yesterday.toDateString();

      if (isToday) return 'Today';
      if (isYesterday) return 'Yesterday';
      
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
   };

   const renderMessages = (messages, append = false) => {
      const messageList = $('#message-list');
      if (!append) messageList.empty();
      if (messages.length === 0) return;

      if (append) oldestMessageTime = messages[0].sentAt;
      else {
         oldestMessageTime = messages[0].sentAt;
         newestMessageTime = messages[messages.length - 1].sentAt;
      }

      const fragment = document.createDocumentFragment();
      let lastDate = null;

      messages.forEach(msg => {
         const msgDate = new Date(msg.sentAt).toDateString();
         
         // Add date separator if date changed
         if (msgDate !== lastDate) {
            const dateSeparator = document.createElement('li');
            dateSeparator.className = 'date-separator';
            dateSeparator.innerHTML = `<span>${formatDate(msg.sentAt)}</span>`;
            fragment.appendChild(dateSeparator);
            lastDate = msgDate;
         }

         const isOwner = msg.userId === sessionStorage.getItem('userId');
         const messageClass = isOwner ? 'msg-owner' : 'msg-other';
         const messageContent = msg.isDeleted 
            ? '<em style="color:#555;font-size:0.85rem;">Message deleted</em>' 
            : msg.message;
         
         const li = document.createElement('li');
         li.className = `msg-item ${messageClass}`;
         li.setAttribute('data-id', msg.id);
         li.innerHTML = `
            <div class="msg-container">
               ${!msg.isDeleted ? `
                  <div class="msg-hover-actions">
                     <button class="hover-icon copy-msg" data-message="${msg.message}" title="Copy"><i class="fa fa-copy"></i></button>
                     ${isOwner ? `
                        <button class="hover-icon edit-msg" data-id="${msg.id}" data-message="${msg.message}" title="Edit"><i class="fa fa-edit"></i></button>
                        <button class="hover-icon delete-msg" data-id="${msg.id}" title="Delete"><i class="fa fa-trash"></i></button>
                     ` : ''}
                     <button class="hover-icon vote-up" data-id="${msg.id}" title="Upvote"><i class="fa fa-thumbs-up"></i></button>
                     <button class="hover-icon vote-down" data-id="${msg.id}" title="Downvote"><i class="fa fa-thumbs-down"></i></button>
                  </div>
               ` : ''}
               <div class="msg-bubble">
                  <div class="msg-header">
                     <span class="msg-sender">${msg.username}</span>
                     <span class="msg-time">${new Date(msg.sentAt).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}</span>
                  </div>
                  <div class="msg-text">${messageContent}</div>
                  ${!msg.isDeleted ? `
                     <div class="msg-vote-count" data-upvotes="${msg.upvoteCount || 0}" data-downvotes="${msg.downvoteCount || 0}">
                        <i class="fa fa-thumbs-up"></i> ${msg.upvoteCount || 0} 
                        <i class="fa fa-thumbs-down"></i> ${msg.downvoteCount || 0}
                     </div>
                  ` : ''}
               </div>
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
         error: (xhr) => console.error(`Send error: ${xhr.responseText}`)
      });
   });

   $(document).on('click', '.copy-msg', function () {
      const message = $(this).data('message');
      navigator.clipboard.writeText(message).then(() => {
         const icon = $(this).find('i');
         icon.removeClass('fa-copy').addClass('fa-check');
         setTimeout(() => icon.removeClass('fa-check').addClass('fa-copy'), 1000);
      });
   });

   $(document).on('click', '.edit-msg', function () {
      const messageId = $(this).data('id');
      const currentMessage = $(this).data('message');
      const newMessage = prompt('Edit message:', currentMessage);
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

   $(document).on('click', '.delete-msg', function () {
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

   $(document).on('click', '.vote-up, .vote-down', function () {
      const messageId = $(this).data('id');
      const voteType = $(this).hasClass('vote-up') ? 'upvote' : 'downvote';

      $.ajax({
         url: `${API_BASE_URL}/group-chat/message/vote`,
         method: 'PUT',
         dataType: 'json',
         data: {
            messageId: messageId,
            voteType: voteType,
            action: 'vote',
            userId: sessionStorage.getItem('userId')
         },
         success: (response) => {
            if (response.success) {
               const msgBubble = $(this).closest('.msg-container');
               const voteCount = msgBubble.find('.msg-vote-count');
               voteCount.attr('data-upvotes', response.details.updatedUpvotes);
               voteCount.attr('data-downvotes', response.details.updatedDownvotes);
               voteCount.html(`
                  <i class="fa fa-thumbs-up"></i> ${response.details.updatedUpvotes} 
                  <i class="fa fa-thumbs-down"></i> ${response.details.updatedDownvotes}
               `);
            }
         }
      });
   });

   const pollNewMessages = () => {
      const chatWindow = $('#chat-window');
      const isAtBottom = chatWindow.scrollTop() + chatWindow.innerHeight() >= chatWindow[0].scrollHeight - 50;

      $.ajax({
         url: `${API_BASE_URL}/group-chat/messages/new?id=${groupId}&newestTimestamp=${encodeURIComponent(newestMessageTime || '')}`,
         method: 'GET',
         dataType: 'json',
         headers: { 'Authorization': `Bearer ${token}` },
         success: (response) => {
            if (response.success && response.details.messages?.length) {
               const newMessages = response.details.messages.sort((a, b) => 
                  new Date(a.sentAt) - new Date(b.sentAt)
               );
               newestMessageTime = newMessages[newMessages.length - 1].sentAt;
               renderMessages(newMessages, false);
               if (isAtBottom) scrollToBottom();
            }
         }
      });
   };

   fetchMessages();
   setInterval(pollNewMessages, 5000);
});
