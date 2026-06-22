# Notification System

## Overview

The notification system has two transports: a server-side DB-backed store and a client-side polling mechanism restricted to users with `accessLevel >= 5`.

---

## Creating Notifications (Server-side)

Use the global helper:

```php
createNotification(
    $userId,   // recipient
    $type,     // one of the ENUM values below
    $title,
    $message,
    $data      // optional array — stored as JSON (e.g. ['thread_id' => '...'])
);
```

The function first checks `notification_settings` for the recipient. If the user has disabled that type, no row is inserted. Failures are logged but never thrown.

**Types:** `thread_comment`, `comment_reply`, `thread_vote`, `comment_vote`, `new_thread`, `mention`, `system`

---

## Polling Architecture

Polling is split between two client-side components:

### `NotificationClient` (`public/javascripts/notifications.js`)

A class-based client initialized in `frontend/views/partials/footer.php` for users with `accessLevel >= 5`.

```js
new NotificationClient({
    onNewNotification: fn,
    onUnreadCountUpdate: fn,
    onError: fn
}).start();
```

Polls `GET /notifications/poll?last_check=<unix>`. On new notifications, calls `onNewNotification` and fires a browser `Notification` (if permitted).

### Inline Poller (`frontend/views/partials/navbar.php`)

A secondary IIFE-based poller also restricted to `accessLevel >= 5`. Updates the navbar badge (`.notification-badge`) using `classList.add/remove('visible')`. Polls every 30 seconds. Pauses when tab is hidden (via `visibilitychange`).

### Poll Endpoint (`Backend/controllers/notifications/poll.php`)

```
GET /notifications/poll?last_check=<unix_timestamp>
```

- Protected by `auth` middleware + server-side `accessLevel >= 5` check.
- Returns:
  ```json
  {
    "success": true,
    "message": "Poll successful",
    "details": {
      "unread_count": 3,
      "new_notifications": [...],
      "timestamp": 1234567890
    }
  }
  ```
- `new_notifications` is empty if `last_check` is omitted.
- Limit: 10 new notifications per poll.

---

## Notification Settings

Per-user settings in `notification_settings` table. Defaults:

| Type | Default |
|------|---------|
| thread_comment | enabled |
| comment_reply | enabled |
| thread_vote | enabled |
| comment_vote | enabled |
| new_thread | **disabled** |
| mention | enabled |
| system | enabled |

Settings are created automatically on first access. Update via `PUT /notifications/settings` (AJAX, single field) or `POST /notifications/settings` (full form).

---

## Cleanup

`notification-cleanup.php` calls `cleanOldNotifications(30)` which deletes rows older than 30 days.

Run on a cron: `0 3 * * * php /path/to/notification-cleanup.php`

---

## Badge UI

The `.notification-badge` element lives in the navbar. CSS hides it by default; `classList.add('visible')` shows it with a pulse animation. When the user is on `/notifications`, CSS forces `display: none` regardless of JS state.

Browser notifications require `Notification.permission === 'granted'`. Permission is requested once on `DOMContentLoaded` by `NotificationClient.requestPermission()`.
