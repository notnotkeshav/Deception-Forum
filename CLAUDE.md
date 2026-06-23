# CLAUDE.md

## Project Summary

**Deception Forum** is a custom PHP MVC application designed for operation on darknet infrastructure (Tor/I2P). It prioritizes security, anonymity, and resistance to active attacks. The system is invite-only, requires mandatory 2FA (TOTP), restricts to Firefox desktop, and implements hardened session management, logging, and anti-forensic measures.

**Security Posture:** Extremely hardened architecture with browser enforcement, cryptographic 2FA, CAPTCHA protection, session expiry, CSRF tokens, and comprehensive security logging. However, **critical vulnerabilities exist** (see Issues section below).

---

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

---

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
| `DESIGN.md` | Architectural design, threat model, data flows |
| `MEMORY.md` | Known issues, technical debt, lessons learned |

---

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
| `auth` | Full session required (`user` + `token` + non-expired `token_expiration`). Redirects partial-auth to TOTP. |
| `guest` | Blocks authenticated users; redirects to `/threads`. Redirects partial-auth to TOTP. |
| `partial_auth` | Between credential check and TOTP step (5-min TTL). |
| `admin` | Requires `$_SESSION['moderator'] === true` — **see Security Issues below**. |
| `username_rate_limit` | 7 username-generation requests / IP / hour via file cache. |

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
- Expiry: 12 seconds per CAPTCHA image

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
| `generateCsrfToken()` | Create random 32-byte CSRF token |
| `generateLoginUrl()` | Create unique per-user login slug |

### Database

PDO wrapper at `App::resolve('Core\Database')`. All queries use named parameters. Database: `forum` (MySQL/InnoDB, utf8mb4_unicode_ci). Voting logic runs through stored procedures (`07_procedure.sql`) that toggle votes and recount atomically.

Schema load order: `01_auth` → `02_threads` → `03_comments` → `04_chats` → `05_moderator` → `06_notifications` → `07_procedure`

### Email & Cache

- Emails never sent synchronously — `queueEmail()` writes JSON to `Backend/Core/email_queue/`; `mail-worker.php` processes and deletes them.
- File cache (`Backend/Core/Cache.php`): MD5-keyed `.cache` files in `Backend/Core/cache/`. **Warning: uses `unserialize()` — potential RCE vector (see Security Issues)**.

### Frontend

No build step. Vendored: jQuery 3.7.1, Quill, DOMPurify, Bootstrap. Per-feature JS in `public/javascripts/`. Chat uses long-polling (`/private-chat/messages/new`, `/group-chat/messages/new`). Notification polling at `/notifications/poll` (30s interval, pauses when tab hidden). All views sanitize via DOMPurify.

---

## Security Mechanisms

### Browser Enforcement (`browser-check.php`)

Blocks all browsers except Firefox Desktop via 8-layer validation:
1. Must contain "Firefox" + "Gecko"
2. No mobile indicators
3. No invalid spoofing combinations
4. Valid Firefox version format
5. Valid Gecko date format
6. Recognized desktop OS only
7. No bot/scraper indicators
8. Gecko must come before Firefox in UA string

Also validates HTTP headers (Accept, Accept-Language, Accept-Encoding).

### Passwords & TOTP

- **Passwords:** bcrypt, 25–255 characters, strict rules (2 upper, 2 lower, 3 digits, 5 special, no patterns, no username/name substring).
- **TOTP:** RFC 6238, 30s window, ±1 step (90s tolerance), 6-digit HMAC-SHA1, custom base32 implementation.
- **Backup codes:** 10 codes (Argon2ID hashed), single-use, stored in JSON on user row.

### Session Management

- Lifetime: 150 minutes (`SESSION_LIFETIME_SECONDS`).
- HttpOnly, Secure, SameSite=Strict cookies.
- Server-side expiry check on every request (except skip routes).
- Client-side timer with alerts at 30, 15, 10, 5 minutes remaining.
- Partial auth TTL: 5 minutes (credential check → TOTP verification).

### CSRF Protection

Every state-changing request must pass a CSRF token via `verifyCsrfToken()`. Token stored in `$_SESSION['csrf_token']` and rotated on TOTP verification.

### Input Validation

All user input validated via `Backend/Utils/Validator.php` before database queries. All queries use PDO prepared statements with named parameters — no string interpolation.

### Security Logging

Every request logged to hourly files:
- `logs/security_YYYY-MM-DD_HH.log` — all requests
- `logs/threats_YYYY-MM-DD.log` — suspicious patterns

Detects: SQL injection, path traversal, XSS, command injection, scanner UAs.

---

## ⚠️ CRITICAL SECURITY ISSUES

### 1. Plaintext Password Storage (CRITICAL)

**File:** `database/01_auth.sql` (line 26), `Backend/controllers/auth/signup.php` (line 127–131)

