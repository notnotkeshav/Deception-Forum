-- ============================================
-- MESSAGE REACTIONS & BLOCKING SYSTEM
-- ============================================

USE forum;

-- =============================================
-- REACTIONS TABLE (for threads and comments)
-- =============================================
CREATE TABLE IF NOT EXISTS reactions (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    targetType ENUM('thread', 'comment') NOT NULL,  -- What is being reacted to
    targetId CHAR(36) NOT NULL,                      -- Thread or comment ID
    userId CHAR(36) NOT NULL,                        -- Who reacted
    emoji VARCHAR(4) NOT NULL,                       -- Emoji (👍 😂 ❤️ 🔥 etc.)
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (targetType, targetId, userId, emoji),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_target (targetType, targetId),
    INDEX idx_user (userId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- CHAT MESSAGE REACTIONS
-- =============================================
CREATE TABLE IF NOT EXISTS chat_message_reactions (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    messageType ENUM('private', 'group') NOT NULL,  -- Private or group message
    messageId CHAR(36) NOT NULL,                     -- Message ID
    userId CHAR(36) NOT NULL,                        -- Who reacted
    emoji VARCHAR(4) NOT NULL,                       -- Emoji
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat_reaction (messageType, messageId, userId, emoji),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_message (messageType, messageId),
    INDEX idx_user (userId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- BLOCKED USERS
-- =============================================
CREATE TABLE IF NOT EXISTS blocked_users (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL,                        -- User who is blocking
    blockedUserId CHAR(36) NOT NULL,                 -- User who is blocked
    reason VARCHAR(255) DEFAULT NULL,                -- Reason for blocking
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_block (userId, blockedUserId),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blockedUserId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (userId),
    INDEX idx_blocked (blockedUserId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ADD EDIT TRACKING TO MESSAGES
-- =============================================
ALTER TABLE comments
ADD COLUMN edited_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN edit_count INT DEFAULT 0;

ALTER TABLE privateChatMessages
ADD COLUMN edited_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN edit_count INT DEFAULT 0;

ALTER TABLE groupMessages
ADD COLUMN edited_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN edit_count INT DEFAULT 0;

ALTER TABLE threads
ADD COLUMN edited_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN edit_count INT DEFAULT 0;

-- =============================================
-- MESSAGE EDIT HISTORY
-- =============================================
CREATE TABLE IF NOT EXISTS message_edit_history (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    messageType ENUM('thread', 'comment', 'private_message', 'group_message') NOT NULL,
    messageId CHAR(36) NOT NULL,
    previousContent TEXT NOT NULL,
    editedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    editedBy CHAR(36) NOT NULL,
    INDEX idx_message (messageType, messageId),
    FOREIGN KEY (editedBy) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- EMOJI REACTIONS ALLOWED
-- =============================================
-- Supported emojis: 👍 😂 ❤️ 🔥 😍 😢 😡 👏
-- Feel free to add more!

CREATE VIEW reaction_summary AS
SELECT
    targetType,
    targetId,
    emoji,
    COUNT(*) as count,
    GROUP_CONCAT(userId) as userIds
FROM reactions
GROUP BY targetType, targetId, emoji;

CREATE VIEW chat_reaction_summary AS
SELECT
    messageType,
    messageId,
    emoji,
    COUNT(*) as count,
    GROUP_CONCAT(userId) as userIds
FROM chat_message_reactions
GROUP BY messageType, messageId, emoji;
