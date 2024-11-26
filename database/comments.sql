CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    threadId INT NOT NULL,
    userId INT NOT NULL,
    content TEXT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    editedAt TIMESTAMP DEFAULT NULL,
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_thread_user (threadId, userId),
    CONSTRAINT fk_comment_thread FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    categoryId INT DEFAULT NULL,
    threadId INT DEFAULT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscription_user FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscription_category FOREIGN KEY (categoryId) REFERENCES categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscription_thread FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_subscription (userId, categoryId, threadId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
