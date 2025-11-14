create database forum;
use forum;
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    profilePic VARCHAR(255),  -- File URL path for the profile picture
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(25) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    loginUrl VARCHAR(15) NOT NULL,
    accessLevel TINYINT(2) NOT NULL,  -- Range 1-12
    lastLogin TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'banned', 'restricted') NOT NULL DEFAULT 'active',
    reputation INT DEFAULT 0,
    strikeCount INT DEFAULT 0,
    upgrades ENUM('VIP', 'PRO', 'ELITE') DEFAULT NULL,
    credits DECIMAL(10,2) DEFAULT 0.00,
    timezone VARCHAR(50) DEFAULT 'UTC',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    isDeleted BOOLEAN DEFAULT FALSE
);

CREATE TABLE passwords (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE inviteCodes (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    code VARCHAR(50) NOT NULL UNIQUE,
    generatorId CHAR(36) NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    usedBy CHAR(36) DEFAULT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generatorId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (usedBy) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE passwordResets (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId char(36) NOT NULL,
    resetToken VARCHAR(255) NOT NULL,
    expiry INT NOT NULL,
    isUsed TINYINT(1) Default 0,
    isDeleted TINYINT(1) DEFAULT 0,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE users 
ADD COLUMN totp_secret VARCHAR(32) DEFAULT NULL,
ADD COLUMN totp_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN totp_backup_codes TEXT DEFAULT NULL;

CREATE TABLE loginCounts (
    userId CHAR(36) PRIMARY KEY NOT NULL,
    loginCount INT NOT NULL DEFAULT 0,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);