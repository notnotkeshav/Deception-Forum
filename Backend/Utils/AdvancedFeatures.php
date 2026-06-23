<?php

namespace Backend\Utils;

use Backend\Core\App;

class AdvancedFeatures
{
    private static $db = null;

    private static function db() {
        if (!self::$db) {
            self::$db = App::resolve('Core\Database');
        }
        return self::$db;
    }

    // ========================================
    // USER SUSPENSION
    // ========================================

    public static function suspendUser($userId, $reason = '') {
        $db = self::db();
        $db->query(
            "UPDATE users SET isSuspended = 1, suspendedAt = NOW(), suspendReason = :reason
             WHERE id = :userId",
            [':userId' => $userId, ':reason' => $reason]
        );
        self::logAudit(authUser()['id'], $userId, 'suspend_user', 'user', $userId, ['reason' => $reason]);
    }

    public static function unsuspendUser($userId) {
        $db = self::db();
        $db->query(
            "UPDATE users SET isSuspended = 0, suspendedAt = NULL, suspendReason = NULL
             WHERE id = :userId",
            [':userId' => $userId]
        );
        self::logAudit(authUser()['id'], $userId, 'unsuspend_user', 'user', $userId);
    }

    public static function isSuspended($userId) {
        $db = self::db();
        $result = $db->query(
            "SELECT isSuspended FROM users WHERE id = :id",
            [':id' => $userId]
        );
        return $result[0]['isSuspended'] ?? false;
    }


    // ========================================
    // POST HIDING (MODERATION)
    // ========================================

    public static function hideThread($threadId, $reason = '') {
        $moderatorId = authUser()['id'];
        $db = self::db();
        $db->query(
            "UPDATE threads SET isHidden = 1, hiddenBy = :moderatorId, hiddenReason = :reason, hiddenAt = NOW()
             WHERE id = :threadId",
            [
                ':threadId' => $threadId,
                ':moderatorId' => $moderatorId,
                ':reason' => $reason
            ]
        );
        self::logAudit($moderatorId, null, 'hide_thread', 'thread', $threadId, ['reason' => $reason]);
    }

    public static function unhideThread($threadId) {
        $moderatorId = authUser()['id'];
        $db = self::db();
        $db->query(
            "UPDATE threads SET isHidden = 0, hiddenBy = NULL, hiddenReason = NULL, hiddenAt = NULL
             WHERE id = :threadId",
            [':threadId' => $threadId]
        );
        self::logAudit($moderatorId, null, 'unhide_thread', 'thread', $threadId);
    }

    public static function hideComment($commentId, $reason = '') {
        $moderatorId = authUser()['id'];
        $db = self::db();
        $db->query(
            "UPDATE comments SET isHidden = 1, hiddenBy = :moderatorId, hiddenReason = :reason, hiddenAt = NOW()
             WHERE id = :commentId",
            [
                ':commentId' => $commentId,
                ':moderatorId' => $moderatorId,
                ':reason' => $reason
            ]
        );
        self::logAudit($moderatorId, null, 'hide_comment', 'comment', $commentId, ['reason' => $reason]);
    }

    public static function unhideComment($commentId) {
        $moderatorId = authUser()['id'];
        $db = self::db();
        $db->query(
            "UPDATE comments SET isHidden = 0, hiddenBy = NULL, hiddenReason = NULL, hiddenAt = NULL
             WHERE id = :commentId",
            [':commentId' => $commentId]
        );
        self::logAudit($moderatorId, null, 'unhide_comment', 'comment', $commentId);
    }


    // ========================================
    // THEME SYSTEM (Level 4+)
    // ========================================

    public static function createTheme($userId, $name, $colors, $cssVars, $isPublic = false, $description = '') {
        $db = self::db();
        $themeId = \bin2hex(\random_bytes(18));

        $db->query(
            "INSERT INTO themes (id, userId, name, description, isPublic, colors, cssVars)
             VALUES (:id, :userId, :name, :description, :isPublic, :colors, :cssVars)",
            [
                ':id' => $themeId,
                ':userId' => $userId,
                ':name' => $name,
                ':description' => $description,
                ':isPublic' => $isPublic ? 1 : 0,
                ':colors' => \json_encode($colors),
                ':cssVars' => \json_encode($cssVars)
            ]
        );

        return $themeId;
    }

    public static function getTheme($themeId) {
        $db = self::db();
        $result = $db->query("SELECT * FROM themes WHERE id = :id", [':id' => $themeId]);
        if (!empty($result)) {
            $result[0]['colors'] = \json_decode($result[0]['colors'], true);
            $result[0]['cssVars'] = \json_decode($result[0]['cssVars'], true);
            return $result[0];
        }
        return null;
    }

    public static function getUserThemes($userId) {
        $db = self::db();
        $themes = $db->query("SELECT * FROM themes WHERE userId = :userId", [':userId' => $userId]);
        foreach ($themes as &$theme) {
            $theme['colors'] = \json_decode($theme['colors'], true);
            $theme['cssVars'] = \json_decode($theme['cssVars'], true);
        }
        return $themes;
    }

