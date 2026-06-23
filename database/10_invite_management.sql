-- Invite Management System (Superadmin only)
-- 2026-06-23

use forum;

-- ============================================================
-- ENHANCE INVITE CODES TABLE FOR BETTER TRACKING
-- ============================================================

ALTER TABLE inviteCodes ADD COLUMN IF NOT EXISTS isRevoked BOOLEAN DEFAULT FALSE;
ALTER TABLE inviteCodes ADD COLUMN IF NOT EXISTS revokedBy CHAR(36) NULL;
ALTER TABLE inviteCodes ADD COLUMN IF NOT EXISTS revokedAt TIMESTAMP NULL;
ALTER TABLE inviteCodes ADD COLUMN IF NOT EXISTS expiresAt TIMESTAMP NULL;
ALTER TABLE inviteCodes ADD COLUMN IF NOT EXISTS maxUses INT DEFAULT 1;
ALTER TABLE inviteCodes ADD COLUMN IF NOT EXISTS timesUsed INT DEFAULT 0;
ALTER TABLE inviteCodes ADD COLUMN IF NOT EXISTS metadata JSON;

CREATE INDEX idx_invites_revoked ON inviteCodes(isRevoked);
CREATE INDEX idx_invites_expires ON inviteCodes(expiresAt);
CREATE INDEX idx_invites_generator ON inviteCodes(generatorId);
CREATE INDEX idx_invites_used ON inviteCodes(used);

-- Add foreign key for revokedBy if not exists
ALTER TABLE inviteCodes
ADD CONSTRAINT fk_invites_revokedby
FOREIGN KEY (revokedBy) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================================
-- INVITE BATCH TRACKING (for bulk operations)
-- ============================================================

CREATE TABLE IF NOT EXISTS invite_batches (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    generatorId CHAR(36) NOT NULL,
    batchName VARCHAR(100),
    totalCodes INT NOT NULL,
    codesUsed INT DEFAULT 0,
    codesRevoked INT DEFAULT 0,
    expiresAt TIMESTAMP NULL,
    metadata JSON,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generatorId) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_batch_generator (generatorId),
    KEY idx_batch_created (createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link invites to batches
ALTER TABLE inviteCodes ADD COLUMN IF NOT EXISTS batchId CHAR(36) NULL;
ALTER TABLE inviteCodes ADD CONSTRAINT fk_invites_batch FOREIGN KEY (batchId)
    REFERENCES invite_batches(id) ON DELETE SET NULL;

-- ============================================================
-- INVITE ANALYTICS TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS invite_analytics (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    generatorId CHAR(36) NOT NULL,
    inviteCode VARCHAR(50) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usedAt TIMESTAMP NULL,
    usedBy CHAR(36) NULL,
    revokedAt TIMESTAMP NULL,
    expirationDays INT,
    FOREIGN KEY (generatorId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (usedBy) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_analytics_generator (generatorId),
    KEY idx_analytics_used (usedAt),
    KEY idx_analytics_created (createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
