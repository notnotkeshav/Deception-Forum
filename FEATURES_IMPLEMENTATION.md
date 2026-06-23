# Message Features Implementation Guide

This document explains how to integrate the new message features (reactions, delete, edit, block) into your views.

---

## Features Implemented

1. **Message Reactions** — Emoji reactions (👍 😂 ❤️ 🔥 😍 😢 😡 👏)
2. **Delete Message** — Soft delete with permission checks
3. **Edit Message** — Edit with full history tracking
4. **Block Users** — Block/unblock users, hide their messages

---

## Database Schema

### New Tables
- `reactions` — Emoji reactions on threads/comments
- `chat_message_reactions` — Emoji reactions on chat messages
- `blocked_users` — User block list
- `message_edit_history` — Track all edits with previous content

### New Columns
- `comments.edited_at` — Last edit timestamp
- `comments.edit_count` — Number of edits
- `privateChatMessages.edited_at` — Last edit timestamp
- `privateChatMessages.edit_count` — Number of edits
- `groupMessages.edited_at` — Last edit timestamp
- `groupMessages.edit_count` — Number of edits

---

## Backend Controllers

### 1. Reaction Endpoint
**File:** `Backend/controllers/reactions/add.php`

**Method:** POST `/reactions/add`

**Request:**
```json
{
  "csrf_token": "...",
  "targetType": "thread|comment",
  "targetId": "uuid",
  "emoji": "👍|😂|❤️|🔥|😍|😢|😡|👏"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Reaction updated",
  "details": {
    "reactionAdded": true,
    "reactions": [
      {"emoji": "👍", "count": 3},
      {"emoji": "😂", "count": 1}
    ]
  }
}
```

### 2. Delete Comment Endpoint
**File:** `Backend/controllers/comments/delete.php`

**Method:** DELETE `/comment/delete` (or POST fallback)

**Request:**
```json
{
  "csrf_token": "...",
  "commentId": "uuid"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Comment deleted successfully"
}
```

### 3. Edit Comment Endpoint
**File:** `Backend/controllers/comments/edit.php` (UPDATED)

**Method:** PUT `/comment/edit`

**Request:**
```json
{
  "csrf_token": "...",
  "commentId": "uuid",
  "comment": "updated content..."
}
```

**Response:**
```json
{
  "success": true,
  "message": "Comment updated successfully",
  "details": {
    "comment": {
      "id": "uuid",
      "content": "updated content",
      "edited_at": "2026-06-23 12:34:56",
      "edit_count": 1
    }
  }
}
```

### 4. Block User Endpoint
**File:** `Backend/controllers/users/block.php`

**Method:** POST `/user/block`

**Request:**
```json
{
  "csrf_token": "...",
  "blockedUserId": "uuid",
  "action": "block|unblock",
  "reason": "optional reason..."
}
```

**Response:**
```json
{
  "success": true,
  "message": "User blocked successfully"
}
```

### 5. List Blocked Users
**File:** `Backend/controllers/users/blocks.php`

**Method:** GET `/user/blocks`

**Response:** Renders view with list of blocked users

---

## Backend Utilities

### MessageFeatures Class
**File:** `Backend/Utils/MessageFeatures.php`

Use in your controllers:

```php
use Backend\Utils\MessageFeatures;

// Get reactions on a comment
$reactions = MessageFeatures::getReactions('comment', $commentId);

// Check if user reacted
$hasReacted = MessageFeatures::hasUserReacted('comment', $commentId, $userId, '👍');

// Get blocked users
$blockedUsers = MessageFeatures::getBlockedUsers($userId);

// Check if user is blocked
$isBlocked = MessageFeatures::isUserBlocked($currentUserId, $otherUserId);

// Delete message with permission check
$result = MessageFeatures::deleteComment($commentId, $userId);

// Get edit history
$history = MessageFeatures::getEditHistory($messageId, 'comment');
```

---

## Frontend Integration

### 1. Include JavaScript
Add to your base template (`frontend/views/partials/footer.php`):

```html
<script src="/public/javascripts/message-features.js"></script>
```

### 2. Render Reactions (Comment Example)

In `frontend/views/partials/comments.view.php`:

