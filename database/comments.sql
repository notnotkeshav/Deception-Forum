CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    threadId INT NOT NULL,
    userId INT NOT NULL,
    content TEXT NOT NULL,
    parentCommentId INT DEFAULT NULL,  -- NULL for top-level comments, references `id` for replies
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    editedAt TIMESTAMP DEFAULT NULL,
    status ENUM('approved', 'flagged') DEFAULT 'approved',
    upvoteCount INT DEFAULT 0,  -- Upvote count
    downvoteCount INT DEFAULT 0,  -- Downvote count
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_thread_user (threadId, userId),
    INDEX idx_parent_comment (parentCommentId),
    CONSTRAINT fk_comment_thread FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_parent FOREIGN KEY (parentCommentId) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE comment_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('upvote', 'downvote') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (comment_id, user_id), -- Ensures one vote per user per comment
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE, -- Maintain referential integrity
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE -- (Assumes a users table exists)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    categoryId INT DEFAULT NULL,
    threadId INT DEFAULT NULL,
    eventType ENUM('new_thread', 'new_comment', 'thread_update', 'reply_to_comment') NOT NULL,  -- Event type for flexibility
    read TINYINT(1) DEFAULT 0,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscription_user FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscription_category FOREIGN KEY (categoryId) REFERENCES categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscription_thread FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_subscription (userId, categoryId, threadId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
