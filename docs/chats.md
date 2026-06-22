# Chat System

## Private Chats

One-to-one conversations between two users.

### Data Model
- `privateChats`: pairs `(user1Id, user2Id)`. `isSystemChat` flag for system-generated chats.
- `privateChatMessages`: `message TEXT`, `isEdited`, `isDeleted` (soft), `upvoteCount`, `downvoteCount`, `sentAt`.
- `privateChatVotes`: unique `(messageId, userId)`.
- `chatReadStatus`: tracks `lastReadAt` per `(userId, chatId)`.

### Polling
New messages are fetched via long-polling:
```
GET /private-chat/messages/new?chatId=<id>&after=<unix>
```
The endpoint returns messages with `sentAt > after`. The JS client (`private-chat.js`) polls on a timer and on `visibilitychange`.

### Voting
`PUT /private-chat/message/vote` calls stored procedure `updatePrivateMessageVotesAndGetCounts` which toggles votes and returns updated counts atomically.

---

## Group Chats

Multi-user rooms with role-based membership.

### Data Model
- `chatGroups`: `groupName`, `createdBy`.
- `groupMembers`: role (`owner`, `admin`, `moderator`, `member`, `guest`), status (`active`, `banned`, `left`).
- `groupMessages`: soft-deletable, vote counts.
- `groupMessageVotes`: unique `(messageId, userId)`.

### Member Roles
| Role | Notes |
|------|-------|
| owner | Chat creator |
| admin | Elevated management |
| moderator | Content moderation |
| member | Default |
| guest | Read-limited (implementation-defined) |

### Polling
```
GET /group-chat/messages/new?groupId=<id>&after=<unix>
```
Handled by `group-chat.js`.

### Voting
`PUT /group-chat/message/vote` calls `updateMessageVotesAndGetCounts`.

---

## Chat Notifications

`chatNotifications` table tracks events per `(userId, chatId, eventType)` with a unique constraint. Event types: `new_message`, `message_edited`, `message_deleted`, `message_upvoted`, `message_downvoted`, `new_member`, `message_mention`.

Priority levels: `low`, `medium`, `high`. Source: `user`, `system`, `admin`. Optional `expiresAt`.

---

## Frontend

| File | Purpose |
|------|---------|
| `public/javascripts/private-chat.js` | Private chat UI, message polling, send/edit/delete |
| `public/javascripts/group-chat.js` | Group chat UI, message polling, send/edit/delete |

Both use jQuery for DOM manipulation and `fetch` for API calls. Message content is rendered as HTML (from Quill editor output) and sanitized with DOMPurify before insertion.
