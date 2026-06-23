# 🎫 Invite Management System

Complete documentation for the superadmin invite management system.

---

## 📋 Overview

**Superadmin-only** feature for managing invite codes:
- ✅ Create single or bulk invites
- ✅ View all invites with filtering
- ✅ Revoke/disable invites
- ✅ Track usage per invite
- ✅ Batch management
- ✅ CSV export
- ✅ Statistics dashboard

**Access:** Level 5+ (Superadmin only)  
**Route:** `/admin/invites-dashboard`

---

## 🚀 Features

### 1. Create Invites

**Single Invite:**
- Generate one invite code
- Set expiration (0 = never)
- Instant display + copy button

**Bulk Invites:**
- Create 1-10,000 codes in one batch
- Name your batch
- Set expiration for all
- Download as CSV
- Copy all codes at once

**Invite Code Format:**
- 12 characters, readable format (XXX-XXX-XXX)
- No confusing characters (0/O, 1/I/l)
- Unique per code

### 2. View Invites

**Filtering:**
- All
- Available (unused, not revoked, not expired)
- Used
- Revoked
- Expired

**Information per invite:**
- Code (copyable)
- Creation date
- Expiration date (if set)
- Used by (username if claimed)
- Status badge

**Search:**
- Live search by code

### 3. Manage Invites

**Per-Invite Actions:**
- Revoke (disable permanently)
- Copy code

**Batch Actions:**
- View all codes in batch
- Revoke entire batch
- View batch statistics

### 4. Statistics

**Dashboard shows:**
- Total invites created
- Available (unused, not revoked, not expired)
- Used (claimed by users)
- Revoked (disabled)
- Expired (past expiration date)

### 5. Export

**CSV Export:**
- All codes or batch codes
- Format: Code, Expires, Used
- Timestamp-named file
- Download or copy

---

## 🛠️ Technical Details

### Database Schema

**New Tables:**

```sql
-- Batch tracking
CREATE TABLE invite_batches (
    id CHAR(36) PRIMARY KEY,
    generatorId CHAR(36),
    batchName VARCHAR(100),
    totalCodes INT,
    codesUsed INT DEFAULT 0,
    codesRevoked INT DEFAULT 0,
    expiresAt TIMESTAMP NULL,
    metadata JSON,
    createdAt TIMESTAMP
);

-- Analytics
CREATE TABLE invite_analytics (
    id CHAR(36) PRIMARY KEY,
    generatorId CHAR(36),
    inviteCode VARCHAR(50),
    createdAt TIMESTAMP,
    usedAt TIMESTAMP NULL,
    usedBy CHAR(36) NULL,
    revokedAt TIMESTAMP NULL,
    expirationDays INT
);
```

**Enhanced Columns (inviteCodes table):**

```sql
isRevoked BOOLEAN DEFAULT FALSE
revokedBy CHAR(36) NULL
revokedAt TIMESTAMP NULL
expiresAt TIMESTAMP NULL
maxUses INT DEFAULT 1
timesUsed INT DEFAULT 0
metadata JSON
batchId CHAR(36) NULL
```

**Indexes:**
- `idx_invites_revoked` (isRevoked)
- `idx_invites_expires` (expiresAt)
- `idx_invites_generator` (generatorId)
- `idx_batch_generator` (generatorId)
- `idx_batch_created` (createdAt)

### Backend Controllers

| File | Method | Path | Purpose |
|------|--------|------|---------|
| `invites.php` | GET | `/admin/invites` | Get invites with filters |
| `invites.php` | POST | `/admin/invites` | Create single/bulk invites |
| `invites.php` | DELETE | `/admin/invites` | Revoke invite/batch |
| `invites-batches.php` | GET | `/admin/invites/batches` | Get batch list |
| `invites-export.php` | GET | `/admin/invites/export` | Download as CSV |
| `invites-dashboard.php` | GET | `/admin/invites-dashboard` | Dashboard UI |

### Backend Utility Class

**InviteManager.php (140+ lines)**

Methods:
- `isSuperAdmin($userId)` — Check if level 5+
- `getInviteById($inviteId)` — Get single invite
- `getAllInvites($limit, $offset, $filters)` — List invites with filtering
- `getInviteStats()` — Get statistics
- `createInvite($generatorId, $expirationDays, $maxUses)` — Single invite
- `createBulkInvites($generatorId, $count, $expirationDays, $batchName)` — Bulk invites
- `revokeInvite($inviteId, $revokedBy)` — Revoke single
- `revokeBatch($batchId, $revokedBy)` — Revoke batch
- `exportInviteCodes($batchId, $format)` — Export CSV
- `getBatches($limit, $offset, $generatorId)` — Get batch list
- `getBatch($batchId)` — Get single batch

