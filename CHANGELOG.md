# Changelog

---

## [Unreleased] — 2026-06-22

### Fixed
- Captcha lockout: 5-attempt limit with 15-minute lockout via session-based counter in `CaptchaVerifier`
- Admin middleware re-enabled (`'admin' => Admin::class` in `Middleware::$MAP`)
- Session warning intervals corrected from elapsed-based to remaining-based (warn at 30, 15, 10, 5 minutes remaining)

---

## 2026-06-22 — Security & correctness fixes (`b49a7aa`)

### Fixed
- `GET /notifications/poll` now enforces `accessLevel >= 5` server-side (was frontend-only)
- `footer.php` `NotificationClient` gated to `accessLevel >= 5` (previously ran for all auth users, bypassing the navbar gate)
- Captcha charset restored to alphanumeric `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (entropy ~33M vs 100K)
- `session_set_cookie_params()` updated from deprecated positional form to array form; `HttpOnly`, `Secure`, `SameSite=Strict` now set
- `SESSION_LIFETIME_SECONDS` constant defined once in `index.php`; `session_check.php` now reads it instead of independently hardcoding `150 * 60`
- `SESSION_LIFETIME` assignment in `session-monitor.js` guarded against `undefined`/`NaN`
- Captcha `maxlength` corrected from 10 to 5 in `index.view.php` and `signup.view.php`
- Removed redundant `window focus` poll listener in navbar; `visibilitychange` alone handles tab-return

---

## 2025-12-27

### Changed
- Session lifetime made configurable from server response in `session-monitor.js` (`SESSION_LIFETIME` now `let`, updated from `/session/check` response) (`9e4fe84`)
- Session lifetime restored to 150 minutes after testing with 3-minute value (`f10956d`)
- Captcha length shortened to 5 characters (`f10956d`)

### Fixed
- `session_set_cookie_params` and `ini_set('session.gc_maxlifetime')` added to `index.php` (`b84aba8`)

---

## 2025-12-06

### Added
- Notification polling system restricted to users with `accessLevel >= 5` in navbar (`ae68da6`)

### Fixed
- `queueEmail()` directory path casing (`core` → `Core`) (`840dc8d`)

---

## 2025-12-02

### Added
- Favicon added to all major views and error pages (`cfcf8c8`)
- Security request logging to hourly log files with threat detection (`fa9b5f8`)
- `logs/` added to `.gitignore` (`5240bce`)

### Changed
- Browser check hardened; environment and SQL config fixes (`0b21f43`)

---

## 2025-11-27

### Changed
- Database schema refactored for consistent `utf8mb4_unicode_ci` collation across all tables (`b43b428`)
- Chat member addition and notification handling refactored; session management improved (`6176bfb`)

---

## 2025-11-18

### Changed
- Profile and notification features refactored (`ed3d2d0`)
- Private chat view and create chat views redesigned with enhanced UI (`6893243`)

---

## 2025-11-14

### Changed
- Thread and user views refactored with improved styling (`ee834fb`)

---

## 2025-09-13

### Added
- Notification system (DB + UI)
- CAPTCHA system for invite code and signup flows
- Custom logo and ScaryVampire font
- Username generation endpoint with suggestions

### Fixed
- TOTP verification flow
- Auth page UI redesign with invite flow

(`fc66836`)

---

## 2025-08-02

### Fixed
- JSON response helper sending incorrect format (`9a701ad`)

---

## 2025-07-14

### Added
- TOTP-based two-factor authentication (RFC 6238 implementation with backup codes) (`83a001f`)

---

## 2025-07-02

### Added
- Asynchronous email sending via file queue + `mail-worker.php` (`5b8ab69`)
- Browser check (Firefox desktop allowlist) (`e56c1a2`)

### Fixed
- Missing variable definitions in chat message polling (`795dbef`)
- `expiresAt` column type changed from TIMESTAMP to DATETIME in password resets (`f3884cb`)

---

## 2025-04-22

### Added
- Upvote/downvote for group chat messages (`e0a9676`, `4d8ac6f`)
- Vote toggle logic via stored procedures (`88612c7`)
- Deep comment nesting: redirect to separate page when level exceeds 5 (`27bbd76`)

### Fixed
- Comment navigation (back to correct parent level) (`bb20b83`)
- `expandedReplies` converted to Map for level-tracking (`f10a28b`)
- Edit URI typo (`a196390`)
- Chat window height adjustment (`0c805e3`)

---

## 2025-04-18

### Added
- Group chat implementation (rooms, members, messages, polling) (`bf00031`)

---

## 2025-03-30

### Added
- Private chat implementation (1:1 messages, polling) (`130fb1f`)

### Changed
- Polling logic improved to stop refresh on inactivity (`0ef5951`)

---

## 2025-01-13

### Added
- Group chat controller files (`40db343`)
- Private chat controller files and basic views (`8333fb2`, `cb5a3ec`)

### Changed
- Controller directory structure reorganised (`f35ce22`)

### Fixed
- Cache retrieval bug (`27dadad`)

---

## 2025-01-10

### Added
- Password change, forgot password, and reset features (`4dd7c85`)
- Email sending for password reset and signup (`bf7c503`)
- Mail templates (signup success, password change, reset) (`e013d93`)
- PHPMailer-based `Mailer` class (`7a555ed`)
- Group chat and private chat database schemas (`402d305`)
- Routes for private and group chats (`3e9cd13`)
- Bootstrap added to views (`b5b610b`)

---

## 2025-01-09

### Added
- Password validation with default params (`c0cb38b`)
- User details route (`f892fd4`)
- Invite link generation (`0ee7184`)
- `inTransaction()` helper on Database class (`b867887`)
- `change_password` controller and `deleteCookies` function (`ff00bde`, `872b6e6`)

### Fixed
- Moderator lock logic refined (`5b5f63d`)

---

## 2025-01-08

### Added
- More utility functions (`43cd72f`)
- Normal heading support in Quill editor (`60a42bb`)
- Notification controller (`5e3ee54`)
- Moderator table added to DB (`d4d9b03`)

### Changed
- Thread controllers updated (`178fe3c`)
- Comment voting and controllers updated (`40caec6`)
- Stored procedure updated (`a4430d5`)

### Fixed
- Relevant HTTP status codes sent from auth controllers (`2e89e86`)
- `userId` column type corrected to `CHAR(36)` (`9f0da75`)

---

## 2025-01-07 and earlier — Initial Development

### Added
- Core framework: `Router`, `Container`, `App`, `Database`, `Cache` (`4774412`, `d8221f1`, `81a12b3`)
- Authentication: signup, signin, session management (`dd7bc50`, `8743e32`)
- Thread CRUD with pagination (`955ff5c`, `dd7bc50`)
- Comment system with threaded replies (`80fa830`, `c47576c`, `61624a6`, `5d8d6c1`)
- Upvote/downvote for threads and comments with stored procedures (`8b06f99`, `6fbdf2f`, `254e302`)
- Moderator routes and thread locking (`8b6cdcd`, `35f17da`, `b7ebcc8`)
- Quill rich text editor (vendored) (`2a41307`)
- Bootstrap CSS (vendored) (`91a18c8`)
- DOMPurify (vendored) (`style(frontend): add styles`)
- Error views (404, 401, etc.) (`3230ea2`)
- Database schema: `users`, `threads`, `comments`, `threadVotes`, `commentVotes` (`ced5de8`, `b6f9ec1`)
- Public assets, favicon (`3235b49`, `b853e01`)
- `.gitignore` (`42202aa`)
