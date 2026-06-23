<?php
/**
 * Individual Comment Item Template
 * Displays a single comment with reactions, edit, delete, and block features
 *
 * Expected variables:
 * - $comment (array): comment data
 * - $userId (string): current user ID (from session)
 * - $isModerator (bool): is current user a moderator
 * - $blockedUserIds (array): list of blocked user IDs
 * - $csrfToken (string): CSRF token
 */

use Backend\Utils\MessageFeatures;

// Skip if user is blocked
if (in_array($comment['userId'], $blockedUserIds ?? [])) {
    return;
}

// Get reactions for this comment
$reactions = MessageFeatures::getReactions('comment', $comment['id']);

// Check permissions
$isOwner = $userId === $comment['userId'];
$canEdit = $isOwner && empty($comment['isDeleted']);
$canDelete = ($isOwner || $isModerator) && empty($comment['isDeleted']);
$canReact = !empty($comment['isDeleted']);

// Format timestamps
$createdAt = new DateTime($comment['createdAt']);
$editedAt = $comment['edited_at'] ? new DateTime($comment['edited_at']) : null;
?>

<li data-message-id="<?= htmlspecialchars($comment['id']) ?>"
    class="comment-item <?= !empty($comment['isDeleted']) ? 'comment-deleted' : '' ?>">

    <!-- Comment Header -->
    <div style="margin-bottom: 0.5em;">
        <span class="comment-author"><?= htmlspecialchars($comment['username'] ?? 'Unknown User') ?></span>
        <span class="comment-meta-label">@</span>
        <span class="comment-timestamp"><?= htmlspecialchars($createdAt->format('M d, Y H:i')) ?></span>
    </div>

    <!-- Comment Content -->
    <div class="comment-content" data-content>
        <?php if (!empty($comment['isDeleted'])): ?>
            <em>This comment has been deleted</em>
        <?php else: ?>
            <?= DOMPurify.sanitize($comment['content'] ?? '') ?>
        <?php endif; ?>
    </div>

    <!-- Edited Indicator -->
    <?php if ($editedAt && !empty($comment['isDeleted']) === false): ?>
        <div class="comment-edited">
            ✏️ Edited <?= htmlspecialchars($editedAt->format('M d, Y H:i')) ?>
            <?php if ($comment['edit_count'] > 1): ?>
                (<?= htmlspecialchars($comment['edit_count']) ?> edits)
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Reactions Section -->
    <?php if (!empty($comment['isDeleted']) === false && count($reactions) > 0): ?>
        <div class="reactions-container" data-reactions-container="comment-<?= htmlspecialchars($comment['id']) ?>">
            <?php foreach ($reactions as $reaction): ?>
                <button class="reaction-btn"
                        data-emoji="<?= htmlspecialchars($reaction['emoji']) ?>"
                        data-target-type="comment"
                        data-target-id="<?= htmlspecialchars($comment['id']) ?>"
                        title="Reacted by <?= htmlspecialchars($reaction['count']) ?> user<?= $reaction['count'] != 1 ? 's' : '' ?>">
                    <?= htmlspecialchars($reaction['emoji']) ?>
                    <span><?= htmlspecialchars($reaction['count']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Comment Actions -->
    <div class="comment-actions">
        <!-- Vote Buttons (if exists in original implementation) -->
        <!-- These would be rendered by existing comment.js -->

        <!-- Add Reaction Button -->
        <?php if (!empty($comment['isDeleted']) === false): ?>
            <button class="comment-action-btn"
                    style="border-color: #9b59b6; color: #9b59b6;"
                    onclick="window.messageFeatures?.showEmojiPicker('comment', '<?= htmlspecialchars($comment['id']) ?>')"
                    title="Add emoji reaction">
                ➕ React
            </button>
        <?php endif; ?>

        <!-- Edit Button -->
        <?php if ($canEdit): ?>
            <button class="comment-action-btn edit edit-message-btn"
                    data-message-id="<?= htmlspecialchars($comment['id']) ?>"
                    data-message-type="comment"
                    data-content="<?= htmlspecialchars($comment['content'] ?? '') ?>"
                    title="Edit this comment">
                ✏️ Edit
            </button>
        <?php endif; ?>

        <!-- Delete Button -->
        <?php if ($canDelete): ?>
            <button class="comment-action-btn delete delete-message-btn"
                    data-message-id="<?= htmlspecialchars($comment['id']) ?>"
                    data-message-type="comment"
                    title="Delete this comment">
                🗑️ Delete
            </button>
        <?php endif; ?>

        <!-- Block User Button (if not self) -->
        <?php if (!$isOwner && !empty($comment['isDeleted']) === false): ?>
            <button class="comment-action-btn block block-user-btn"
                    data-user-id="<?= htmlspecialchars($comment['userId']) ?>"
                    title="Block this user">
                🚫 Block
            </button>
        <?php endif; ?>

        <!-- Reply Button (if exists in original implementation) -->
        <!-- This would be rendered by existing comment.js -->
    </div>

    <!-- Edit Form (Hidden by default, shown when edit clicked) -->
    <div id="edit-form-<?= htmlspecialchars($comment['id']) ?>"
         class="edit-comment-form"
         style="display: none;">
        <textarea placeholder="Edit your comment..."
                  minlength="1"
                  maxlength="65535"
                  id="edit-textarea-<?= htmlspecialchars($comment['id']) ?>"><?= htmlspecialchars($comment['content'] ?? '') ?></textarea>
        <div class="edit-form-actions">
            <button type="button"
                    class="save-btn"
                    onclick="saveCommentEdit('<?= htmlspecialchars($comment['id']) ?>')">
                💾 Save
            </button>
            <button type="button"
                    class="cancel-btn"
                    onclick="cancelCommentEdit('<?= htmlspecialchars($comment['id']) ?>')">
                ✕ Cancel
            </button>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    </div>

    <!-- Nested Replies -->
    <?php if (!empty($comment['replies']) && is_array($comment['replies'])): ?>
        <ul class="replies-list">
            <?php foreach ($comment['replies'] as $reply): ?>
                <?php include __DIR__ . '/_comment-item.view.php'; $comment = $reply; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</li>

<script>
/**
 * Edit comment functions
 */
function editComment(commentId) {
    const form = document.getElementById('edit-form-' + commentId);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

function saveCommentEdit(commentId) {
    const textarea = document.getElementById('edit-textarea-' + commentId);
    const csrfInput = document.querySelector('#edit-form-' + commentId + ' input[name="csrf_token"]');

    if (!textarea || !csrfInput) return;

    const newContent = textarea.value.trim();
    if (!newContent) {
        alert('Comment cannot be empty');
        return;
    }

    // Send edit request
    fetch('/comment/edit', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            csrf_token: csrfInput.value,
            commentId: commentId,
            comment: newContent
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const contentEl = document.querySelector('[data-message-id="' + commentId + '"] .comment-content');
            if (contentEl) {
                contentEl.textContent = newContent;
            }

            // Hide form
            document.getElementById('edit-form-' + commentId).style.display = 'none';

            // Show success message
            showCommentAlert('Comment updated successfully!', 'success');
        } else {
            showCommentAlert('Error: ' + (data.message || 'Failed to update comment'), 'error');
        }
    })
    .catch(err => {
        console.error('Edit error:', err);
        showCommentAlert('Error updating comment', 'error');
    });
}

function cancelCommentEdit(commentId) {
    document.getElementById('edit-form-' + commentId).style.display = 'none';
}

function showCommentAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = 'comment-alert ' + type;
    alert.innerHTML = message + '<button type="button" class="close-btn" onclick="this.parentElement.remove()">×</button>';
    document.body.insertBefore(alert, document.body.firstChild);

    setTimeout(() => alert.remove(), 5000);
}
</script>
