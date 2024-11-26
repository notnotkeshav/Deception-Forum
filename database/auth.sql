CREATE TABLE USERS (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    profilePic VARCHAR(255),  -- File URL path for the profile picture
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(25) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    loginURL VARCHAR(15) NOT NULL,
    accessLevel TINYINT(2) NOT NULL,  -- Range 1-12
    lastLogin TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'banned', 'restricted') NOT NULL DEFAULT 'active',
    reputation INT DEFAULT 0,  -- Changed from ENUM to INT for reputation score
    strikeCount INT DEFAULT 0,
    upgrades ENUM('VIP', 'PRO', 'ELITE') DEFAULT NULL,
    credits DECIMAL(10,2) DEFAULT 0.00,
    timezone VARCHAR(50) DEFAULT 'UTC',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    isDeleted TINYINT(2) DEFAULT 0  -- Renamed from deleted to isDeleted
);


CREATE TABLE PASSWORDS (
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
    generatorId INT NOT NULL,
    used TINYINT(1) DEFAULT 0,  -- 0 for not used, 1 for used
    usedBy INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generatorId) REFERENCES USER(ID) ON DELETE CASCADE,
    FOREIGN KEY (usedBy) REFERENCES USER(ID) ON DELETE CASCADE,
);
