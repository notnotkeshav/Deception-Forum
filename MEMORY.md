# MEMORY.md — Known Issues, Technical Debt & Lessons Learned

## Overview

This file tracks significant issues, design decisions, architectural debt, and lessons learned during development of Deception Forum. Unlike CLAUDE.md (operational reference) or DESIGN.md (architectural overview), this is a running log of problems encountered and their resolutions (or pending work).

---

## Critical Issues (Must Fix Before Production)

### 1. Plaintext Password Storage in `passwords` Table

**Status:** OPEN — CRITICAL  
**Discovery:** 2026-06-22 (security audit)  
**File:** `database/01_auth.sql` (line 26), `Backend/controllers/auth/signup.php` (line 127–131)

**Problem:**
The `passwords` table has a `password VARCHAR(255)` column that stores the plaintext password:
```sql
CREATE TABLE passwords (
    id CHAR(36) PRIMARY KEY,
    userId CHAR(36),
    passwordHash VARCHAR(255),
    password VARCHAR(255),  -- ⚠️ PLAINTEXT
    ...
);
```

And signup explicitly stores it:
```php
$db->query("INSERT INTO passwords (userId, passwordHash, password) VALUES (...)", [
    ":password" => $_POST['password']  // ⚠️ PLAINTEXT
]);
```

**Why It Exists:**
Unknown. Hypothesis: intended as an audit log ("who used what password") but violates all security practices.

**Impact:**
- **HIGH/CRITICAL:** If database is breached, all passwords are immediately compromised
- Users likely reuse passwords across services → cascading compromise
- Violates GDPR, CCPA, PCI-DSS, and darknet security principles
- Enables insider attacks (DBA with database access)

**Solution:**
1. **Immediate:** Delete plaintext column: `ALTER TABLE passwords DROP COLUMN password;`
2. **If audit log is needed:** Create separate `password_audit` table with only hashes + metadata (timestamp, action, hash of new password)
3. **Ensure:** No code references the plaintext column
4. **Test:** Verify signup flow works with hash-only storage

**Timeline:** URGENT — fix before next deployment.

---

### 2. PHP `unserialize()` in Cache → Remote Code Execution

**Status:** OPEN — CRITICAL  
**Discovery:** 2026-06-22 (security audit)  
**File:** `Backend/Core/Cache.php` (lines 22, 50)

**Problem:**
```php
public function get($key) {
    $data = file_get_contents($filePath);
    return unserialize($data);  // ⚠️ RCE VECTOR
}

public function clearExpired() {
    foreach (glob($this->cacheDir . '*.cache') as $file) {
        $data = unserialize(file_get_contents($file));  // ⚠️ RCE VECTOR
        ...
    }
}
```

If an attacker can write to `/Backend/Core/cache/`, they can place a serialized PHP object exploit. When `Cache::get()` or `clearExpired()` runs, arbitrary code executes.

**Why It Exists:**
`unserialize()` is the easiest way to serialize/deserialize in PHP. No consideration of security implications.

**Impact:**
- **CRITICAL:** Full server compromise if cache directory is writable
- Exploitable via:
  - Directory traversal in other code
  - Race condition during file write
  - Symlink attack if OS permissions are weak
  - Shared hosting environment

**Solution:**
1. **Replace with JSON:**
   ```php
   public function get($key) {
       $data = file_get_contents($filePath);
       return json_decode($data, true);
   }
   ```
2. **Update `set()` to use json_encode:**
   ```php
   file_put_contents($filePath, json_encode($data));
   ```
3. **Audit:** Verify no other `unserialize()` calls in codebase.
4. **Test:** All cache operations (rate limiting, invite codes, etc.)

**Timeline:** URGENT — fix before deployment. Trivial change.

---

### 3. Hardcoded Database Credentials in Code

**Status:** OPEN — HIGH  
**Discovery:** 2026-06-22 (security audit)  
**File:** `Backend/Core/Database.php` (line 16)

**Problem:**
```php
public function __construct(array $config, string $username = "root", string $password = "11112222")
```

Hardcoded defaults appear in source code. If code is leaked (GitHub, repository mirror), credentials are public.

**Why It Exists:**
Convenience for development. Bootstrap script (index.php) is supposed to override with environment variables.

