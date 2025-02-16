CREATE TABLE threads (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    userId CHAR(36) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    editedAt TIMESTAMP DEFAULT NULL,
    status ENUM('closed', 'open', 'archived', 'pinned') DEFAULT 'open',
    isDeleted BOOLEAN DEFAULT FALSE,
    viewsCount INT DEFAULT 0,
    upvoteCount INT DEFAULT 0,
    downvoteCount INT DEFAULT 0,
    locked BOOLEAN DEFAULT FALSE,
    lockedBy CHAR(36) DEFAULT NULL,
    INDEX idxUserStatus (userId, status),
    INDEX idxCreatedAt (createdAt),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lockedBy) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE threadCategoryLink (
    threadId CHAR(36) NOT NULL,
    categoryId CHAR(36) NOT NULL,
    PRIMARY KEY (threadId, categoryId),
    FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (categoryId) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idxThreadCategory (threadId, categoryId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE threadImages (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    threadId CHAR(36) NOT NULL,
    imageUrl VARCHAR(255) NOT NULL,
    FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    INDEX idxThread (threadId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE threadVotes (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    threadId CHAR(36),
    userId CHAR(36),
    voteType ENUM('upvote', 'downvote'),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniqueVote (threadId, userId),
    FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);