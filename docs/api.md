# API Reference

All JSON responses follow:
```json
{ "success": bool, "message": "string", "details": {} }
```

Middleware keys: `auth` = fully authenticated, `guest` = unauthenticated, `admin` = moderator session.

---

## Authentication

| Method | URI | Middleware | Description |
|--------|-----|-----------|-------------|
| GET | `/signup` | guest | Signup form |
| POST | `/signup` | guest | Create account (requires invite code `?invite=`) |
| GET | `/signin` | guest | Signin form |
| POST | `/signin` | guest | Validate credentials → partial auth |
| POST | `/signout` | auth | Destroy session |
| GET | `/totp-setup` | — | TOTP setup page |
| POST | `/totp-setup` | — | Save TOTP secret |
| GET | `/verify-totp` | — | TOTP verification page |
| POST | `/verify-totp` | — | Verify TOTP code → full session |
| GET | `/username` | guest + username_rate_limit | Generate random username suggestion |
| GET | `/generate_invite_code` | auth | Invite code form |
| POST | `/generate_invite_code` | auth | Generate new invite code |

### Password Management

| Method | URI | Middleware | Description |
|--------|-----|-----------|-------------|
| GET/PUT | `/change-password` | auth | Display form / process change |
| GET/POST | `/forgot-password` | guest | Request reset email |
| GET/PATCH | `/reset-password` | guest | Reset via token |

### Session

| Method | URI | Middleware | Description |
|--------|-----|-----------|-------------|
| GET | `/session/check` | auth | Returns `session_started`, `session_lifetime`, `time_remaining`, `will_expire_at` |
| POST | `/session/renew` | auth | Renew session via TOTP re-verification |

---

## Threads

| Method | URI | Middleware | Description |
|--------|-----|-----------|-------------|
| GET | `/threads` | auth | List all threads |
| GET | `/threads/new` | auth | Create thread form |
| POST | `/threads` | auth | Submit new thread |
| GET | `/thread?id=` | auth | View thread |
| GET | `/thread/edit?id=` | auth | Edit form |
| PUT | `/thread?id=` | auth | Update thread |
| DELETE | `/thread?id=` | auth | Delete thread |
| PUT | `/thread/vote?id=` | auth | Upvote/downvote (calls stored procedure) |
| PUT | `/thread/lock?id=` | admin | Lock/unlock thread |
| GET | `/thread/comments?id=` | auth | Paginated comment view |

---

## Comments

| Method | URI | Middleware | Description |
|--------|-----|-----------|-------------|
| GET | `/comments?threadId=` | auth | All comments for thread |
| GET | `/comments/load-more` | auth | Load more (pagination) |
| POST | `/comment` | auth | Create comment (supports `parentCommentId` for nesting) |
| PUT | `/comment/edit?id=` | auth | Edit comment |
| DELETE | `/comment?id=` | auth | Delete comment |
| PUT | `/comment/vote?id=` | auth | Upvote/downvote (calls stored procedure) |

Comments support up to 5 nesting levels. Level 6+ is redirected to a separate page.

---

## Notifications

| Method | URI | Middleware | Description |
|--------|-----|-----------|-------------|
| GET | `/notifications` | auth | Notification page |
| POST | `/notifications` | auth | AJAX notification actions |
| GET | `/notifications/poll?last_check=` | auth + accessLevel≥5 | Long-poll for new notifications since unix timestamp |
| GET | `/notifications/count` | auth | Unread count |
| POST | `/notifications/mark-read` | auth | Mark as read |
| GET/POST | `/notifications/settings` | auth | View/update settings |
| PUT | `/notifications/settings` | auth | AJAX update single setting |
| POST | `/notifications/subscribe` | auth | Subscribe to type |
| POST | `/notifications/unsubscribe` | auth | Unsubscribe from type |

**Notification types:** `thread_comment`, `comment_reply`, `thread_vote`, `comment_vote`, `new_thread`, `mention`, `system`

---

## Private Chats

| Method | URI | Middleware | Description |
|--------|-----|-----------|-------------|
| GET | `/private-chats` | auth | List all private chats |
| GET/POST | `/private-chat/new` | auth | Start new private chat |
| GET | `/private-chat?id=` | auth | View chat |
| GET | `/private-chat/messages?chatId=` | auth | Fetch messages |
| GET | `/private-chat/messages/new?chatId=&after=` | auth | Long-poll for new messages |
| POST | `/private-chat/message` | auth | Send message |
| PUT | `/private-chat/message?id=` | auth | Edit message |
| DELETE | `/private-chat/message?id=` | auth | Delete message |
| PUT | `/private-chat/message/vote?id=` | auth | Vote on message |

---

## Group Chats

| Method | URI | Middleware | Description |
|--------|-----|-----------|-------------|
| GET | `/group-chats` | auth | List all group chats |
| GET/POST | `/group-chat/new` | auth | Create group chat |
| GET | `/group-chat?id=` | auth | View group chat |
| GET | `/group-chat/messages?groupId=` | auth | Fetch messages |
| GET | `/group-chat/messages/new?groupId=&after=` | auth | Long-poll for new messages |
| POST | `/group-chat/message` | auth | Send message |
| PUT | `/group-chat/message?id=` | auth | Edit message |
| DELETE | `/group-chat/message?id=` | auth | Delete message |
| PUT | `/group-chat/message/vote?id=` | auth | Vote on message |
| GET/POST | `/group-chat/member/add?groupId=` | auth | Add member |

---

## Profiles & Users

| Method | URI | Middleware | Description |
|--------|-----|-----------|-------------|
| GET | `/user` | auth | Current user details |
| GET | `/profile?username=` | — | Public profile |
| GET/POST | `/profile/settings` | auth | Privacy settings |
| PUT | `/profile/settings` | auth | AJAX update single privacy setting |

---

## CAPTCHA

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/captcha` | Generate CAPTCHA image (PNG) |
| POST | `/captcha` | Verify input → `{ success, message, locked_out? }` |

Lockout response includes `"locked_out": true` and minutes remaining in `message`.