**Impact:**
- **HIGH:** Code leak → immediate database compromise
- Default credentials are weak and obvious (user="root", pass="11112222")
- Risk: source code in GitHub, deployment scripts, container images

**Solution:**
1. **Remove defaults entirely:**
   ```php
   public function __construct(array $config, string $username, string $password)
   {
       // No default values; fail loudly if missing
   }
   ```
2. **Require environment variables:**
   ```php
   $username = getenv('DB_USERNAME') or die('DB_USERNAME not set');
   $password = getenv('DB_PASSWORD') or die('DB_PASSWORD not set');
   ```
3. **Document:** .env example must have placeholder (not real values)
4. **Test:** Verify code fails cleanly if .env is missing

**Timeline:** URGENT — fix before next deployment.

---

## High-Priority Issues

### 4. No Data-at-Rest Encryption

**Status:** OPEN — MEDIUM/HIGH  
**Discovery:** 2026-06-22 (security audit)

**Problem:**
Database, cache files, email queue, and logs are stored unencrypted on disk. A server seizure (warrant, raid, theft) exposes all data plaintext.

**Impact:**
- Database: User emails, usernames, TOTP secrets, session tokens, plaintext passwords (issue #1)
- Cache: IP rate limiting data, invite codes, usernames
- Email queue: Password reset tokens, 2FA codes
- Logs: Request URIs, threat detections, user activity

**Why Not Fixed Yet:**
Encryption adds complexity (key management, performance overhead). Decided to accept risk initially.

**Solution Options:**
1. **Database Level:** Enable InnoDB Transparent Encryption (TDE) — minimal code changes
2. **Disk Level:** LUKS/dm-crypt for filesystem — requires deployment change
3. **Application Level:** Encrypt fields before storage (Argon2ID + AES-GCM) — complex
4. **Key Management:** Use HSM or Key Management Service (AWS KMS, Vault) — operational burden

**Recommended:** Implement InnoDB TDE for database, LUKS for cache/queue directories, encrypt logs on rotation.

**Timeline:** Before production deployment on exposed hardware.

---

### 5. Admin Middleware Missing Null Check

**Status:** OPEN — MEDIUM  
**Discovery:** 2026-06-22 (security audit)  
**File:** `Backend/Middleware/Admin.php` (line 15)

**Problem:**
```php
if (!$_SESSION['moderator']) {  // No isset() check
    sendJsonResponse(false, "You do not have moderators permissions...", [], 403);
}
```

If `$_SESSION['moderator']` is unset, PHP issues E_WARNING and the condition silently passes (false == false → continue).

**Impact:**
- **MEDIUM:** Non-admin user might not get the intended 403 response
- Better to fail loudly and log the error

**Solution:**
```php
if (empty($_SESSION['moderator']) || !$_SESSION['moderator']) {
    sendJsonResponse(false, "Access denied", [], 403);
}
```

Or more explicitly:
```php
if (!isset($_SESSION['moderator']) || !$_SESSION['moderator']) {
    error_log("Unauthorized access attempt by user: " . ($_SESSION['userId'] ?? 'unknown'));
    sendJsonResponse(false, "Access denied", [], 403);
}
```

**Timeline:** Next code review / pull request.

---

### 6. Session Cleanup Not Implemented

**Status:** OPEN — MEDIUM  
**Discovery:** 2026-06-22 (design review)

**Problem:**
- Sessions stored in default PHP handler (typically `/var/lib/php/sessions/`)
- No explicit cleanup on logout (no `session_destroy()` call)
- PHP garbage collection may not run frequently enough
- Old session files remain on disk indefinitely

**Impact:**
- Stale session data persists (forensic artifact)
- Disk space accumulation
- Potential for session fixation if GC is disabled

**Solution:**
1. Implement explicit logout:
   ```php
   session_destroy();
   ```
2. Set up PHP GC: `session.gc_probability = 1` (or use cron)
3. Consider: Redis session handler with TTL-based auto-deletion
4. Implement: Secure session deletion (overwrite with zeros before unlink)

**Timeline:** Next maintenance release.

---

## Technical Debt

### 7. Cache `clearExpired()` Never Called

**Status:** OPEN — LOW  
**File:** `Backend/Core/Cache.php` (line 47)

**Problem:**
The `clearExpired()` method is defined but never called. Cache files accumulate indefinitely.

**Solution:**
1. Add cron job: `*/15 * * * * php /path/to/notification-cleanup.php`
2. Or: Implement background worker to clean cache

**Timeline:** Next release.

---

### 8. Logging Retention Policy Undefined

**Status:** OPEN — LOW  
**Discovery:** 2026-06-22 (security audit)

**Problem:**
Security logs rotate hourly (`security_YYYY-MM-DD_HH.log`) but no deletion schedule. Logs accumulate forever (forensic goldmine for attackers).

**Solution:**
1. Implement log rotation with retention: `logrotate` config
   ```
   /path/to/logs/security_*.log {
       daily
       rotate 30
       compress
       delaycompress
       missingok
       notifempty
   }
   ```
2. Consider: Encrypt logs on rotation, ship to secure storage
3. Set retention policy: 7 days (default), adjustable per deployment

**Timeline:** Before production.

---

### 9. No Rate Limiting on Authentication Endpoints

**Status:** OPEN — MEDIUM  
**Discovery:** 2026-06-22 (threat analysis)

**Problem:**
`/signin` has no rate limiting. Attacker can attempt unlimited password guesses. Bcrypt is slow (~80ms per hash) but persistent attacker can exhaust CPU.

**Solution:**
1. **IP-based:** Track failed attempts per IP, lockout after N failures
   ```php
   $cache->set("signin_attempts:{$ip}", count + 1, 3600);
   if ($cache->get("signin_attempts:{$ip}") > 5) abort(429, "Too many attempts");
   ```
2. **User-based:** Track failed attempts per username, lockout after N
3. **Reverse proxy:** nginx `limit_req` module
4. **Progressive delay:** Increase TOTP code generation time with each failed attempt

**Timeline:** Before production.

---

### 10. CSRF Token Rotation Limited

**Status:** OPEN — LOW  
**Discovery:** 2026-06-22 (design review)

**Problem:**
CSRF token is rotated only on TOTP verification (step 2 of auth). Not rotated on every request (rare practice but more defensive).

**Impact:**
- If token is leaked mid-request, attacker has ~2.5 hours to exploit
- Standard practice is to rotate per request (performance trade-off)

**Solution:**
Consider rotation on every form submission (frontend + backend coordination). Low priority; SameSite=Strict is primary defense.

**Timeline:** Low priority.

---

### 11. No End-to-End Encryption (E2EE)

**Status:** DESIGN DECISION — NOT IMPLEMENTED  
**Discovery:** 2026-06-22 (threat analysis)

**Problem:**
Server reads all message content (threads, comments, chats). If server is compromised, all communications are exposed.

**Impact:**
- Darknet forums need maximum secrecy
- Server admin, database admin, or attacker can read all messages

**Solution:**
Implement client-side E2EE:
1. Generate public/private keypair per user (ECDH or RSA)
2. Encrypt message with recipient's public key before sending
3. Server stores encrypted content (cannot read)
4. Recipient decrypts with private key

**Complexity:**
- Requires key management (exchange, revocation, rotation)
- Backup codes for key recovery
- Performance overhead
- Breaks server-side search/moderation

**Timeline:** Future enhancement (v2.0).

---

### 12. No Decentralized Moderation

**Status:** DESIGN DECISION — NOT IMPLEMENTED  

**Problem:**
Single admin/moderator team controls all moderation decisions. Vulnerable to:
- Admin takeover
- Compromise of moderator accounts
- Censorship by single authority

**Solution:**
Implement Byzantine-Fault-Tolerant consensus for moderation (Raft, Hotstuff, or simple voting).

**Complexity:**
- Significant architectural change
- Requires multi-node deployment
- Consensus latency (seconds to minutes)

**Timeline:** Major redesign (future version).

---

### 13. Browser Enforcement Not a Security Boundary

**Status:** DESIGN KNOWN ISSUE  
**Discovery:** Initial design phase

**Problem:**
Firefox-only enforcement can be bypassed by:
- Spoofing user-agent in requests (curl, Node.js, Python)
- Using browser automation tools with Firefox UA
- Modifying Firefox to appear as different UA

**Why Kept:**
Filters out automated scanning (good for ops), raises bar for casual attackers, signals privacy-focused deployment.

**Impact:**
- LOW to SECURITY (not intended as security boundary)
- HIGH to UX (ensures legitimate users use proper browser)

**Timeline:** Keep as-is; document as "obfuscation, not security."

---

## Lessons Learned

### 1. Never Store Plaintext Passwords

**Lesson:** Plaintex password storage violates every security standard (GDPR, OWASP, NIST). Discovered it exists; don't know why.

**Action:** Document reason (if any) or delete immediately.

---

### 2. PHP `unserialize()` Is Dangerous

**Lesson:** Custom serialization is fragile. Standard: use JSON, protobuf, or other format that doesn't execute code.

**Action:** Replaced all `unserialize()` with JSON (future).

---

### 3. Environment Variables, Not Hardcoded Credentials

**Lesson:** Source code is not secret (leaked, mirrored, shared). Credentials belong in secure configuration (env vars, vaults, HSMs).

**Action:** Remove hardcoded defaults, require environment variables.

---

### 4. Defense in Depth Applies Even to "Hardened" Systems

**Lesson:** Even with strict browser enforcement, TOTP, and logging, basic vulnerabilities (plaintext storage, unserialize()) can slip through.

**Action:** Multi-layer code review, static analysis (PHPStan), fuzzing.

---

### 5. Logging is a Double-Edged Sword

**Lesson:** Comprehensive security logging is valuable for threat detection, but logs themselves are forensic artifacts. Encrypt and rotate them.

**Action:** Implement log encryption, retention policy, secure deletion.

---

### 6. Session Management is Hard

**Lesson:** Session expiry, CSRF, fixation, hijacking — multiple threats. Mitigations (HttpOnly, SameSite, rotation) are needed in layers.

**Action:** Leverage well-tested libraries when possible (though this project avoids dependencies).

---

### 7. Custom Frameworks Have Trade-Offs

**Lesson:** No Composer dependencies means simpler attack surface, but also no battle-tested router, ORM, or session handler. More code to audit.

**Action:** Keep custom framework minimal; consider selective use of hardened libraries (cryptography, sanitization).

---

### 8. Darknet Deployments Need Extra Operational Security

**Lesson:** Tor/I2P hidden services need special care:
- Log cleanup (forensic resistance)
- Key rotation (prevent long-term compromise)
- Network isolation (no data exfil)
- Threat monitoring (unusual activity)

**Action:** Document operational runbook for darknet deployment (separate guide).

---

### 9. Zero Trust: Never Trust Client Input or Session Data

**Lesson:** Session data can be leaked, user input can be malicious. Validate everything server-side; don't trust client-provided tokens or IDs.

**Action:** Review all controllers for assumptions about session/input safety.

---

### 10. Performance vs. Security Trade-Offs

**Lesson:** Some hardening measures (encryption, logging, validation) have performance costs. Balance security goals with latency/throughput requirements.

**Action:** Profile application; optimize hot paths. Monitor under load.

---

## Architecture Decisions & Rationale

### Why No ORM?

**Decision:** Use raw PDO with prepared statements, no Eloquent/Doctrine.

**Rationale:**
- Simpler codebase (fewer dependencies)
- Easier to audit (all queries visible)
- Faster (no abstraction overhead)
- More control over performance (manual query optimization)

**Trade-Off:** More boilerplate, easier to make SQL mistakes (mitigated by prepared statements).

---

### Why File-Based Cache Over Redis?

**Decision:** `Backend/Core/Cache.php` uses filesystem, not Redis/memcached.

**Rationale:**
- No external service dependency
- Simpler deployment (single server)
- Suitable for small user bases

**Trade-Off:** Single-process cache (no clustering); slower than in-memory stores.

**Future:** Consider Redis with encryption for larger deployments.

---

### Why Custom DI Container?

**Decision:** Implement `Backend/Core/Container.php` instead of using an existing library.

**Rationale:**
- Single file, easy to audit
- Minimal overhead
- No external dependency

**Trade-Off:** Less tested than Laravel Container, Symfony DI, or Pimple.

---

### Why 150-Minute Session Lifetime?

**Decision:** `SESSION_LIFETIME_SECONDS = 150 * 60` (2.5 hours).

**Rationale:**
- Long enough to avoid frequent re-auth (reduces user friction)
- Short enough to limit session hijacking window (24h is too long)
- Middle ground for darknet forum use case

**Alternative:** 60 minutes (stricter); 24 hours (lenient).

**Future:** Make configurable per deployment; tighten for high-security sites.

---

### Why Soft Deletes?

**Decision:** `isDeleted BOOLEAN` column instead of hard DELETE.

**Rationale:**
- Accident recovery (accidentally deleted posts can be restored)
- Audit trail (what was deleted, when, why)
- Compliance (GDPR/CCPA historical data)

**Trade-Off:** Queries must filter `WHERE isDeleted=0`; requires discipline.

---

## Operational Runbook (To Be Expanded)

### Daily Tasks
- [ ] Monitor security logs for threats
- [ ] Check email queue for stuck jobs
- [ ] Verify database backups completed

### Weekly Tasks
- [ ] Review admin access logs
- [ ] Check disk space (cache, logs)
- [ ] Test session cleanup

### Monthly Tasks
- [ ] Rotate TOTP secrets (or schedule for rotation)
- [ ] Audit user access levels
- [ ] Review moderator permissions
- [ ] Backup database (encrypted)

### On Incident (Suspected Breach)
1. Isolate server from network
2. Snapshot filesystem (forensics)
3. Enable verbose logging
4. Review recent logs for IOCs
5. Check for unauthorized admin/moderator accounts
6. Force password resets for all users
7. Invalidate all sessions
8. Audit database for data exfil (SELECT to external systems)

---

## Testing & QA Notes

### Manual Security Testing Checklist

- [ ] Attempt to login without CAPTCHA (should require it)
- [ ] Attempt to signup with invalid email (should reject)
- [ ] Attempt to signup with weak password (should reject)
- [ ] Attempt to login with expired session (should redirect to TOTP renewal)
- [ ] Attempt to access admin endpoints as regular user (should 403)
- [ ] Attempt CSRF attack from different domain (browser should block)
- [ ] Attempt SQL injection in search/filter endpoints (should be neutralized)
- [ ] Test TOTP with ±30s clock skew (should work)
- [ ] Test TOTP backup code (should work once, then fail)
- [ ] Test CAPTCHA lockout (5 fails → 15-min lockout)
- [ ] Test private chat with deleted user (should handle gracefully)
- [ ] Test thread vote toggle (on/off/change vote)
- [ ] Verify browser-check blocks non-Firefox (test with curl, Chrome UA)

### Automated Testing (To Be Implemented)

- Unit tests for TOTP generation/verification
- Unit tests for password validation
- Unit tests for CAPTCHA generation/verification
- Integration tests for auth flow (signin → TOTP → full session)
- Integration tests for vote procedures (atomicity, concurrency)
- Fuzz testing for comment content (XSS detection)
- Load testing for concurrent logins (bcrypt CPU usage)

---

## Future Enhancements

### v1.1 (Near Term)
- [ ] Fix critical vulnerabilities (#1, #2, #3)
- [ ] Implement data-at-rest encryption
- [ ] Add IP-based rate limiting
- [ ] Implement log rotation + retention policy
- [ ] Add TOTP secret rotation endpoint

### v2.0 (Mid Term)
- [ ] End-to-end message encryption (E2EE)
- [ ] Decentralized moderation (consensus-based)
- [ ] WebSocket support (real-time notifications, no polling)
- [ ] Message reactions/emoji
- [ ] User banning/timeout management

### v3.0 (Long Term)
- [ ] Distributed deployment (multi-node consensus)
- [ ] Sharding (horizontal scaling)
- [ ] Full-text search (encrypted)
- [ ] Mobile app (Tor Browser on mobile)

---

## Document Metadata

**Created:** 2026-06-22  
**Version:** 1.0  
**Status:** ACTIVE  
**Last Updated:** 2026-06-22  

**Maintainers:** Security team  
**Review Frequency:** Monthly (or after incidents)

---

**End of MEMORY.md**
