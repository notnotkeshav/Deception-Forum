/**
 * Message Features: Reactions, Delete, Edit, Block Users
 * Handles: emoji reactions, message deletion, message editing, user blocking
 */

class MessageFeatures {
    constructor() {
        this.allowedEmojis = ['👍', '😂', '❤️', '🔥', '😍', '😢', '😡', '👏'];
        this.init();
    }

    init() {
        this.attachEventListeners();
    }

    // =============================================
    // EVENT LISTENERS
    // =============================================

    attachEventListeners() {
        // Reaction buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('reaction-btn')) {
                this.handleReactionClick(e);
            }
        });

        // Delete buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-message-btn')) {
                this.handleDeleteClick(e);
            }
        });

        // Edit buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-message-btn')) {
                this.handleEditClick(e);
            }
        });

        // Block user buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('block-user-btn')) {
                this.handleBlockClick(e);
            }
        });

        // Save edit
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('edit-message-form')) {
                this.handleEditSubmit(e);
            }
        });
    }

    // =============================================
    // REACTIONS
    // =============================================

    /**
     * Handle reaction button click
     */
    handleReactionClick(e) {
        const btn = e.target;
        const emoji = btn.dataset.emoji;
        const targetType = btn.dataset.targetType; // 'thread' or 'comment'
        const targetId = btn.dataset.targetId;
        const csrfToken = this.getCsrfToken();

        this.addReaction(targetType, targetId, emoji, csrfToken);
    }

    /**
     * Send reaction to server
     */
    async addReaction(targetType, targetId, emoji, csrfToken) {
        try {
            const response = await fetch('/reactions/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    csrf_token: csrfToken,
                    targetType: targetType,
                    targetId: targetId,
                    emoji: emoji
                })
            });

            const data = await response.json();

            if (data.success) {
                this.updateReactionUI(targetType, targetId, data.details.reactions);
            } else {
                this.showAlert('Error: ' + data.message, 'danger');
            }
        } catch (error) {
            console.error('Error adding reaction:', error);
            this.showAlert('Error adding reaction', 'danger');
        }
    }

    /**
     * Update reaction UI
     */
    updateReactionUI(targetType, targetId, reactions) {
        const reactionsContainer = document.querySelector(
            `[data-reactions-container="${targetType}-${targetId}"]`
        );

        if (!reactionsContainer) return;

        // Clear existing reactions
        reactionsContainer.innerHTML = '';

        // Add updated reactions
        reactions.forEach(reaction => {
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm btn-outline-secondary reaction-btn ms-1';
            btn.dataset.emoji = reaction.emoji;
            btn.dataset.targetType = targetType;
            btn.dataset.targetId = targetId;
            btn.innerHTML = `${reaction.emoji} ${reaction.count}`;
            reactionsContainer.appendChild(btn);
        });

        // Add "Add reaction" button
        const addBtn = document.createElement('button');
        addBtn.className = 'btn btn-sm btn-outline-secondary ms-1';
        addBtn.innerHTML = '➕';
        addBtn.onclick = () => this.showEmojiPicker(targetType, targetId);
        reactionsContainer.appendChild(addBtn);
    }

    /**
     * Show emoji picker
     */
    showEmojiPicker(targetType, targetId) {
        const picker = document.createElement('div');
        picker.className = 'emoji-picker card p-2 mb-2';
        picker.innerHTML = this.allowedEmojis
            .map(emoji => `<button class="btn btn-sm btn-light me-1 reaction-btn" data-emoji="${emoji}" data-target-type="${targetType}" data-target-id="${targetId}">${emoji}</button>`)
            .join('');

        const container = document.querySelector(`[data-reactions-container="${targetType}-${targetId}"]`);
        container.parentElement.insertBefore(picker, container.nextSibling);

        // Remove after selection
        setTimeout(() => picker.remove(), 5000);
    }

    // =============================================
    // DELETE MESSAGE
    // =============================================

    /**
     * Handle delete button click
     */
    handleDeleteClick(e) {
        if (!confirm('Are you sure you want to delete this message?')) return;

        const btn = e.target;
        const messageId = btn.dataset.messageId;
        const messageType = btn.dataset.messageType; // 'comment', 'thread', etc.
        const csrfToken = this.getCsrfToken();

        this.deleteMessage(messageId, messageType, csrfToken);
    }

    /**
     * Delete message via API
     */
    async deleteMessage(messageId, messageType, csrfToken) {
        try {
            let endpoint = '/comment/delete';
            if (messageType === 'private-message') endpoint = '/private-chat/message';
            if (messageType === 'group-message') endpoint = '/group-chat/message';

            const response = await fetch(endpoint, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    csrf_token: csrfToken,
                    [messageType === 'comment' ? 'commentId' : 'messageId']: messageId
                })
            });

            const data = await response.json();

            if (data.success) {
                // Hide the message element
                const messageEl = document.querySelector(`[data-message-id="${messageId}"]`);
                if (messageEl) {
                    messageEl.style.opacity = '0.5';
                    messageEl.innerHTML = '<em>Message deleted</em>';
                }
                this.showAlert('Message deleted', 'success');
            } else {
                this.showAlert('Error: ' + data.message, 'danger');
            }
        } catch (error) {
            console.error('Error deleting message:', error);
            this.showAlert('Error deleting message', 'danger');
        }
    }

    // =============================================
    // EDIT MESSAGE
    // =============================================

    /**
     * Handle edit button click
     */
    handleEditClick(e) {
        const btn = e.target;
        const messageId = btn.dataset.messageId;
        const messageType = btn.dataset.messageType;
        const currentContent = btn.dataset.content;

        this.showEditForm(messageId, messageType, currentContent);
    }

    /**
     * Show edit form
     */
    showEditForm(messageId, messageType, currentContent) {
        const messageEl = document.querySelector(`[data-message-id="${messageId}"]`);
        if (!messageEl) return;

        const form = document.createElement('form');
        form.className = 'edit-message-form card p-3 mb-3';
        form.innerHTML = `
            <div class="mb-3">
                <textarea class="form-control" name="content" minlength="1" maxlength="65535" required>${DOMPurify.sanitize(currentContent)}</textarea>
            </div>
            <input type="hidden" name="csrf_token" value="${this.getCsrfToken()}">
            <input type="hidden" name="${messageType === 'comment' ? 'commentId' : 'messageId'}" value="${messageId}">
            <input type="hidden" name="messageType" value="${messageType}">
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
            <button type="button" class="btn btn-secondary btn-sm ms-2" onclick="this.closest('form').remove()">Cancel</button>
        `;

        messageEl.insertAdjacentElement('beforeend', form);
        form.querySelector('textarea').focus();
    }

    /**
     * Submit edited message
     */
    async handleEditSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const messageId = form.querySelector('[name="messageId"], [name="commentId"]').value;
        const messageType = form.querySelector('[name="messageType"]').value;
        const content = form.querySelector('[name="content"]').value;
        const csrfToken = form.querySelector('[name="csrf_token"]').value;

        try {
            let endpoint = '/comment/edit';
            if (messageType === 'private-message') endpoint = '/private-chat/message';
            if (messageType === 'group-message') endpoint = '/group-chat/message';

            const response = await fetch(endpoint, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    csrf_token: csrfToken,
                    [messageType === 'comment' ? 'commentId' : 'messageId']: messageId,
                    comment: content
                })
            });

            const data = await response.json();

            if (data.success) {
                // Update message content and show "edited" indicator
                const messageEl = document.querySelector(`[data-message-id="${messageId}"]`);
                if (messageEl) {
                    messageEl.querySelector('[data-content]').textContent = content;
                    const edited = document.createElement('small');
                    edited.className = 'text-muted d-block mt-1';
                    edited.innerHTML = '<em>Edited</em>';
                    messageEl.appendChild(edited);
                }
                form.remove();
                this.showAlert('Message updated', 'success');
            } else {
                this.showAlert('Error: ' + data.message, 'danger');
            }
        } catch (error) {
            console.error('Error editing message:', error);
            this.showAlert('Error editing message', 'danger');
        }
    }

    // =============================================
    // BLOCK USER
    // =============================================

    /**
     * Handle block button click
     */
    handleBlockClick(e) {
        const btn = e.target;
        const blockedUserId = btn.dataset.userId;
        const reason = prompt('Reason for blocking (optional):');

        this.blockUser(blockedUserId, reason);
    }

    /**
     * Block user via API
     */
    async blockUser(blockedUserId, reason = null) {
        try {
            const response = await fetch('/user/block', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    csrf_token: this.getCsrfToken(),
                    blockedUserId: blockedUserId,
                    action: 'block',
                    reason: reason || ''
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('User blocked successfully', 'success');
                // Optionally hide user's content from page
                document.querySelectorAll(`[data-user-id="${blockedUserId}"]`).forEach(el => {
                    el.style.opacity = '0.5';
                });
            } else {
                this.showAlert('Error: ' + data.message, 'danger');
            }
        } catch (error) {
            console.error('Error blocking user:', error);
            this.showAlert('Error blocking user', 'danger');
        }
    }

    /**
     * Unblock user
     */
    async unblockUser(blockedUserId) {
        try {
            const response = await fetch('/user/block', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    csrf_token: this.getCsrfToken(),
                    blockedUserId: blockedUserId,
                    action: 'unblock'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('User unblocked', 'success');
                return Promise.resolve(data);
            } else {
                this.showAlert('Error: ' + data.message, 'danger');
                return Promise.reject(data);
            }
        } catch (error) {
            console.error('Error unblocking user:', error);
            this.showAlert('Error unblocking user', 'danger');
            return Promise.reject(error);
        }
    }

    // =============================================
    // UTILITIES
    // =============================================

    /**
     * Get CSRF token from page
     */
    getCsrfToken() {
        return document.querySelector('[name="csrf_token"]')?.value ||
               document.querySelector('[data-csrf]')?.getAttribute('data-csrf') ||
               '';
    }

    /**
     * Show alert message
     */
    showAlert(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.insertAdjacentElement('afterbegin', alert);

        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.messageFeatures = new MessageFeatures();
});
