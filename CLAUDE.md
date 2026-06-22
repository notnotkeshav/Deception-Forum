# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
bash sql.sh                    # import all DB schema files in order
php mail-worker.php            # flush the email queue (run on cron or manually)
php notification-cleanup.php   # delete notifications older than 30 days
```

**Environment** — create `.env` in the project root (not tracked):
```
DB_HOST, DB_PORT, DB_NAME, DB_CHARSET, DB_USERNAME, DB_PASSWORD
MAILER_HOST, MAILER_PORT, MAILER_USERNAME, MAILER_PASSWORD, MAILER_ENCRYPTION, MAILER_FROM_EMAIL, MAILER_FROM_NAME
```

## Documentation

Full system docs live in `docs/`:

| File | Covers |
|------|--------|
| `docs/architecture.md` | Request lifecycle, routing, DI container, workers, cache |
| `docs/auth.md` | Access levels, registration, 3-step login, TOTP, CAPTCHA, middleware keys |
| `docs/api.md` | Every endpoint — method, URI, middleware, description |
| `docs/database.md` | All table schemas, indexes, stored procedures |
| `docs/notifications.md` | Polling architecture, settings, badge UI |
| `docs/security.md` | Session config, CSRF, logging, password rules |
| `docs/chats.md` | Private and group chat data model and polling |

## Architecture

### Request Lifecycle (`index.php`)

1. Define `SESSION_LIFETIME_SECONDS = 150 * 60`. Set `gc_maxlifetime`, `session_set_cookie_params` (HttpOnly, Secure, SameSite=Strict), then `session_start()`.
2. Register the autoloader (`Backend\Core\Database` → `Backend/Core/Database.php`). No Composer.
3. Bootstrap DI container — binds `Core\Database`, `Core\Cache`, `Core\Mailer`, `Core\TemplateLoader`.
4. Log every request to `logs/security_YYYY-MM-DD_HH.log`; threats also to `logs/threats_YYYY-MM-DD.log`.
5. Check session expiry for authenticated users; redirect expired sessions to `/verify-totp?action=renew`.
6. Dispatch through `Router`.

**Session lifetime is defined once as `SESSION_LIFETIME_SECONDS`.** Both `index.php` (expiry logic) and `session_check.php` (JS client sync) read this constant. Never hardcode `150 * 60` elsewhere.

### Routing & Controllers

`Backend/Routes/Router.php` — exact URI string matching only (no dynamic segments). Controllers are plain PHP files `require`d directly. Middleware applied in order: global → group → route-specific.

```php
$router->get('/threads', 'threads/all.php')->only('auth');
$router->put('/thread/lock', 'moderators/lock.php')->only('admin');
```

### Middleware Keys

| Key | Behaviour |
|-----|-----------|
| `auth` | Full session required (`user` + `token` + non-expired `token_expiration`) |
| `guest` | Blocks authenticated users |
| `partial_auth` | Between credential check and TOTP step |
| `admin` | Requires `$_SESSION['moderator'] === true` — `Backend/Middleware/Admin.php` |
| `username_rate_limit` | 7 username-generation requests / IP / hour via file cache |

### Authentication Flow

```
POST /signin → partial_auth session (5 min TTL)
POST /verify-totp → full session: user, token, userId, token_expiration (24h), session_started
```

Session warnings fire client-side at **30, 15, 10, 5 minutes remaining** (not elapsed).  
On expiry, server redirects to `/verify-totp?action=renew&returnTo=<uri>`.

Access levels: 1–4 standard, 5+ can generate invites and receive real-time notifications, 15 = VeinKeeper (system, non-loginable).

### CAPTCHA (`Backend/controllers/captcha/index.php`)

- Charset: `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (no ambiguous 0/O/1/I)
- Length: 5 — `maxlength="5"` on all captcha inputs
- Lockout: 5 failed attempts → 15-minute session lockout (`CaptchaVerifier`)
- POST response includes `"locked_out": true` when locked

### Notifications

`/notifications/poll` is protected by `auth` middleware **and** a server-side `accessLevel >= 5` check inside the controller. The `accessLevel >= 5` condition in `navbar.php` and `footer.php` is a frontend optimisation only — the API enforces it independently.

`createNotification($userId, $type, $title, $message, $data)` respects per-user `notification_settings` before inserting.

### Key Global Helpers (`Backend/Utils/functions.php`)

| Function | Purpose |
|---|---|
| `view($path, $args)` | Render `frontend/views/$path`, extracting `$args` |
| `sendJsonResponse($ok, $msg, $details, $code)` | Emit JSON and exit |
| `redirect($url)` | Header redirect + exit |
| `abort($code, $data)` | Render error view and die |
| `authUser()` | `$_SESSION['user']` or null |
| `queueEmail($to, $subject, $body)` | Write job to `Backend/Core/email_queue/` |
| `verifyCsrfToken($token)` | Validate CSRF; `abort(419)` on mismatch |
| `createNotification(...)` | Insert notification respecting user settings |
| `isGroupMember($groupId, $userId)` | Check active group membership |

### Database

PDO wrapper at `App::resolve('Core\Database')`. All queries use named parameters. Database: `forum` (MySQL/InnoDB, utf8mb4_unicode_ci). Voting logic runs through stored procedures (`07_procedure.sql`) that toggle votes and recount atomically.

Schema load order: `01_auth` → `02_threads` → `03_comments` → `04_chats` → `05_moderator` → `06_notifications` → `07_procedure`

### Email & Cache

- Emails never sent synchronously — `queueEmail()` writes JSON to `Backend/Core/email_queue/`; `mail-worker.php` processes and deletes them.
- File cache (`Backend/Core/Cache.php`): MD5-keyed `.cache` files in `Backend/Core/cache/`. Used for username-gen rate limiting.

### Frontend

No build step. Vendored: jQuery 3.7.1, Quill, DOMPurify, Bootstrap. Per-feature JS in `public/javascripts/`. Chat uses long-polling (`/private-chat/messages/new`, `/group-chat/messages/new`). Notification polling at `/notifications/poll` (30s interval, pauses when tab hidden).
