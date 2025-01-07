CREATE TABLE moderators (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    userId CHAR(36) NOT NULL UNIQUE, -- Links to the users table
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'moderator',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    joinedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE privileges (
    id CHAR(36) PRIMARY KEY NOT NULL DEFAULT (UUID()),
    moderatorId CHAR(36) NOT NULL UNIQUE, -- Each moderator gets a unique set of privileges
    canBanUsers TINYINT(1) DEFAULT 0, -- 1 = true, 0 = false
    canDeletePosts TINYINT(1) DEFAULT 0,
    canEditPosts TINYINT(1) DEFAULT 0,
    canViewReports TINYINT(1) DEFAULT 0,
    canManageUsers TINYINT(1) DEFAULT 0,
    canCreateGroups TINYINT(1) DEFAULT 0,
    canAssignRoles TINYINT(1) DEFAULT 0,
    canPinPosts TINYINT(1) DEFAULT 0,
    canViewLogs TINYINT(1) DEFAULT 0,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (moderatorId) REFERENCES moderators(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
