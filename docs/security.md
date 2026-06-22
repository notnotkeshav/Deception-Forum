# Security

## Session Security

| Property | Value |
|----------|-------|
| Lifetime | 150 minutes (`SESSION_LIFETIME_SECONDS`) |
| Cookie HttpOnly | true |
| Cookie Secure | true |
| Cookie SameSite | Strict |
| GC maxlifetime | 150 minutes |
| Source of truth | `SESSION_LIFETIME_SECONDS` constant in `index.php` |

Session expiry is enforced server-side on every request. Expired sessions redirect to TOTP re-verification (`/verify-totp?action=renew`). The constant is also used by `session_check.php` so the JS timer always agrees with the server.

---

## CSRF Protection

Every state-changing request should pass a CSRF token via `verifyCsrfToken()`:

```php
verifyCsrfToken($_POST['csrf_token']);   // aborts 419 on mismatch
```

Token is stored in `$_SESSION['csrf_token']` and generated with `generateCsrfToken()`. The token is rotated on full authentication (TOTP verification step).

---

## CAPTCHA

Protects the invite-code request and signup endpoints.

| Property | Value |
|----------|-------|
| Charset | `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (no 0, O, 1, I) |
| Length | 5 characters |
| Space | ~33 million combinations |
| Expiry | 12 seconds |
| Max attempts | 5 per session |
| Lockout | 15 minutes after 5 failures |
| Rate limit | Via `CaptchaVerifier` session-based attempt counter |

---

## Security Logging

Every request is logged to `logs/security_YYYY-MM-DD_HH.log` (hourly rotation):

```
[timestamp] [NORMAL|SUSPICIOUS] IP=... Method=... URI=... UserAgent=... Threats=... Session=...
```

Suspicious requests (pattern matches for SQL injection, path traversal, XSS, command injection, known scanner UAs) are additionally written to `logs/threats_YYYY-MM-DD.log` and to the system error log.

Detected patterns:
- SQL injection keywords (`union`, `select`, `insert`, etc.)
- Path traversal (`../`, `/etc/`, `/proc/`)
- XSS (`<script`, `javascript:`, `onerror=`)
- Command injection (`;`, `|`, `` ` ``, `$()`)
- Known scanner UAs (sqlmap, nikto, nmap, burp, metasploit, curl, wget)

---

## Authentication Security

- **Passwords:** bcrypt, minimum 25 characters, strict complexity rules (2 upper, 2 lower, 3 digits, 5 special, no common patterns, no username/name substring).
- **TOTP:** RFC 6238, ±30s window, 6-digit HMAC-SHA1.
- **Backup codes:** 10 codes, Argon2ID hashed, single-use.
- **Partial auth TTL:** 5 minutes — the window between credential validation and TOTP entry.
- **Invite-only:** Registration requires a one-time invite code from an `accessLevel >= 5` user.

---

## Authorization

- All protected routes use `->only('auth')` or `->only('admin')` in the router.
- The `admin` middleware (`Backend/Middleware/Admin.php`) checks `$_SESSION['moderator'] === true`.
- The notification poll endpoint enforces `accessLevel >= 5` server-side (not just in the frontend template).
- Soft-deletes (`isDeleted`) prevent hard data removal; deleted content stays in DB but is filtered from queries.

---

## Input Validation

All user input is validated through `Backend/Utils/Validator.php` before touching the database. All database queries use PDO prepared statements with named parameters — no string interpolation into SQL.

The email validator rejects a hardcoded list of disposable email domains.

---

## Browser Restriction

`browser-check.php` (included in `index.php`) enforces a browser allowlist. Only approved browsers can reach the application; others receive an error view.

---

## Known Plaintext Password Storage

`database/01_auth.sql` defines a `passwords` table with both `passwordHash` and `password` (plaintext) columns. **This is a critical security issue** that should be addressed — the plaintext column should be dropped and the table repurposed as a hash-only audit log if needed.