    public static function setActiveTheme($userId, $themeId) {
        $db = self::db();
        $db->query(
            "UPDATE user_theme_preferences SET activeThemeId = :themeId WHERE userId = :userId",
            [':userId' => $userId, ':themeId' => $themeId]
        );
    }

    public static function getActiveTheme($userId) {
        $db = self::db();
        $result = $db->query(
            "SELECT activeThemeId FROM user_theme_preferences WHERE userId = :userId",
            [':userId' => $userId]
        );

        if (!empty($result) && $result[0]['activeThemeId']) {
            return self::getTheme($result[0]['activeThemeId']);
        }
        return null;
    }

    public static function setCustomCSS($userId, $customCSS, $enable = true) {
        $db = self::db();
        $db->query(
            "UPDATE user_theme_preferences SET customCSS = :css, enableCustomCSS = :enable WHERE userId = :userId",
            [':userId' => $userId, ':css' => $customCSS, ':enable' => $enable ? 1 : 0]
        );
    }

    public static function getCustomCSS($userId) {
        $db = self::db();
        $result = $db->query(
            "SELECT customCSS, enableCustomCSS FROM user_theme_preferences WHERE userId = :userId",
            [':userId' => $userId]
        );
        return !empty($result) ? $result[0] : null;
    }

    public static function deleteTheme($themeId) {
        $db = self::db();
        $db->query("DELETE FROM themes WHERE id = :id", [':id' => $themeId]);
    }


    // ========================================
    // PLUGIN SYSTEM (Secure)
    // ========================================

    public static function installPlugin($authorId, $name, $version, $manifest, $code) {
        $db = self::db();
        $pluginId = \bin2hex(\random_bytes(18));
        $codeHash = \hash('sha256', $code);

        // Validate manifest and extract permissions
        $manifestArray = \is_string($manifest) ? \json_decode($manifest, true) : $manifest;
        $permissions = $manifestArray['permissions'] ?? [];

        $db->query(
            "INSERT INTO plugins (id, author, name, version, description, manifestJSON, codeHash, permissions)
             VALUES (:id, :author, :name, :version, :description, :manifest, :hash, :permissions)",
            [
                ':id' => $pluginId,
                ':author' => $authorId,
                ':name' => $name,
                ':version' => $version,
                ':description' => $manifestArray['description'] ?? '',
                ':manifest' => \is_string($manifest) ? $manifest : \json_encode($manifest),
                ':hash' => $codeHash,
                ':permissions' => \json_encode($permissions)
            ]
        );

        self::logAudit($authorId, null, 'install_plugin', 'plugin', $pluginId, ['name' => $name, 'version' => $version]);
        return $pluginId;
    }

    public static function enablePluginForUser($userId, $pluginId, $config = []) {
        $db = self::db();

        try {
            $db->query(
                "INSERT INTO user_plugins (id, userId, pluginId, enabled, config)
                 VALUES (:id, :userId, :pluginId, 1, :config)
                 ON DUPLICATE KEY UPDATE enabled = 1, config = :config",
                [
                    ':id' => \bin2hex(\random_bytes(18)),
                    ':userId' => $userId,
                    ':pluginId' => $pluginId,
                    ':config' => \json_encode($config)
                ]
            );
        } catch (\Exception $e) {
            // Already exists, update only
            $db->query(
                "UPDATE user_plugins SET enabled = 1, config = :config WHERE userId = :userId AND pluginId = :pluginId",
                [':userId' => $userId, ':pluginId' => $pluginId, ':config' => \json_encode($config)]
            );
        }
    }

    public static function disablePluginForUser($userId, $pluginId) {
        $db = self::db();
        $db->query(
            "UPDATE user_plugins SET enabled = 0 WHERE userId = :userId AND pluginId = :pluginId",
            [':userId' => $userId, ':pluginId' => $pluginId]
        );
    }

    public static function getUserPlugins($userId) {
        $db = self::db();
        $plugins = $db->query(
            "SELECT p.*, up.enabled, up.config FROM plugins p
             LEFT JOIN user_plugins up ON p.id = up.pluginId
             WHERE up.userId = :userId AND up.enabled = 1",
            [':userId' => $userId]
        );

        foreach ($plugins as &$plugin) {
            $plugin['manifestJSON'] = \json_decode($plugin['manifestJSON'], true);
            $plugin['permissions'] = \json_decode($plugin['permissions'], true);
            $plugin['config'] = \json_decode($plugin['config'], true);
        }
        return $plugins;
    }

    public static function getPublicPlugins($limit = 20, $offset = 0) {
        $db = self::db();
        return $db->query(
            "SELECT id, name, version, description, author, rating, downloads, verified
             FROM plugins WHERE verified = 1 AND enabled = 1
             ORDER BY downloads DESC, rating DESC
             LIMIT :limit OFFSET :offset",
            [':limit' => $limit, ':offset' => $offset]
        );
    }

    public static function validatePluginCode($code, $hash) {
        return \hash('sha256', $code) === $hash;
    }


    // ========================================
    // KEYBOARD SHORTCUTS
    // ========================================

