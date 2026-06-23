# DESIGN.md — Architectural Design & Threat Model

## Executive Summary

Deception Forum is a purpose-built darknet communication platform emphasizing anonymity, resistance to active attacks, and operational security. The system employs:

- **Invite-only registration** with cryptographically signed codes
- **Mandatory 2FA (TOTP)** with Argon2ID-hashed backup codes
- **Firefox-only enforcement** via 8-layer user-agent validation
- **Hardened session management** (150-min lifetime, HttpOnly+Secure+SameSite=Strict cookies)
- **Comprehensive security logging** with threat pattern detection
- **No external dependencies** — custom PHP MVC framework, embedded jQuery/Quill
- **Soft-delete architecture** for forensic resistance
- **File-based caching** with rate limiting
- **Asynchronous email queue** to avoid synchronous exposure

This document describes the architectural design, data flows, threat model, and security justifications.

---

## System Architecture

### High-Level Component Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ Client (Firefox Desktop Browser)                             │
│  - No JavaScript execution outside approved sandboxes      │
│  - DOMPurify XSS sanitization on all dynamic content       │
│  - Session monitor (client-side timer)                      │
└──────────────┬──────────────────────────────────────────────┘
               │ HTTPS/Tor
┌──────────────▼──────────────────────────────────────────────┐
│ Edge (Reverse Proxy — nginx on Tor/I2P)                     │
│  - TLS termination                                          │
│  - Strips identifying headers                              │
│  - Rate limiting (IP-based)                                │
└──────────────┬──────────────────────────────────────────────┘
               │
┌──────────────▼──────────────────────────────────────────────┐
│ PHP Application (index.php)                                 │
│  1. Browser check (Firefox validation)                     │
│  2. Security logging                                        │
│  3. Session expiry check                                    │
│  4. Route dispatch                                          │
└──────────────┬──────────────────────────────────────────────┘
               │
    ┌──────────┴──────────┬──────────────┐
    │                     │              │
┌───▼────┐         ┌─────▼──┐    ┌─────▼──┐
│ Router │         │ Middleware   │ Container
└───┬────┘         └─────┬──┘    └─────┬──┘
    │                    │             │
┌───▼────────────────────▼─┐      ┌───▼──────────────┐
│ Middleware Chain         │      │ DI Services      │
│ - auth / guest           │      │ - Database       │
│ - partial_auth           │      │ - Cache          │
│ - admin                  │      │ - Mailer         │
│ - csrf                   │      │ - TemplateLoader │
│ - rate_limit             │      └──────────────────┘
└──────────────┬───────────┘
               │
        ┌──────▼───────┐
        │ Controller   │
        │ (require)    │
        └──────┬───────┘
               │
    ┌──────────┼──────────┬──────────────┐
    │          │          │              │
┌───▼─────┐┌──▼──┐┌──────▼──┐┌────────┐
│ Database ││Cache││ Mailer  ││ View   │
│  (PDO)   ││ (FS)││(PHPMail)││(Extract)│
└──────────┘└─────┘└─────────┘└────────┘
    │         │         │          │
┌───▼─────────▼─────────▼──────────▼────┐
│ Persistent Layer                       │
│ - MySQL (InnoDB, utf8mb4_unicode_ci) │
│ - File cache (/Backend/Core/cache/) │
│ - Email queue (/Backend/Core/email_queue/)
│ - Security logs (/logs/)             │
└────────────────────────────────────────┘
```

---

## Request Lifecycle (Detailed Flow)

```
1. Browser Request (HTTPS/Tor)
   ↓
2. index.php entry point
   ├─ SESSION_LIFETIME_SECONDS = 150 * 60 (constant)
   ├─ session_set_cookie_params(HttpOnly, Secure, SameSite=Strict)
   ├─ session_start()
   ├─ browser-check.php (Firefox validation)
   ├─ logSecurityEvent() (hourly log file)
   ├─ Session expiry check (if authenticated)
   │   └─ If expired: destroy session, redirect to /verify-totp?action=renew
   └─ $router->route($uri, $method)
   ↓
3. Router::route()
   ├─ Find matching route (exact URI + method match)
   ├─ Collect middleware (global → group → route-specific)
   └─ applyMiddleware() (in order)
   ↓
4. Middleware Chain
   ├─ Each middleware::handle() runs
   ├─ If fails: abort(401/403/500) and exit
   └─ If passes: continue to next middleware
   ↓
5. Controller Execution
   ├─ require(Backend/controllers/{controller})
   ├─ Access to $_SESSION, $_POST, $_GET, functions, App::resolve()
   ├─ Perform validation, query database, render view or sendJsonResponse()
   └─ exit()
   ↓
