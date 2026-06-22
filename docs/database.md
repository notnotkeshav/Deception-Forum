# Database Schema

Database name: `forum`. Engine: InnoDB, charset: utf8mb4_unicode_ci. All IDs are `CHAR(36) UUID`.

Apply SQL files in order: `01_auth.sql` → `07_procedure.sql`.

---

## Auth (`01_auth.sql`)

### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | CHAR(36) PK | UUID default |
| profilePic | VARCHAR(255) | File URL |
| email | VARCHAR(255) UNIQUE | |
| username | VARCHAR(25) UNIQUE | |
| name | VARCHAR(255) | |
| passwordHash | VARCHAR(255) | bcrypt |
| loginUrl | VARCHAR(15) | per-user login path slug |
| accessLevel | TINYINT(2) | 1–15 |
| lastLogin | TIMESTAMP | |
| status | ENUM | `active`, `banned`, `restricted` |
| reputation | INT | |
| strikeCount | INT | |
| upgrades | ENUM | `VIP`, `PRO`, `ELITE` |
| credits | DECIMAL(10,2) | |
| timezone | VARCHAR(50) | default `UTC` |
| totp_secret | VARCHAR(32) | Base32 |
| totp_enabled | BOOLEAN | |
| totp_backup_codes | TEXT | JSON array of `{hash, used}` |
| isDeleted | BOOLEAN | soft delete |

### `passwords`
Audit log of password hashes. Stores both `passwordHash` (bcrypt) and plain `password` — **the plain storage should be reviewed and removed**.

### `inviteCodes`
`code`, `generatorId → users.id`, `used`, `usedBy → users.id`.

### `passwordResets`
`resetToken`, `expiry` (unix int), `isUsed`, `isDeleted`.

### `loginCounts`
One row per user, tracks total login count.

### `profile_privacy`
Per-user visibility flags: `show_email`, `show_name`, `show_join_date`, `show_last_login`, `show_reputation`, `show_threads`, `show_comments`, `show_stats`, `profile_visibility` (public/private).

---

## Threads (`02_threads.sql`)

### `threads`
| Column | Type | Notes |
|--------|------|-------|
| id | CHAR(36) PK | |
| title | VARCHAR(255) | |
| content | TEXT | HTML (Quill output) |
| userId | CHAR(36) FK | author |
| status | ENUM | `closed`, `open`, `archived`, `pinned` |
| isDeleted | BOOLEAN | |
| viewsCount, upvoteCount, downvoteCount | INT | |
| locked | BOOLEAN | |
| lockedBy | CHAR(36) FK | moderator |

Indexes: `(userId, status)`, `(createdAt)`.

### `categories`, `threadCategoryLink`
Many-to-many thread categorisation.

### `threadImages`
Attached image URLs per thread.

### `threadVotes`
Unique `(threadId, userId)`. Vote toggling handled by stored procedure `updateThreadVotesAndGetCounts`.

---

## Comments (`03_comments.sql`)

### `comments`
Self-referential: `parentCommentId → comments.id` (ON DELETE CASCADE). Supports unlimited nesting depth; UI limits to 5 before redirecting.

### `commentVotes`
Unique `(commentId, userId)`. Vote toggling by stored procedure `updateCommentVotesAndGetCounts`.

---

## Chats (`04_chats.sql`)

### Group Chats
- `chatGroups`: `groupName`, `createdBy → users.id`
- `groupMembers`: role (`owner`, `admin`, `moderator`, `member`, `guest`), status (`active`, `banned`, `left`)
- `groupMessages`: `message TEXT`, soft-delete, vote counts
- `groupMessageVotes`: unique `(messageId, userId)`, procedure-backed

### Private Chats
- `privateChats`: `user1Id`, `user2Id`, `isSystemChat` flag
- `privateChatMessages`: soft-delete, vote counts
- `privateChatVotes`: unique `(messageId, userId)`, procedure-backed
- `chatReadStatus`: `(userId, chatId)` last-read tracking

### `chatNotifications`
Event-based notifications for chat activity. Types: `new_message`, `message_edited`, `message_deleted`, `message_upvoted`, `message_downvoted`, `new_member`, `message_mention`. Unique `(userId, chatId, eventType)`.

---

## Moderators (`05_moderator.sql`)

### `moderators`
Links `userId → users.id`. Role: `super_admin`, `admin`, `moderator`. Status: `active`, `inactive`, `banned`.

### `Access`
One row per moderator. Granular boolean flags:
`canBanUsers`, `canDeletePosts`, `canEditPosts`, `canViewReports`, `canManageUsers`, `canCreateGroups`, `canAssignRoles`, `canPinPosts`, `canViewLogs`.

---

## Notifications (`06_notifications.sql`)

### `notifications`
| Column | Type | Notes |
|--------|------|-------|
| userId | CHAR(36) FK | recipient |
| type | ENUM | `thread_comment`, `comment_reply`, `thread_vote`, `comment_vote`, `new_thread`, `mention`, `system` |
| title | VARCHAR(255) | |
| message | TEXT | |
| data | JSON | e.g. `{"thread_id": "...", "comment_id": "..."}` |
| read_at | TIMESTAMP NULL | null = unread |

Indexes: `(userId, created_at)`, `(userId, read_at)`, `(type)`.

### `notification_settings`
One row per user. Boolean columns per notification type. Default: all enabled except `new_thread`.

---

## Stored Procedures (`07_procedure.sql`)

Four procedures following the same toggle pattern:

| Procedure | Operates on |
|-----------|------------|
| `updateCommentVotesAndGetCounts(commentId, voteType, userId)` | `commentVotes` + `comments` |
| `updateThreadVotesAndGetCounts(threadId, voteType, userId)` | `threadVotes` + `threads` |
| `updateMessageVotesAndGetCounts(messageId, voteType, userId)` | `groupMessageVotes` + `groupMessages` |
| `updatePrivateMessageVotesAndGetCounts(messageId, voteType, userId)` | `privateChatVotes` + `privateChatMessages` |

**Logic:** If user already voted the same type → delete (toggle off). If different type → update. If no vote → insert. Then recount and UPDATE the parent table's `upvoteCount`/`downvoteCount`. Returns updated counts.