### Frontend

**Dashboard Components:**

1. **Overview Tab**
   - Statistics grid (5 cards)
   - Quick action buttons
   - Recent invites list

2. **Create Tab**
   - Single invite form
   - Bulk invite form
   - Code display with copy

3. **View Tab**
   - Filter buttons
   - Search box
   - Invite list
   - Load more button

4. **Batches Tab**
   - Batch cards
   - Statistics per batch
   - View/revoke actions

**JavaScript (invite-manager.js, 350+ lines)**

Functions:
- `loadStats()` — Fetch and display statistics
- `loadInvites(filter)` — Load filtered invites
- `loadBatches()` — Load batch list
- `createInvite()` — Form submission
- `createBulkInvites()` — Bulk form submission
- `revokeInvite(inviteId)` — Revoke single
- `revokeBatch(batchId)` — Revoke batch
- `exportInvites()` — Download CSV
- `searchInvites(query)` — Live search
- Filter/tab switching

---

## 📍 Access Control

**Only Level 5+ (Superadmin) can:**
- View dashboard
- Create invites
- Revoke invites
- Export invites
- View statistics

**Lower levels:**
- Redirected to 403 error
- No access to endpoints

**Check in:**
- `invites.php` controller
- `invites-dashboard.php` controller
- `InviteManager::isSuperAdmin()`

---

## 🔄 API Reference

### Get Invites

```
GET /admin/invites?limit=50&offset=0&status=available&generatorId=xxx
```

**Response:**
```json
{
    "success": true,
    "message": "Invites retrieved",
    "details": {
        "invites": [...],
        "stats": {
            "total": 100,
            "available": 45,
            "used": 50,
            "revoked": 5,
            "expired": 0
        },
        "limit": 50,
        "offset": 0
    }
}
```

### Create Invite

```
POST /admin/invites
{
    "csrf_token": "...",
    "action": "single",
    "expirationDays": 7
}
```

**Response:**
```json
{
    "success": true,
    "message": "Invite created",
    "details": {
        "invite": {
            "id": "uuid",
            "code": "ABC-DEF-GHI",
            "expiresAt": "2026-07-01T...",
            "maxUses": 1
        }
    }
}
```

### Create Bulk Invites

```
POST /admin/invites
{
    "csrf_token": "...",
    "action": "bulk",
    "count": 100,
    "expirationDays": 30,
    "batchName": "Wave 1"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Created 100 invites",
    "details": {
        "batch": {
            "batchId": "uuid",
            "count": 100,
            "codes": ["ABC-...", "DEF-...", ...],
            "expiresAt": "2026-07-23T..."
        }
    }
}
```

### Revoke Invite

```
DELETE /admin/invites
{
    "csrf_token": "...",
    "inviteId": "uuid"
}
```

### Revoke Batch

```
DELETE /admin/invites
{
    "csrf_token": "...",
    "batchId": "uuid"
}
```

### Get Batches

```
GET /admin/invites/batches?limit=20&offset=0
```

### Export CSV

```
GET /admin/invites/export?batchId=uuid

# Returns CSV file:
# Code,Expires,Used
# ABC-DEF-GHI,2026-07-01,No
# ...
```

---

## 📊 Statistics

The dashboard shows real-time statistics:

- **Total:** All invites ever created
- **Available:** Unused + not revoked + not expired
- **Used:** Claimed by users
- **Revoked:** Manually disabled
- **Expired:** Past expiration date

Calculation:
```sql
SELECT
    COUNT(*) as total,
    SUM(used = 0 AND isRevoked = 0 AND (expiresAt IS NULL OR expiresAt > NOW())) as available,
    SUM(used = 1) as used,
    SUM(isRevoked = 1) as revoked,
    SUM(expiresAt IS NOT NULL AND expiresAt < NOW()) as expired
FROM inviteCodes
```

---

## 🔒 Security

- **Access Control:** Level 5+ check on every request
- **CSRF Protection:** All POST/DELETE require token
- **Audit Logging:** All actions logged via `AdvancedFeatures::logAudit()`
- **Rate Limiting:** Max 10,000 codes per bulk operation
- **Input Validation:** Expiration 0-365 days, count 1-10,000
- **SQL Injection:** Prepared statements only

---

## 🎯 Usage Guide

### Creating a Single Invite

1. Go to `/admin/invites-dashboard`
2. Click **Create Invites** tab
3. In "Create Single Invite" section
4. Set expiration days (0 = never)
5. Click **Generate Invite**
6. Code displays in result box
7. Click **Copy Code** to clipboard

