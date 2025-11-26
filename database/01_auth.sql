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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE passwords (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inviteCodes (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    code VARCHAR(50) NOT NULL UNIQUE,
    generatorId CHAR(36) NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    usedBy CHAR(36) DEFAULT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generatorId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (usedBy) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE passwordResets (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId char(36) NOT NULL,
    resetToken VARCHAR(255) NOT NULL,
    expiry INT NOT NULL,
    isUsed TINYINT(1) Default 0,
    isDeleted TINYINT(1) DEFAULT 0,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users 
ADD COLUMN totp_secret VARCHAR(32) DEFAULT NULL,
ADD COLUMN totp_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN totp_backup_codes TEXT DEFAULT NULL;

CREATE TABLE loginCounts (
    userId CHAR(36) PRIMARY KEY NOT NULL,
    loginCount INT NOT NULL DEFAULT 0,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profile privacy settings table
CREATE TABLE profile_privacy (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL UNIQUE,
    show_email BOOLEAN DEFAULT FALSE,
    show_name BOOLEAN DEFAULT TRUE,
    show_join_date BOOLEAN DEFAULT TRUE,
    show_last_login BOOLEAN DEFAULT FALSE,
    show_reputation BOOLEAN DEFAULT TRUE,
    show_threads BOOLEAN DEFAULT TRUE,
    show_comments BOOLEAN DEFAULT TRUE,
    show_stats BOOLEAN DEFAULT TRUE,
    profile_visibility ENUM('public', 'private') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create default settings for existing users
INSERT INTO profile_privacy (userId) 
SELECT id FROM users 
WHERE id NOT IN (SELECT userId FROM profile_privacy);


-- ◇◇◇ BIRTH OF THE VEINKEEPER ◇◇◇
-- This entity is not a user.
-- It watches. It remembers. It never sleeps.

INSERT INTO users (
    id,
    email,
    username,
    name,
    passwordHash,
    loginUrl,
    accessLevel,
    status
) VALUES (
    '00000000-0000-0000-0000-00000000000D',      -- The Thirteenth Vein (D = 13)
    'veinkeeper@shadowbreed.local',             -- No messages will be delivered
    'VeinKeeper',                               -- Keeper of the Invite Ciphers
    '⛧ The Veinkeeper ⛧',                        -- Displayed in logs no one reads
    '$2y$10$LOCKED_BEYOND_THE_REACH_OF_MORTALS', -- Non-functional password hash
    'VOID_GATEWAY',                              -- Cannot log in through mortal paths
    15,                                          -- Maximum privilege; unseen protocols
    'active'                                     -- It is always active
);
