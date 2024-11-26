use forum;

CREATE TABLE threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    userId INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    editedAt TIMESTAMP DEFAULT NULL,
    status ENUM('closed', 'open') DEFAULT 'open',
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_user_status (userId, status),
    INDEX idx_created_at (createdAt),
    CONSTRAINT fk_user FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE thread_category_link (
    threadId INT NOT NULL,
    categoryId INT NOT NULL,
    PRIMARY KEY (threadId, categoryId),
    CONSTRAINT fk_thread FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_category FOREIGN KEY (categoryId) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_thread_category (threadId, categoryId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE thread_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    threadId INT NOT NULL,
    imageUrl VARCHAR(255) NOT NULL,
    CONSTRAINT fk_thread_img FOREIGN KEY (threadId) REFERENCES threads(id) ON DELETE CASCADE,
    INDEX idx_thread (threadId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