### Creating Bulk Invites

1. Go to `/admin/invites-dashboard`
2. Click **Create Invites** tab
3. In "Create Bulk Invites" section
4. Enter number of codes (1-10,000)
5. Set expiration days
6. Name your batch (optional)
7. Click **Generate Batch**
8. Codes display in textarea
9. Click **Copy All** or **Download CSV**

### Viewing Invites

1. Go to `/admin/invites-dashboard`
2. Click **View All** tab
3. Use filter buttons (All, Available, Used, etc.)
4. Search by code if needed
5. Click **Load More** for pagination
6. Click **Revoke** to disable a code

### Managing Batches

1. Go to `/admin/invites-dashboard`
2. Click **Batches** tab
3. See all batch statistics
4. Click **View Codes** to see individual invites
5. Click **Revoke All** to disable entire batch

### Exporting Codes

1. Go to `/admin/invites-dashboard`
2. Click **Export Codes (CSV)** button
3. File downloads as `invites_YYYY-MM-DD.csv`
4. Format: Code, Expires, Used

---

## 📋 Invite Lifecycle

```
Created → Available → [Used or Revoked or Expired]

States:
1. Available  = unused + not revoked + not expired
2. Used       = claimed by user during signup
3. Revoked    = manually disabled by admin
4. Expired    = past expiration date
```

---

## 🔍 Audit Logging

All actions logged via `AdvancedFeatures::logAudit()`:

```
Action                  Entity Type    Details
create_invite           invite         code, expiresAt, maxUses
create_bulk_invites     invite_batch   count, batchName, expirationDays
revoke_invite           invite         (none)
revoke_batch            invite_batch   (none)
```

View logs at `/admin/management` → **Audit Logs** tab.

---

## 🚨 Common Issues

### All codes revoked but still showing as available
- Check that `isRevoked` column was set correctly
- Run: `SELECT COUNT(*) FROM inviteCodes WHERE isRevoked = 1`

### CSV download not working
- Verify superadmin access (level 5+)
- Check browser console for errors
- Try export from command line

### Bulk creation too slow
- Limit to 1,000-5,000 codes per batch
- Use multiple smaller batches instead

### Codes expiring incorrectly
- Verify server timezone is correct
- Check database time: `SELECT NOW()`
- Expiration uses server time, not client time

---

## 📈 Statistics Example

```
Total Invites: 1,250
├─ Available: 345 (27%)
├─ Used: 800 (64%)
├─ Revoked: 100 (8%)
└─ Expired: 5 (0.4%)
```

---

## 🔐 Security Best Practices

1. **Share codes securely**
   - Use encrypted email
   - Avoid plaintext in logs
   - Delete exported files after use

2. **Monitor usage**
   - Check statistics regularly
   - Review audit logs for suspicious activity
   - Revoke unused codes after period

3. **Batch management**
   - Name batches by distribution method
   - Track who generated each batch
   - Revoke batches when promotion ends

4. **Expiration strategy**
   - Set reasonable expiration (7-30 days typical)
   - No expiration for permanent codes
   - Balance security with usability

---

## 🚀 Implementation Files

| File | Lines | Purpose |
|------|-------|---------|
| `database/10_invite_management.sql` | 50+ | Schema + indexes |
| `Backend/Utils/InviteManager.php` | 140+ | Utility class |
| `Backend/controllers/admin/invites.php` | 60+ | Main API |
| `Backend/controllers/admin/invites-batches.php` | 25+ | Batch endpoint |
| `Backend/controllers/admin/invites-export.php` | 20+ | CSV export |
| `Backend/controllers/admin/invites-dashboard.php` | 15+ | Dashboard view |
| `frontend/views/admin/invites-dashboard.view.php` | 400+ | Dashboard UI |
| `public/javascripts/invite-manager.js` | 350+ | Frontend logic |

---

## 📚 Related Documentation

- **DELIVERY_SUMMARY_ADVANCED_FEATURES.md** — Other admin features
- **CLAUDE.md** — Project configuration
- **docs/database.md** — Database schema reference

---

## ✅ Testing Checklist

- [ ] Create single invite
- [ ] Create 100-code batch
- [ ] Copy code to clipboard
- [ ] Filter invites (all statuses)
- [ ] Search by code
- [ ] Revoke single invite
- [ ] Revoke entire batch
- [ ] View batch statistics
- [ ] Export as CSV
- [ ] Check audit logs
- [ ] Verify access control (non-superadmin redirected)
- [ ] Test code expiration (set date to past)
- [ ] Load more pagination
- [ ] Mobile responsive UI

---

**Commit:** Include with `cf2555f`  
**Status:** ✅ Complete
