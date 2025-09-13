CREATE TABLE comments (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    threadId CHAR(36) NOT NULL,
    userId CHAR(36) NOT NULL,
    content TEXT NOT NULL,
    parentCommentId CHAR(36) DEFAULT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    editedAt TIMESTAMP DEFAULT NULL,
    status ENUM('approved', 'flagged') DEFAULT 'approved',
    upvoteCount INT DEFAULT 0,
    downvoteCount INT DEFAULT 0,
    isDeleted BOOLEAN DEFAULT FALSE,
    INDEX idxThreadUser (threadId, userId),
    INDEX idxParentComment (parentCommentId),
    FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parentCommentId) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE commentVotes (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    commentId CHAR(36) NOT NULL,
    userId CHAR(36) NOT NULL,
    voteType ENUM('upvote', 'downvote') NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniqueVote (commentId, userId),
    FOREIGN KEY (commentId) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);