6. Response to Client
   ├─ HTTP headers (Cache-Control, Content-Type, etc.)
   ├─ HTML body (from view) OR JSON (from sendJsonResponse)
   └─ TLS/Tor delivery back to client
```

---

## Authentication & Authorization Design

### Three-Step Authentication

**Step 1: Credential Verification (POST /signin)**
- Email + password submitted via HTTPS
- Password validated against bcrypt hash in `users.passwordHash`
- On success: create `$_SESSION['partial_auth']` (expires in 5 min)
- Redirect to `/verify-totp` (or `/totp-setup` if first login)

**Step 2: TOTP Verification (POST /verify-totp)**
- User provides 6-digit TOTP code (or backup code)
- Code validated against user's `totp_secret` (RFC 6238, ±30s window)
- On success:
  - Destroy `partial_auth`
  - Create full session: `user`, `userId`, `token`, `token_expiration` (24h from now), `moderator` (bool)
  - Set `session_started` timestamp
  - Rotate CSRF token
- Redirect to `/threads`

**Step 3: Session Expiry & Renewal**
- On every request (except skip routes), `index.php` checks: `time() - $_SESSION['session_started'] > SESSION_LIFETIME_SECONDS`
- If expired: destroy session, set `$_SESSION['session_expired'] = true`, redirect to `/verify-totp?action=renew&returnTo=<uri>`
- After TOTP re-verification, user returned to original URI

### Access Levels

| Level | Capabilities |
|-------|---|
| 1–4 | Standard users. Read-only access to public content. Can create posts/comments/chats. No invite generation. |
| 5–14 | Elevated users. Can generate invite codes. Receive real-time notifications. (5+ required for `/notifications/poll`). |
| 15 | VeinKeeper (system). Non-loginable. Used for code seeding and bootstrapping. |

### Moderator Authorization

Separate `moderators` table links users to roles (super_admin, admin, moderator). Granular permissions in `Access` table:
- `canBanUsers`, `canDeletePosts`, `canEditPosts`, `canViewReports`, `canManageUsers`, `canCreateGroups`, `canAssignRoles`, `canPinPosts`, `canViewLogs`

Admin endpoints check `$_SESSION['moderator'] === true` (via Admin middleware).

---

## Invitation System

### Design Goals

- **Cryptographically signed** to prevent forgery
- **One-time use** to prevent reuse
- **Traceable** (who generated, who used)
- **Rate-limited** per generator (implicit via code generation)

### Flow

1. **Code Generation (GET/POST /generate_invite_code)**
   - Requires auth middleware (full session)
   - Requires `accessLevel >= 5`
   - Generate unique alphanumeric code (20 chars, URL-safe)
   - Insert into `inviteCodes` table: `(code, generatorId, used=false)`
   - Return code to user

2. **Code Distribution (Out-of-Band)**
   - User shares code privately (Tor PM, encrypted email, etc.)
   - Code is opaque (no user data embedded)

3. **Code Validation (POST /signup)**
   - User visits `/signup?invite=<code>`
   - Captcha required (5 chars, 12s expiry, 15-min lockout)
   - On POST:
     - Lookup code: `SELECT * FROM inviteCodes WHERE code = ? AND used = 0`
     - If not found: abort(404)
     - Validate email (not in disposable domain list)
     - Validate password (strict rules: 25+ chars, 2 upper, 2 lower, 3 digit, 5 special, no patterns)
     - Validate username (server-generated via `/generate-username`, cached per IP)
     - Insert new user: `INSERT INTO users (...)`
     - Insert password audit: `INSERT INTO passwords (userId, passwordHash, password)` ⚠️ **Plaintext stored**
     - Mark code used: `UPDATE inviteCodes SET used = 1, usedBy = ?`
     - Generate TOTP secret, redirect to `/totp-setup`

### Security Properties

- **Weakness:** No cryptographic signature on codes. A compromised database reveals all distributed codes.
- **Strength:** One-time use prevents replay attacks.
- **Traceability:** Full audit trail of who generated/used each code.

---

## Data Flows

### Thread Creation (POST /threads)

```
1. User authenticated (auth middleware checks ✓)
2. POST body: { title, content, categories[] }
3. Validate: title (1–255 chars), content (1–65535 chars), CSRF token
4. Query: SELECT * FROM categories WHERE id IN (...)  [validate category IDs]
5. Insert: INSERT INTO threads (id, title, content, userId, status='open', createdAt)
6. Insert votes records if applicable
7. Insert category links via threadCategoryLink junction table
8. Redirect to /threads/{id}
```

### Comment Creation (POST /comment)

```
1. User authenticated (auth middleware checks ✓)
2. POST body: { threadId, parentCommentId?, content }
3. Validate: content (non-empty), CSRF token
4. Query: SELECT id FROM threads WHERE id = ? AND isDeleted = 0  [verify thread exists]
5. If parentCommentId: SELECT id FROM comments WHERE id = ? AND isDeleted = 0  [verify parent comment]
6. Insert: INSERT INTO comments (threadId, parentCommentId, userId, content, createdAt)
   - Supports unlimited nesting; UI limits to 5 before pagination