**Issue:** The `passwords` table stores both `passwordHash` AND plaintext `password`. Line 130 explicitly inserts the plaintext password:
```php
$db->query("INSERT INTO passwords (userId, passwordHash, password) VALUES (...)", [
    ":password" => $_POST['password']  // ← PLAINTEXT
]);
```

**Impact:** HIGH. Complete compromise if database is breached. Password reuse across other services. Violates all security best practices.

**Fix:** Delete the plaintext `password` column entirely. If an audit log is needed, hash passwords with a non-invertible function.

---

### 2. PHP `unserialize()` in Cache (CRITICAL)

**File:** `Backend/Core/Cache.php` (lines 22, 50)

**Issue:** Cache uses `unserialize()` on untrusted file contents:
```php
public function get($key) {
    $data = file_get_contents($filePath);
    return unserialize($data);  // ← RCE VECTOR
}
```

**Impact:** HIGH. If cache files are writable by an attacker or contain controlled data, arbitrary code execution is possible.

**Fix:** Replace with JSON serialization:
```php
return json_decode($data, true);  // Instead of unserialize()
```

---

### 3. Hardcoded Database Credentials (HIGH)

**File:** `Backend/Core/Database.php` (line 16)

**Issue:** Database constructor has hardcoded defaults:
```php
public function __construct(array $config, string $username = "root", string $password = "11112222")
```

**Impact:** HIGH. If code is leaked or bootstrapped improperly, attacker has database credentials.

**Fix:** Remove defaults. Require credentials from environment variables only. Fail loudly if missing.

---

### 4. Unvalidated `$_SESSION['moderator']` in Admin Middleware (MEDIUM)

**File:** `Backend/Middleware/Admin.php` (line 15)

**Issue:** Missing null check before boolean evaluation:
```php
if (!$_SESSION['moderator']) {  // No isset() check
```

**Impact:** MEDIUM. If `$_SESSION['moderator']` is unset, returns false, which fails silently. Better to throw error.

**Fix:**
```php
if (empty($_SESSION['moderator']) || !$_SESSION['moderator']) {
    sendJsonResponse(false, "Access denied", [], 403);
}
```

---

### 5. No Data-at-Rest Encryption (MEDIUM)

**Issue:** Database and file storage (cache, email queue, logs) are unencrypted.

**Impact:** MEDIUM. Violates dark web operational security. Disk seizure exposes all user data, sessions, and secrets.

**Fix:** Implement transparent database encryption (MySQL TDE, InnoDB encryption) and encrypt cache/queue files at rest.

---

### 6. Session State in Memory (MEDIUM)

**Issue:** All session data (user info, tokens, TOTP secrets) stored in PHP session files or in-memory.

**Impact:** MEDIUM. Memory dumps, crash logs, or old session files could leak sensitive data.

**Fix:** Implement session cleanup on logout. Use secure session handlers (Redis with encryption). Wipe session memory on exit.

---

### 7. Weak Browser Enforcement Bypass (LOW)

**File:** `browser-check.php`

**Issue:** User-Agent is trivial to spoof. This provides obfuscation, not security.

**Impact:** LOW. Determined attackers can bypass with curl/requests libraries. Detects bots but not sophisticated bypasses.

**Fix:** Add server-side JavaScript challenge (e.g., require WebGL support, DOM APIs only available in Firefox). Accept this is a speed bump, not a barrier.

---

## Important Notes for Code Changes

### Cache & Serialization
- Do NOT use `unserialize()` on any user-influenced data. Always use JSON or a safe serialization format.
- Cache expiration cleanup in `Cache::clearExpired()` is called nowhere — consider cron integration.

### Admin Middleware
- Always check `isset($_SESSION['key'])` before accessing. Middleware should exit cleanly, never silently fail.

### Database Credentials
- Require all DB credentials from environment. No defaults. Use `getenv()` or similar, fail if missing.

### TOTP Implementation
- The custom base32 decoder is correct (RFC 4648). Window of ±1 step is appropriate.
- Backup codes are Argon2ID hashed — good practice. Ensure they're only readable by the user.

### Session Lifetime
- 150 minutes is 2.5 hours. Verify this is appropriate for a darknet forum. Consider shorter for high-security deployments.

### Logging
- Security logs are hourly files in `logs/`. Retention policy is undefined. Implement log rotation and encryption.

### Email Queue
- Emails are queued as JSON files. Ensure queue directory has restricted permissions (0700).

---

## Directory Structure

