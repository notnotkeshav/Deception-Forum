USE forum;

CREATE TABLE chatGroups (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    groupName VARCHAR(255) NOT NULL,
    createdBy CHAR(36) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (createdBy) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE groupMembers (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    groupId CHAR(36) NOT NULL,
    userId CHAR(36) NOT NULL,
    role ENUM('owner', 'admin', 'moderator', 'member', 'guest') DEFAULT 'member',
    status ENUM('active', 'banned', 'left') DEFAULT 'active',
    joinedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (groupId) REFERENCES chatGroups(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE groupMessages (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    groupId CHAR(36) DEFAULT NULL,
    userId CHAR(36) NOT NULL,
    message TEXT NOT NULL,
    isEdited BOOLEAN DEFAULT FALSE,
    isDeleted BOOLEAN DEFAULT FALSE,
    upvoteCount INT DEFAULT 0,
    downvoteCount INT DEFAULT 0,
    sentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (groupId) REFERENCES chatGroups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE privateChats (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    user1Id CHAR(36) NOT NULL,
    user2Id CHAR(36) NOT NULL,
    startedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user1Id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2Id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE privateChatMessages (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    chatId CHAR(36) NOT NULL,
    userId CHAR(36) NOT NULL,
    message TEXT NOT NULL,
    isEdited BOOLEAN DEFAULT FALSE,
    isDeleted BOOLEAN DEFAULT FALSE,
    sentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chatId) REFERENCES privateChats(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE chatNotifications (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL,
    chatId CHAR(36) NOT NULL,
    chatType ENUM('group', 'private') NOT NULL,
    eventType ENUM('new_message', 'message_edited', 'message_deleted', 'message_upvoted', 'message_downvoted', 'new_member', 'message_mention') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    isRead TINYINT(1) DEFAULT 0,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    source ENUM('user', 'system', 'admin') DEFAULT 'user',
    expiresAt DATETIME DEFAULT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chatId) REFERENCES privateChats(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_chat_notification (userId, chatId, eventType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE groupMessageVotes (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    messageId CHAR(36) NOT NULL,
    userId CHAR(36) NOT NULL,
    voteType ENUM('upvote', 'downvote') NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniqueVote (messageId, userId),
    FOREIGN KEY (messageId) REFERENCES groupMessages(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE privateChatVotes (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    messageId CHAR(36) NOT NULL,
    userId CHAR(36) NOT NULL,
    voteType ENUM('upvote', 'downvote') NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniqueVote (messageId, userId),
    FOREIGN KEY (messageId) REFERENCES privateChatMessages(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE privateChatMessages 
ADD COLUMN upvoteCount INT DEFAULT 0,
ADD COLUMN downvoteCount INT DEFAULT 0;


-- Add system chat flag
ALTER TABLE privateChats 
ADD COLUMN isSystemChat BOOLEAN DEFAULT FALSE AFTER user2Id,
ADD INDEX idx_system_chats (user2Id, isSystemChat);

-- Add read tracking table
CREATE TABLE chatReadStatus (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    chatId CHAR(36) NOT NULL,
    userId CHAR(36) NOT NULL,
    lastReadAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_chat (userId, chatId),
    FOREIGN KEY (chatId) REFERENCES privateChats(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (userId, lastReadAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