7. Return JSON: { success, commentId, renderedHTML }
```

### Vote Toggling (POST /threads/vote, /comments/vote)

```
1. User authenticated
2. POST: { targetId, voteType='upvote'|'downvote' }
3. Lookup current vote: SELECT * FROM threadVotes WHERE threadId=? AND userId=?
4. Call stored procedure: CALL updateThreadVotesAndGetCounts(threadId, voteType, userId)
   - Procedure logic:
     a. If user already voted same type: DELETE the vote (toggle off)
     b. If different type: UPDATE the vote (change)
     c. If no vote: INSERT new vote
     d. Recount: SELECT COUNT(*) FROM threadVotes WHERE threadId=? AND voteType=...
     e. UPDATE threads SET upvoteCount=?, downvoteCount=?
     f. RETURN (upvoteCount, downvoteCount)
5. Return JSON: { success, upvotes, downvotes }
```

### Notification Polling (GET /notifications/poll)

```
1. User authenticated (auth middleware)
2. Server-side check: if accessLevel < 5: abort(403)
3. Query params: ?lastCheck={unix_timestamp}
4. Query: SELECT id, type, title, message, data, createdAt 
         FROM notifications 
         WHERE userId=? AND UNIX_TIMESTAMP(created_at) > ?
         ORDER BY createdAt DESC LIMIT 10
5. Return JSON: { notifications[], unreadCount, lastCheck=(now) }
6. Client-side: polls every 30s (pauses if tab hidden)
```

### Private Chat Polling (GET /private-chat/messages/new)

```
1. User authenticated (auth middleware)
2. Query params: ?chatId={id}, ?afterId={id}
3. Verify user is participant: SELECT * FROM privateChats 
   WHERE id=? AND (user1Id=? OR user2Id=?)
4. Query: SELECT id, userId, message, createdAt, upvoteCount, downvoteCount
         FROM privateChatMessages 
         WHERE chatId=? AND id > ? AND isDeleted=0
         ORDER BY createdAt DESC LIMIT 50