```
.
├── Backend/
│   ├── Core/
│   │   ├── bootstrap.php           ← DI setup
│   │   ├── Database.php            ← PDO wrapper
│   │   ├── Cache.php               ← File-based cache (⚠️ unserialize)
│   │   ├── Mailer.php              ← Email integration
│   │   ├── Container.php           ← DI container
│   │   ├── config.php              ← Database config
│   │   ├── cache/                  ← Cache files (MD5-keyed)
│   │   ├── email_queue/            ← Pending emails (JSON)
│   │   └── mail-templates/         ← Email HTML templates
│   ├── Middleware/
│   │   ├── Auth.php, Guest.php, PartialAuth.php, Admin.php, Middleware.php
│   │   └── UsernameGenerationMiddleware.php
│   ├── Utils/
│   │   ├── functions.php           ← Global helpers
│   │   ├── TOTP.php                ← RFC 6238 implementation
│   │   ├── Validator.php           ← Input validation
│   │   ├── ValidationException.php
│   │   ├── ResponseCode.php
│   │   ├── NotificationManager.php
│   │   └── auth/generator.php      ← Username/login slug generation
│   ├── Routes/
│   │   ├── Router.php              ← Routing engine
│   │   └── routes.php              ← Route definitions
│   └── controllers/
│       ├── auth/                   ← signin, signup, TOTP, password reset
│       ├── threads/                ← create, edit, vote, view
│       ├── comments/               ← create, edit, vote, view
│       ├── chats/                  ← private/group messaging
│       ├── captcha/                ← CAPTCHA generation/verification
│       ├── notifications/          ← polling, settings
│       ├── profile/                ← profile info, settings
│       └── moderators/             ← admin-only endpoints
├── database/
│   ├── 01_auth.sql                 ← Users, auth tables
│   ├── 02_threads.sql              ← Threads, comments
│   ├── 03_comments.sql
│   ├── 04_chats.sql                ← Chat tables
│   ├── 05_moderator.sql            ← Moderator roles, permissions
│   ├── 06_notifications.sql        ← Notification system
│   └── 07_procedure.sql            ← Vote toggle stored procedures
├── frontend/
│   ├── views/
│   │   ├── auth/                   ← signin, signup, TOTP, password reset
│   │   ├── threads/                ← thread listing, viewing
│   │   ├── chats/                  ← chat UIs (private/group)
│   │   ├── notifications/          ← notification center
│   │   ├── profile/                ← profile page, settings
│   │   ├── partials/               ← navbar, footer, comments
│   │   └── errors/                 ← error pages (401, 404, 419, etc.)
│   └── styles/ (if present)
├── public/
│   ├── javascripts/
│   │   ├── auth.js                 ← Login/signup logic
│   │   ├── custom.js               ← General utilities
│   │   ├── session-monitor.js      ← Session expiry timer (client-side)
│   │   ├── notifications.js        ← Poll loop
│   │   ├── thread.js, comment.js   ← Content interactions
│   │   ├── private-chat.js, group-chat.js
│   │   ├── jquery-3.7.1.min.js, quill.min.js, purify.min.js
│   │   └── bootstrap (CSS/JS)
│   └── images/
├── docs/
│   ├── architecture.md
│   ├── auth.md
│   ├── api.md
│   ├── database.md
│   ├── security.md
│   ├── notifications.md
│   └── chats.md
├── index.php                       ← Entry point
├── browser-check.php               ← Firefox enforcement
├── mail-worker.php                 ← Background email processor
├── notification-cleanup.php        ← Background notification deletion
├── sql.sh                          ← Database setup script
├── CLAUDE.md                       ← This file
├── DESIGN.md                       ← Architectural design & threat model
├── MEMORY.md                       ← Known issues & lessons learned
├── CHANGELOG.md
└── .env (not tracked)              ← Environment variables
```

---

## VeinKeeper

A non-loginable system account with `accessLevel=15`. Resides at `00000000-0000-0000-0000-00000000000D`. Used for bootstrapping invite codes without a human user. Symbolic; all invitations from this account bypass normal generator validation.

---

## Session & Operational Security for Dark Web Deployment

1. **Tor/I2P Headers:** Ensure reverse proxy (nginx/Tor) strips identifying headers (X-Forwarded-For, X-Real-IP) unless explicitly trusted.
2. **Log Rotation:** Implement immediate log cleanup or encryption. Logs are a forensic goldmine.
3. **Cache Cleanup:** Set up cron to delete expired cache entries. Current `clearExpired()` is manual.
4. **Email Encryption:** Consider end-to-end email encryption for sensitive account notifications (password reset, 2FA changes).
5. **Database Backups:** If backups are taken, encrypt them and store off-server. Backups contain plaintext passwords (critical bug #1).
6. **Session Fixation:** CSRF rotation on every state change is correct. Verify partial-auth sessions are destroyed on timeout.

---

## Updates & Changes Log

**2026-06-22 — Security Audit v1.0:**
- Initial comprehensive security analysis completed.
- Identified 7 critical/high/medium severity issues.
- Created DESIGN.md and MEMORY.md.
- Flagged plaintext password storage, unserialize() RCE, hardcoded credentials.
