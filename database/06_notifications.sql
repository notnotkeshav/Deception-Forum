CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL,
    type ENUM('thread_comment', 'comment_reply', 'thread_vote', 'comment_vote', 'new_thread', 'mention', 'system') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON DEFAULT NULL,
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (userId, created_at),
    INDEX idx_user_read (userId, read_at),
    INDEX idx_type (type)
);

CREATE TABLE notification_settings (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL,
    thread_comment BOOLEAN DEFAULT TRUE,
    comment_reply BOOLEAN DEFAULT TRUE,
    thread_vote BOOLEAN DEFAULT TRUE,
    comment_vote BOOLEAN DEFAULT TRUE,
    new_thread BOOLEAN DEFAULT FALSE,
    mention BOOLEAN DEFAULT TRUE,
    system BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_settings (userId),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO notification_settings (userId) 
SELECT id FROM users 
WHERE id NOT IN (SELECT userId FROM notification_settings);
