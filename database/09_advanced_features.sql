-- Advanced Features: Suspension, Post Hiding, Themes, Plugins, Keyboard Shortcuts, Drafts
-- 2026-06-23

use forum;

-- ============================================================
-- 1. USER SUSPENSION
-- ============================================================

ALTER TABLE users ADD COLUMN isSuspended BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN suspendedAt TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN suspendReason TEXT NULL;

CREATE INDEX idx_users_suspended ON users(isSuspended);


-- ============================================================
-- 2. POST HIDING (Moderation)
-- ============================================================

ALTER TABLE threads ADD COLUMN isHidden BOOLEAN DEFAULT FALSE;
ALTER TABLE threads ADD COLUMN hiddenBy CHAR(36) NULL;
ALTER TABLE threads ADD COLUMN hiddenReason TEXT NULL;
ALTER TABLE threads ADD COLUMN hiddenAt TIMESTAMP NULL;

ALTER TABLE comments ADD COLUMN isHidden BOOLEAN DEFAULT FALSE;
ALTER TABLE comments ADD COLUMN hiddenBy CHAR(36) NULL;
ALTER TABLE comments ADD COLUMN hiddenReason TEXT NULL;
ALTER TABLE comments ADD COLUMN hiddenAt TIMESTAMP NULL;

CREATE INDEX idx_threads_hidden ON threads(isHidden);
CREATE INDEX idx_comments_hidden ON comments(isHidden);

ALTER TABLE threads ADD FOREIGN KEY (hiddenBy) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE comments ADD FOREIGN KEY (hiddenBy) REFERENCES users(id) ON DELETE SET NULL;


-- ============================================================
-- 3. THEME SYSTEM (Customization for Level 4+)
-- ============================================================

CREATE TABLE themes (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    isPublic BOOLEAN DEFAULT FALSE,
    baseTheme ENUM('dark', 'light', 'custom') DEFAULT 'custom',
    colors JSON NOT NULL,
    cssVars JSON NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_theme_name (userId, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_theme_preferences (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL UNIQUE,
    activeThemeId CHAR(36) NULL,
    systemTheme ENUM('auto', 'dark', 'light') DEFAULT 'auto',
    customCSS TEXT,
    enableCustomCSS BOOLEAN DEFAULT FALSE,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (activeThemeId) REFERENCES themes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default preferences for all users
INSERT INTO user_theme_preferences (userId)
SELECT id FROM users
WHERE id NOT IN (SELECT userId FROM user_theme_preferences);

CREATE INDEX idx_themes_public ON themes(isPublic);
CREATE INDEX idx_themes_user ON themes(userId);


-- ============================================================
-- 4. PLUGIN SYSTEM (Secure extensions for dark web)
-- ============================================================

CREATE TABLE plugins (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    author CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL UNIQUE,
    version VARCHAR(20) NOT NULL,
    description TEXT,
    manifestJSON JSON NOT NULL,
    codeHash VARCHAR(64) NOT NULL,
    permissions JSON NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    verified BOOLEAN DEFAULT FALSE,
    verifiedBy CHAR(36) NULL,
    verificationDate TIMESTAMP NULL,
    downloads INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verifiedBy) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_verified (verified),
    KEY idx_enabled (enabled),
    KEY idx_author (author)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_plugins (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL,
    pluginId CHAR(36) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    config JSON,
    installedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pluginId) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_plugin (userId, pluginId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plugin_versions (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    pluginId CHAR(36) NOT NULL,
    version VARCHAR(20) NOT NULL,
    codeHash VARCHAR(64) NOT NULL,
    manifestJSON JSON NOT NULL,
    changelog TEXT,
    releasedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pluginId) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plugin_version (pluginId, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_user_plugins_enabled ON user_plugins(enabled);


-- ============================================================
-- 5. KEYBOARD SHORTCUTS
-- ============================================================

CREATE TABLE user_shortcuts (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL,
    action VARCHAR(50) NOT NULL,
    `keys` VARCHAR(50) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_action_shortcut (userId, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default shortcuts (inserted per user on first use)
CREATE TABLE default_shortcuts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL UNIQUE,
    `keys` VARCHAR(50) NOT NULL,
    description VARCHAR(100),
    category VARCHAR(30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO default_shortcuts (action, keys, description, category) VALUES
    ('nav_down', 'j', 'Navigate down', 'navigation'),
    ('nav_up', 'k', 'Navigate up', 'navigation'),
    ('nav_next', 'l', 'Next thread', 'navigation'),
    ('nav_prev', 'h', 'Previous thread', 'navigation'),
    ('go_threads', 'g+t', 'Go to threads', 'global'),
    ('go_profile', 'g+p', 'Go to profile', 'global'),
    ('go_notifications', 'g+n', 'Go to notifications', 'global'),
    ('go_messages', 'g+m', 'Go to messages', 'global'),
    ('reply_focused', 'r', 'Reply to focused thread', 'action'),
    ('like_focused', '+', 'Like focused content', 'action'),
    ('save_draft', 'ctrl+s', 'Save draft', 'editing'),
    ('expand_reply', 'e', 'Expand reply box', 'editing'),
    ('close_modal', 'esc', 'Close modal', 'global'),
    ('submit_form', 'ctrl+enter', 'Submit form', 'editing'),
    ('search_focus', '/', 'Focus search', 'global');

CREATE INDEX idx_shortcuts_action ON default_shortcuts(action);


-- ============================================================
-- 6. DRAFT AUTO-SAVE (Client-side primarily, optional server storage)
-- ============================================================

CREATE TABLE drafts (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL,
    draftType ENUM('thread', 'comment', 'message') NOT NULL,
    threadId CHAR(36) NULL,
    content LONGTEXT NOT NULL,
    metadata JSON,
    lastSavedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expiresAt TIMESTAMP NULL,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    KEY idx_user_drafts (userId),
    KEY idx_draft_type (draftType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-delete expired drafts (older than 30 days)
ALTER TABLE drafts ADD COLUMN createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
CREATE INDEX idx_drafts_expires ON drafts(expiresAt);


-- ============================================================
-- 7. PLUGIN HOOKS/EVENTS (For plugin lifecycle)
-- ============================================================

CREATE TABLE plugin_events (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    pluginId CHAR(36) NOT NULL,
    eventType VARCHAR(50) NOT NULL,
    triggerTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data JSON,
    FOREIGN KEY (pluginId) REFERENCES plugins(id) ON DELETE CASCADE,
    KEY idx_event_type (eventType),
    KEY idx_plugin_id (pluginId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. AUDIT LOG (For suspension, hiding, plugin actions)
-- ============================================================

CREATE TABLE audit_log (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    actorId CHAR(36) NOT NULL,
    targetId CHAR(36) NULL,
    action VARCHAR(50) NOT NULL,
    entityType VARCHAR(30) NOT NULL,
    entityId CHAR(36) NULL,
    details JSON,
    ipAddress VARCHAR(45) NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actorId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (targetId) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_action (action),
    KEY idx_created (createdAt),
    KEY idx_actor (actorId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