```php
<?php
use Backend\Utils\MessageFeatures;

// Get reactions
$reactions = MessageFeatures::getReactions('comment', $comment['id']);

// Get current user's reactions (if logged in)
$userReacted = $_SESSION['userId'] ? 
    MessageFeatures::hasUserReacted('comment', $comment['id'], $_SESSION['userId'], '👍') : 
    false;
?>

<div class="comment-card mb-3">
    <div class="comment-content" data-message-id="<?= htmlspecialchars($comment['id']) ?>" data-content>
        <?= htmlspecialchars($comment['content']) ?>
    </div>
    
    <!-- Show if edited -->
    <?php if ($comment['edited_at']): ?>
        <small class="text-muted d-block mt-1">
            <em>Edited <?= htmlspecialchars($comment['edited_at']) ?> 
               (<?= $comment['edit_count'] ?> edits)</em>
        </small>
    <?php endif; ?>
    
    <!-- Reactions Container -->
    <div class="comment-actions mt-2">
        <div data-reactions-container="comment-<?= htmlspecialchars($comment['id']) ?>" class="reaction-container">
            <?php foreach ($reactions as $reaction): ?>
                <button class="btn btn-sm btn-outline-secondary reaction-btn ms-1"
                        data-emoji="<?= htmlspecialchars($reaction['emoji']) ?>"
                        data-target-type="comment"
                        data-target-id="<?= htmlspecialchars($comment['id']) ?>">
                    <?= htmlspecialchars($reaction['emoji']) ?> 
                    <?= $reaction['count'] ?>
                </button>
            <?php endforeach; ?>
            
            <!-- Add Reaction Button -->
            <button class="btn btn-sm btn-outline-secondary ms-1" 
                    onclick="window.messageFeatures.showEmojiPicker('comment', '<?= htmlspecialchars($comment['id']) ?>')">
                ➕
            </button>
        </div>
    </div>
    
    <!-- Action Buttons (Edit, Delete) -->
    <div class="comment-footer mt-2">
        <?php if ($_SESSION['userId'] === $comment['userId']): ?>
            <button class="btn btn-sm btn-link edit-message-btn"
                    data-message-id="<?= htmlspecialchars($comment['id']) ?>"
                    data-message-type="comment"
                    data-content="<?= htmlspecialchars($comment['content']) ?>">
                ✏️ Edit
            </button>
            
            <button class="btn btn-sm btn-link delete-message-btn text-danger"
                    data-message-id="<?= htmlspecialchars($comment['id']) ?>"
                    data-message-type="comment">
                🗑️ Delete
            </button>
        <?php elseif ($_SESSION['moderator'] ?? false): ?>
            <button class="btn btn-sm btn-link delete-message-btn text-danger"
                    data-message-id="<?= htmlspecialchars($comment['id']) ?>"
                    data-message-type="comment">
                🗑️ Delete (Mod)
            </button>
        <?php endif; ?>
    </div>
</div>
```

### 3. Block User Button (Profile Example)

In `frontend/views/profile/index.view.php`:

```php
<?php if ($_SESSION['userId'] !== $user['id']): ?>
    <button class="btn btn-danger block-user-btn"
            data-user-id="<?= htmlspecialchars($user['id']) ?>">
        🚫 Block User
    </button>
<?php endif; ?>
```

### 4. Show Edited Indicator

In any message display:

```php
<?php if ($message['edited_at']): ?>
    <small class="text-muted ms-2">
        <em title="Edited <?= htmlspecialchars($message['edited_at']) ?>">
            (edited <?= htmlspecialchars($message['edit_count']) ?>×)
        </em>
    </small>
<?php endif; ?>
```

---

## JavaScript API

### Add Reaction
```javascript
const csrfToken = document.querySelector('[name="csrf_token"]').value;
window.messageFeatures.addReaction('comment', 'comment-uuid', '👍', csrfToken);
```

### Delete Message
```javascript
window.messageFeatures.deleteMessage('message-uuid', 'comment', csrfToken);
```

### Edit Message
```javascript
window.messageFeatures.showEditForm('message-uuid', 'comment', 'current content');
```

### Block User
```javascript
window.messageFeatures.blockUser('user-uuid', 'reason optional');
```

