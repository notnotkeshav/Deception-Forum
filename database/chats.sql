USE forum;
CREATE TABLE groups (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    groupName VARCHAR(255) NOT NULL,
    createdBy INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (createdBy) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE groupMembers (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    groupId INT NOT NULL,
    userId INT NOT NULL,
    role ENUM('owner', 'admin', 'moderator', 'member', 'guest') DEFAULT 'member',
    status ENUM('active', 'banned', 'left') DEFAULT 'active',
    joinedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (groupId) REFERENCES groups(id),
    FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE messages (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    groupId INT DEFAULT NULL,
    userId INT NOT NULL,
    message TEXT NOT NULL,
    sentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id),
    FOREIGN KEY (groupId) REFERENCES groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE privateChats (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    user1Id INT NOT NULL,
    user2Id INT NOT NULL,
    startedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user1Id) REFERENCES users(id),
    FOREIGN KEY (user2Id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE privateChatMessages (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    chatId INT NOT NULL,
    userId INT NOT NULL,
    message TEXT NOT NULL,
    sentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chatId) REFERENCES privateChats(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE chat_notifications (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId INT NOT NULL,
    chatId INT NOT NULL,
    chatType ENUM('group', 'private') NOT NULL,
    eventType ENUM('new_message', 'message_edited', 'message_deleted', 'message_upvoted', 'message_downvoted', 'new_member', 'message_mention') NOT NULL,  -- Types of events
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    read TINYINT(1) DEFAULT 0,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    source ENUM('user', 'system', 'admin') DEFAULT 'user',
    expiresAt TIMESTAMP DEFAULT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chatId) REFERENCES chats(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_chat_notification (userId, chatId, eventType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE groupChatVotes (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    messageId INT NOT NULL,
    userId INT NOT NULL,
    voteType ENUM('upvote', 'downvote') NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniqueVote (messageId, userId),
    FOREIGN KEY (messageId) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE privateChatVotes (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    messageId INT NOT NULL,
    userId INT NOT NULL,
    voteType ENUM('upvote', 'downvote') NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniqueVote (messageId, userId),
    FOREIGN KEY (messageId) REFERENCES privateChatMessages(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
