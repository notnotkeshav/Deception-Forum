CREATE TABLE USER (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    profilePic VARCHAR(255),  -- Stores fileuploadId, you can adjust size as needed
    email varchar(255) NOT NULL UNIQUE,
    username VARCHAR(25) NOT NULL UNIQUE,
    name varchar(255) NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    loginURL VARCHAR(15) NOT NULL,
    accessLevel TINYINT(2) NOT NULL,  -- Range 1-12
    lastLogin TIMESTAMP NULL DEFAULT NULL,
    status ENUM('active', 'banned', 'restricted') NOT NULL DEFAULT 'active',
    reputationPoint ENUM('newbie', 'expert', 'intermediate') NOT NULL DEFAULT 'newbie',
    strikeCount INT DEFAULT 0,
    upgrades ENUM('VIP', 'PRO', 'ELITE') DEFAULT NULL,
    credits DECIMAL(10,2) DEFAULT 0.00,
    timezone VARCHAR(50) DEFAULT 'UTC',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
);

CREATE TABLE PASSWORD (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    password varchar(255) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES USER(ID) ON DELETE CASCADE
);

CREATE TABLE InviteCodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    userId INT NOT NULL,
    used TINYINT(1) DEFAULT 0,  -- 0 for not used, 1 for used
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES USER(ID) ON DELETE CASCADE
);