### Unblock User
```javascript
window.messageFeatures.unblockUser('user-uuid');
```

---

## CSS Styling

Add to your stylesheet (`public/css/custom.css`):

```css
/* Reactions */
.reaction-container {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 10px;
}

.reaction-btn {
    font-size: 0.9rem;
    padding: 0.25rem 0.5rem;
}

.reaction-btn:hover {
    background-color: #e9ecef;
}

.emoji-picker {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
}

/* Edit form */
.edit-message-form {
    background-color: #f0f8ff;
    border-left: 4px solid #007bff;
}

/* Deleted message */
[data-deleted="true"] {
    opacity: 0.6;
    font-style: italic;
    color: #6c757d;
}
```

---

## Usage Examples

### Complete Comment Block

```php
<?php
use Backend\Utils\MessageFeatures;
use Backend\Utils\DOMSanitizer;

foreach ($comments as $comment) {
    $reactions = MessageFeatures::getReactions('comment', $comment['id']);
    $isBlocked = MessageFeatures::isUserBlocked($_SESSION['userId'] ?? null, $comment['userId']);
    
    if ($isBlocked) continue; // Skip blocked users
    
    $isOwner = $_SESSION['userId'] === $comment['userId'];
    $isMod = $_SESSION['moderator'] ?? false;
    ?>
    <div class="comment" data-message-id="<?= htmlspecialchars($comment['id']) ?>">
        <strong><?= htmlspecialchars($comment['username']) ?></strong>
        
        <div data-content><?= DOMPurify.sanitize($comment['content']) ?></div>
        
        <?php if ($comment['edited_at']): ?>
            <small class="text-muted">(edited)</small>
        <?php endif; ?>
        
        <!-- Reactions -->
        <div data-reactions-container="comment-<?= htmlspecialchars($comment['id']) ?>">
            <?php foreach ($reactions as $r): ?>
                <button class="reaction-btn" data-emoji="<?= $r['emoji'] ?>"...>
                    <?= $r['emoji'] ?> <?= $r['count'] ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Actions -->
        <?php if ($isOwner || $isMod): ?>
            <button class="edit-message-btn"...>Edit</button>
            <button class="delete-message-btn"...>Delete</button>
        <?php endif; ?>
        
        <!-- Block user (if not self) -->
        <?php if ($isOwner === false): ?>
            <button class="block-user-btn" data-user-id="<?= htmlspecialchars($comment['userId']) ?>">
                Block
            </button>
        <?php endif; ?>
    </div>
<?php } ?>
```

---

## Migration Checklist

- [x] Database schema created (`database/08_reactions_and_blocks.sql`)
- [x] Backend controllers created
- [x] Routes added to router
- [x] Utility class created (`Backend/Utils/MessageFeatures.php`)
- [x] Frontend JavaScript library created
- [ ] Integrate reactions into comment views
- [ ] Integrate delete/edit buttons into comment views
- [ ] Integrate block buttons into user profiles
- [ ] Style reactions and edit forms
- [ ] Test all features end-to-end
- [ ] Update API documentation
- [ ] Add to CHANGELOG

---

## Security Considerations

✅ **CSRF Protection** — All endpoints validate CSRF tokens  
✅ **Authorization** — Users can only edit/delete their own messages  
✅ **Admin Override** — Moderators can delete any message  
✅ **Input Validation** — Emoji restricted to allowed set  
✅ **SQL Injection** — All queries use prepared statements  
✅ **XSS Prevention** — Content sanitized with DOMPurify  

---

## Performance Notes

- Reactions are retrieved with a single GROUP BY query
- Edit history is optional (loaded only when needed)
- Blocked users are cached per session
- Soft deletes don't require physical removal
- No N+1 queries when listing messages

---

## Future Enhancements

- [ ] Reaction picker (vs. hardcoded emoji list)
- [ ] Message threading within reactions
- [ ] Reaction notifications
- [ ] Block list notifications
- [ ] Undo delete (within time window)
- [ ] Compare edit versions side-by-side
- [ ] Bulk block/unblock

---

## Support

For issues or questions, refer to:
- `Backend/Utils/MessageFeatures.php` — Backend API reference
- `public/javascripts/message-features.js` — Frontend API reference
- Example implementations in comment/chat views