5. Return JSON: { messages[], hasMore }
```

---

## Threat Model & Mitigations

### 1. Network-Level Attacks (Passive Eavesdropping)

**Threat:** Tor/I2P exit node eavesdropping, ISP monitoring, traffic analysis.

**Mitigations:**
- TLS encryption (all traffic HTTPS)
- Forward secrecy via TLS 1.3
- Tor/I2P provides additional encryption layers
- Padding/timing obfuscation (defer if needed)

**Residual Risk:** MEDIUM. TLS can still leak metadata (DNS, hostname, request sizes). Defense: Use .onion addresses exclusively, avoid DNS leaks.

---

### 2. Server Compromise (Database / File System Breach)

**Threat:** Attacker gains filesystem or database access (misconfigured permissions, SQL injection, RCE).

**Mitigations:**
- All queries use PDO prepared statements (named parameters)
- Input validation via `Validator.php`
- Browser enforcement (`browser-check.php`) — not a security boundary
- Session data encrypted via TLS only (no data-at-rest encryption)
- Soft deletes keep deleted content in database (prevents forensic recovery but provides audit trail)

**Residual Risk:** **CRITICAL**. No data-at-rest encryption. Plaintext passwords in database (bug #1).

---

### 3. Session Hijacking / Fixation

**Threat:** Attacker steals or forges session cookie.

**Mitigations:**
- HttpOnly, Secure, SameSite=Strict cookie attributes
- Random bearer token in `$_SESSION['token']` (separate from PHP session ID)
- Token expiration after 24 hours (checked on every request)
- CSRF token rotation on TOTP verification
- Server-side session expiry (150 min)

**Residual Risk:** LOW. Cookies are resistant to JavaScript theft; SameSite=Strict prevents CSRF-based fixation. Token is stateful (no verification mechanism against database).

---

### 4. Brute-Force Attacks (Passwords, TOTP, CAPTCHA)

**Threat:** Attacker tries to guess credentials.

**Mitigations:**
- **Passwords:** 25+ character requirement, bcrypt with cost factor (default). No rate limiting on login (crypto-only defense).
- **TOTP:** 6-digit code, 30-second window, ±1 step tolerance. Guessing all 1M codes exhausts in ~16 min. No rate limiting but code expires.
- **CAPTCHA:** 5-character, 12-second expiry, 5-attempt lockout → 15-minute session lockout.
- **Email enumeration:** Signup doesn't leak whether email is registered (returns generic error).

**Residual Risk:** MEDIUM. No rate limiting on `/signin`. Attacker can attempt password guesses indefinitely (though bcrypt is slow). CAPTCHA lockout is session-local (not IP-based).

---

### 5. Authorization Bypass (Privilege Escalation)

**Threat:** Attacker modifies session to escalate access (e.g., set `accessLevel=15`).

**Mitigations:**
- Session data is immutable from client perspective (stored on server).
- Access checks happen server-side (middleware, controller logic).
- Admin checks: `$_SESSION['moderator'] === true` (bit-checked in middleware).
- Invite generation: `accessLevel >= 5` checked in controller.

**Residual Risk:** LOW. Session data is read from `$_SESSION` (populated by server on login). No mechanism for client to modify it.

---

### 6. Cross-Site Scripting (XSS)

**Threat:** Attacker injects JavaScript into page (stored or reflected).

**Mitigations:**
- DOMPurify on all dynamic content (frontend)
- Quill editor sanitization for HTML content
- Views use `htmlspecialchars()` for user output (where applicable)
- No `eval()` or `innerHTML` with user data

**Residual Risk:** MEDIUM. Browser enforcement is not a security boundary. User can use Firefox with XSS extensions disabled. Sanitization must be comprehensive.

---

### 7. SQL Injection

**Threat:** Attacker injects SQL via POST/GET parameters.

**Mitigations:**
- All queries use PDO prepared statements with named parameters
- No string interpolation into SQL
- Validator class checks input format before queries

**Residual Risk:** LOW. Prepared statements are the gold standard. No known SQL injection vectors.

---

### 8. CSRF (Cross-Site Request Forgery)

**Threat:** Attacker tricks user into making unintended state-changing request (on different site).

**Mitigations:**
- SameSite=Strict cookie attribute (browser-enforced)
- CSRF token in `$_SESSION['csrf_token']`, validated via `verifyCsrfToken()`
- Token rotation on TOTP verification

**Residual Risk:** LOW. SameSite=Strict is the primary defense. CSRF tokens are secondary validation.

---

### 9. DoS / Rate Limiting

**Threat:** Attacker floods server with requests.

**Mitigations:**
- Username generation: 7 requests per IP per hour (file cache)
- CAPTCHA: 5-attempt lockout per session (15 min)
- Reverse proxy (nginx) can implement rate limiting
- Email queue is asynchronous (doesn't block requests)

**Residual Risk:** MEDIUM. No global rate limiting on `/signin`, `/threads`, etc. Determined attacker can exhaust server resources.

---

### 10. Information Disclosure (Logs / Metadata)

**Threat:** Logs or metadata leak user information (usernames, IPs, request patterns).

**Mitigations:**
- Security logs include IP, method, URI, user-agent, threats detected
- Logs rotated hourly
- Deletion policy undefined
- No log encryption

**Residual Risk:** HIGH. Logs are valuable forensic artifacts. Should be encrypted, rotated, and securely deleted.

---

### 11. Plaintext Password Storage (DATABASE COMPROMISE)

**Threat:** If database is breached, attackers obtain plaintext passwords.

**Mitigations:** NONE. The `passwords` table stores plaintext passwords intentionally (audit log?).

**Residual Risk:** **CRITICAL**. This is the most severe vulnerability in the codebase. Immediate remediation required.

---

### 12. PHP `unserialize()` RCE (CACHE COMPROMISE)

**Threat:** If cache files are compromised or contain attacker-controlled data, arbitrary code execution.

**Mitigations:** NONE. `Cache::get()` calls `unserialize()` directly.

**Residual Risk:** **CRITICAL**. Any attacker-controlled serialized object in cache is RCE.

---

### 13. Hardcoded Database Credentials (CODE LEAK)

**Threat:** If source code is leaked, default DB credentials in code allow direct database access.

**Mitigations:** None for the hardcoded defaults.

**Residual Risk:** HIGH. Should be removed entirely; credentials must come from environment.

---

## Security Design Decisions

### Why Firefox-Only Enforcement?

Reduces attack surface to single browser vendor. Firefox is privacy-focused and less targeted than Chrome. Browser enforcement is NOT a security boundary but increases difficulty for mass scanners and bots.

### Why Invite-Only Registration?

Prevents spam and unlimited account creation. Creates audit trail of who brought users into the system. For darknet forums, limits exposure to outsiders.

### Why TOTP + Backup Codes?

TOTP is standardized (RFC 6238), hardware tokens optional, and resistant to phishing (code is time-bound). Backup codes provide recovery path if device is lost.

### Why File-Based Cache?

Avoids Redis/memcached dependency. Simple, self-contained. Downside: single-process cache (no cluster support). Suitable for small deployments.

### Why Soft Deletes?

Prevents accidental data loss. Maintains audit trail. Can satisfy retention policies (GDPR, etc.). Downside: requires careful filtering in queries.

### Why Session Lifetime = 150 Minutes?

2.5 hours is a reasonable compromise for darknet forums. Long enough to reduce auth fatigue, short enough to limit session hijacking window. Adjust for threat model.

### Why Custom DI Container?

No external dependencies → smaller attack surface, easier to audit. Trade-off: no battle-tested container; more maintenance burden.

### Why Exact URI Matching (No Route Parameters)?

Simpler router, fewer edge cases. Downside: no `/users/{id}` style routes. Routes are hardcoded (fine for small app).

---

## Operational Security (Dark Web Deployment)

### Network Configuration

1. **Reverse Proxy (nginx on Tor/I2P):**
   - Terminate TLS
   - Strip headers: X-Forwarded-For, X-Real-IP (unless trusted)
   - Block suspicious user-agents (automated scanning)
   - Implement rate limiting by IP

2. **Database:**
   - Run on isolated network (not publicly routable)
   - Restrict access to PHP application only
   - Enable MySQL binlog encryption (if backups)
   - Consider InnoDB transparent encryption

3. **File Permissions:**
   - `/Backend/Core/cache/`: 0700 (application-only)
   - `/Backend/Core/email_queue/`: 0700
   - `/logs/`: 0700 (no world read)
   - `.env`: 0600 (application-only)

### Log Management

- Logs are hourly files (`security_YYYY-MM-DD_HH.log`)
- Implement log rotation + compression
- Consider:
  - Encryption at rest
  - Removal of sensitive data (passwords, tokens)
  - Offsite archival with secure deletion
  - syslog integration with centralized logging

### Backup & Recovery

- Database backups should be encrypted (GPG)
- Store offsite with secure deletion schedule
- Test restore procedures regularly
- Consider: point-in-time recovery, log-based recovery

### Session State Handling

- Sessions stored in default PHP session handler (files or configured backend)
- Consider: Redis with TTL, memcached (encrypted)
- Implement: session cleanup on logout, garbage collection

### Threat Detection & Response

- Monitor security logs for attack patterns
- Implement alerting on:
  - Multiple failed CAPTCHAs (account takeover attempt)
  - Multiple TOTP verification failures
  - Admin endpoint access (unauthorized)
  - Unusual access patterns (automated scraping)

---

## Performance Considerations

### Database

- Indexes on: `users(email)`, `users(username)`, `threads(userId, status)`, `threads(createdAt)`
- Stored procedures for vote counting (atomic, efficient)
- Soft deletes require `WHERE isDeleted=0` on all queries

### Caching Strategy

- Rate limit data cached per IP (username generation)
- Invite codes cached post-signup
- No expensive queries cached (chat messages, notifications)

### Frontend

- Long-polling for chat/notifications (not WebSocket — no persistent connections)
- Pagination for threads/comments (no infinite scroll to limit memory usage)
- Quill editor handles large HTML content (sanitization may slow page load)

---

## Future Hardening Opportunities

1. **Data-at-Rest Encryption:** Implement transparent encryption for database and file storage.
2. **Key Rotation:** Implement periodic CSRF token rotation, TOTP secret rotation for long-lived accounts.
3. **Zero-Knowledge Architecture:** Consider end-to-end encryption for messages (server cannot read content).
4. **Decentralization:** Explore distributed consensus for moderation (blockchain-like) to prevent single-admin takeover.
5. **Hardware Security:** Consider hardware security modules (HSMs) for key storage.
6. **Continuous Security Audits:** Third-party security audits, fuzzing, static analysis.

---

## Compliance & Regulatory Notes

- **GDPR:** Soft deletes alone don't satisfy right-to-deletion. Implement crypto-shredding (encryption key deletion).
- **CCPA:** Privacy policy must disclose data collection (logs, email queue, notifications).
- **No Jurisdiction:** Dark web deployment may circumvent regulations, but don't assume immunity.

---

**Document Version:** 1.0  
**Date:** 2026-06-22  
**Status:** Initial comprehensive design review completed.