    public static function getDefaultShortcuts() {
        $db = self::db();
        return $db->query("SELECT * FROM default_shortcuts ORDER BY category, action");
    }

    public static function getUserShortcuts($userId) {
        $db = self::db();
        return $db->query(
            "SELECT action, keys, enabled FROM user_shortcuts WHERE userId = :userId",
            [':userId' => $userId]
        );
    }

    public static function setShortcut($userId, $action, $keys) {
        $db = self::db();

        try {
            $db->query(
                "INSERT INTO user_shortcuts (id, userId, action, keys, enabled)
                 VALUES (:id, :userId, :action, :keys, 1)",
                [
                    ':id' => \bin2hex(\random_bytes(18)),
                    ':userId' => $userId,
                    ':action' => $action,
                    ':keys' => $keys
                ]
            );
        } catch (\Exception $e) {
            $db->query(
                "UPDATE user_shortcuts SET keys = :keys WHERE userId = :userId AND action = :action",
                [':userId' => $userId, ':action' => $action, ':keys' => $keys]
            );
        }
    }

    public static function getShortcut($userId, $action) {
        $db = self::db();
        $result = $db->query(
            "SELECT keys FROM user_shortcuts WHERE userId = :userId AND action = :action",
            [':userId' => $userId, ':action' => $action]
        );
        return !empty($result) ? $result[0]['keys'] : null;
    }

    public static function resetShortcutsToDefault($userId) {
        $db = self::db();
        $db->query("DELETE FROM user_shortcuts WHERE userId = :userId", [':userId' => $userId]);
    }


    // ========================================
    // DRAFTS (Client-side primarily)
    // ========================================

    public static function saveDraft($userId, $draftType, $content, $threadId = null, $metadata = []) {
        $db = self::db();
        $draftId = \bin2hex(\random_bytes(18));

        $db->query(
            "INSERT INTO drafts (id, userId, draftType, threadId, content, metadata, expiresAt)
             VALUES (:id, :userId, :draftType, :threadId, :content, :metadata, DATE_ADD(NOW(), INTERVAL 30 DAY))
             ON DUPLICATE KEY UPDATE content = :content, metadata = :metadata, lastSavedAt = NOW()",
            [
                ':id' => $draftId,
                ':userId' => $userId,
                ':draftType' => $draftType,
                ':threadId' => $threadId,
                ':content' => $content,
                ':metadata' => \json_encode($metadata)
            ]
        );

        return $draftId;
    }

    public static function getDraft($userId, $draftType, $threadId = null) {
        $db = self::db();

        if ($threadId) {
            $result = $db->query(
                "SELECT * FROM drafts WHERE userId = :userId AND draftType = :draftType AND threadId = :threadId
                 ORDER BY lastSavedAt DESC LIMIT 1",
                [':userId' => $userId, ':draftType' => $draftType, ':threadId' => $threadId]
            );
        } else {
            $result = $db->query(
                "SELECT * FROM drafts WHERE userId = :userId AND draftType = :draftType
                 ORDER BY lastSavedAt DESC LIMIT 1",
                [':userId' => $userId, ':draftType' => $draftType]
            );
        }

        if (!empty($result)) {
            $result[0]['metadata'] = \json_decode($result[0]['metadata'], true);
            return $result[0];
        }
        return null;
    }

    public static function deleteDraft($draftId) {
        $db = self::db();
        $db->query("DELETE FROM drafts WHERE id = :id", [':id' => $draftId]);
    }

    public static function getUserDrafts($userId) {
        $db = self::db();
        $drafts = $db->query(
            "SELECT id, draftType, threadId, content, lastSavedAt FROM drafts
             WHERE userId = :userId AND expiresAt > NOW()
             ORDER BY lastSavedAt DESC",
            [':userId' => $userId]
        );
        return $drafts;
    }


    // ========================================
    // AUDIT LOGGING
    // ========================================

    public static function logAudit($actorId, $targetId, $action, $entityType, $entityId, $details = []) {
        $db = self::db();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        $db->query(
            "INSERT INTO audit_log (id, actorId, targetId, action, entityType, entityId, details, ipAddress)
             VALUES (:id, :actorId, :targetId, :action, :entityType, :entityId, :details, :ipAddress)",
            [
                ':id' => \bin2hex(\random_bytes(18)),
                ':actorId' => $actorId,
                ':targetId' => $targetId,
                ':action' => $action,
                ':entityType' => $entityType,
                ':entityId' => $entityId,
                ':details' => \json_encode($details),
                ':ipAddress' => $ipAddress
            ]
        );
    }

    public static function getAuditLog($limit = 50, $offset = 0, $filters = []) {
        $db = self::db();
        $query = "SELECT * FROM audit_log WHERE 1=1";
        $params = [];

        if (!empty($filters['action'])) {
            $query .= " AND action = :action";
            $params[':action'] = $filters['action'];
        }

        if (!empty($filters['actorId'])) {
            $query .= " AND actorId = :actorId";
            $params[':actorId'] = $filters['actorId'];
        }

        $query .= " ORDER BY createdAt DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        return $db->query($query, $params);
    }
}
