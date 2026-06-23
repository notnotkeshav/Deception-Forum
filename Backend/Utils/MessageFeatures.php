<?php

namespace Backend\Utils;

use Backend\Core\App;

class MessageFeatures
{
    private static $db = null;

    public static function db()
    {
        if (!self::$db) {
            self::$db = App::container()->resolve('Core\Database');
        }
        return self::$db;
    }

    // =============================================
    // REACTION FUNCTIONS
    // =============================================

    /**
     * Get all reactions for a target (thread or comment)
     */
    public static function getReactions($targetType, $targetId)
    {
        $stmt = self::db()->query(
            "SELECT emoji, COUNT(*) as count, GROUP_CONCAT(userId) as userIds
             FROM reactions
             WHERE targetType = :type AND targetId = :id
             GROUP BY emoji
             ORDER BY count DESC",
            [':type' => $targetType, ':id' => $targetId]
        );

        return self::db()->getAll($stmt);
    }

    /**
     * Check if user has reacted with specific emoji
     */
    public static function hasUserReacted($targetType, $targetId, $userId, $emoji)
    {
        $stmt = self::db()->query(
            "SELECT id FROM reactions
             WHERE targetType = :type AND targetId = :id AND userId = :uid AND emoji = :emoji",
            [':type' => $targetType, ':id' => $targetId, ':uid' => $userId, ':emoji' => $emoji]
        );

        return $stmt ? self::db()->getOne($stmt) : false;
    }

    /**
     * Get all chat message reactions
     */
    public static function getChatReactions($messageType, $messageId)
    {
        $stmt = self::db()->query(
            "SELECT emoji, COUNT(*) as count, GROUP_CONCAT(userId) as userIds
             FROM chat_message_reactions
             WHERE messageType = :type AND messageId = :id
             GROUP BY emoji
             ORDER BY count DESC",
            [':type' => $messageType, ':id' => $messageId]
        );

        return self::db()->getAll($stmt);
    }

    // =============================================
    // MESSAGE DELETE FUNCTIONS
    // =============================================

    /**
     * Soft delete a comment
     */
    public static function deleteComment($commentId, $userId)
    {
        // Verify ownership
        $stmt = self::db()->query(
            "SELECT userId FROM comments WHERE id = :id AND isDeleted = 0",
            [':id' => $commentId]
        );
        $comment = self::db()->getOne($stmt);

        if (!$comment) {
            return ['success' => false, 'message' => 'Comment not found'];
        }

        if ($comment['userId'] !== $userId && !($_SESSION['moderator'] ?? false)) {
            return ['success' => false, 'message' => 'Permission denied'];
        }

        self::db()->query(
            "UPDATE comments SET isDeleted = 1 WHERE id = :id",
            [':id' => $commentId]
        );

        return ['success' => true, 'message' => 'Comment deleted'];
    }

    /**
     * Soft delete a chat message
     */
    public static function deleteMessage($messageId, $messageType, $userId)
    {
        $table = $messageType === 'private' ? 'privateChatMessages' : 'groupMessages';

        $stmt = self::db()->query(
            "SELECT userId FROM {$table} WHERE id = :id AND isDeleted = 0",
            [':id' => $messageId]
        );
        $message = self::db()->getOne($stmt);

        if (!$message) {
            return ['success' => false, 'message' => 'Message not found'];
        }

        if ($message['userId'] !== $userId && !($_SESSION['moderator'] ?? false)) {
            return ['success' => false, 'message' => 'Permission denied'];
        }

        self::db()->query(
            "UPDATE {$table} SET isDeleted = 1 WHERE id = :id",
            [':id' => $messageId]
        );

        return ['success' => true, 'message' => 'Message deleted'];
    }

    // =============================================
    // MESSAGE EDIT FUNCTIONS
    // =============================================

    /**
     * Get edit history for a message
     */
    public static function getEditHistory($messageId, $messageType)
    {
        $stmt = self::db()->query(
            "SELECT previousContent, editedAt, editedBy
             FROM message_edit_history
             WHERE messageId = :id AND messageType = :type
             ORDER BY editedAt DESC",
            [':id' => $messageId, ':type' => $messageType]
        );

        return self::db()->getAll($stmt);
    }

    /**
     * Save to edit history before updating
     */
    public static function saveEditHistory($messageId, $messageType, $previousContent, $userId)
    {
        self::db()->query(
            "INSERT INTO message_edit_history (messageId, messageType, previousContent, editedBy)
             VALUES (:id, :type, :content, :user)",
            [':id' => $messageId, ':type' => $messageType, ':content' => $previousContent, ':user' => $userId]
        );
    }

    // =============================================
    // BLOCKING FUNCTIONS
    // =============================================

    /**
     * Block a user
     */
    public static function blockUser($userId, $blockedUserId, $reason = null)
    {
        if ($userId === $blockedUserId) {
            return ['success' => false, 'message' => 'Cannot block yourself'];
        }

        // Check if already blocked
        $stmt = self::db()->query(
            "SELECT id FROM blocked_users WHERE userId = :uid AND blockedUserId = :bid",
            [':uid' => $userId, ':bid' => $blockedUserId]
        );

        if (self::db()->getOne($stmt)) {
            return ['success' => false, 'message' => 'User already blocked'];
        }

        // Block user
        self::db()->query(
            "INSERT INTO blocked_users (userId, blockedUserId, reason)
             VALUES (:uid, :bid, :reason)",
            [':uid' => $userId, ':bid' => $blockedUserId, ':reason' => $reason]
        );

        // Hide messages from blocked user
        self::db()->query(
            "UPDATE privateChatMessages SET isDeleted = 1
             WHERE chatId IN (
                SELECT id FROM privateChats
                WHERE (user1Id = :uid AND user2Id = :bid) OR (user1Id = :bid AND user2Id = :uid)
             )",
            [':uid' => $userId, ':bid' => $blockedUserId]
        );

        return ['success' => true, 'message' => 'User blocked successfully'];
    }

    /**
     * Unblock a user
     */
    public static function unblockUser($userId, $blockedUserId)
    {
        $stmt = self::db()->query(
            "SELECT id FROM blocked_users WHERE userId = :uid AND blockedUserId = :bid",
            [':uid' => $userId, ':bid' => $blockedUserId]
        );

        if (!self::db()->getOne($stmt)) {
            return ['success' => false, 'message' => 'User not blocked'];
        }

        self::db()->query(
            "DELETE FROM blocked_users WHERE userId = :uid AND blockedUserId = :bid",
            [':uid' => $userId, ':bid' => $blockedUserId]
        );

        return ['success' => true, 'message' => 'User unblocked'];
    }

    /**
     * Get list of blocked users
     */
    public static function getBlockedUsers($userId)
    {
        $stmt = self::db()->query(
            "SELECT u.id, u.username, u.name, u.profilePic, bu.reason, bu.createdAt
             FROM blocked_users bu
             JOIN users u ON bu.blockedUserId = u.id
             WHERE bu.userId = :uid
             ORDER BY bu.createdAt DESC",
            [':uid' => $userId]
        );

        return self::db()->getAll($stmt);
    }

    /**
     * Check if user is blocked
     */
    public static function isUserBlocked($userId, $checkUserId)
    {
        $stmt = self::db()->query(
            "SELECT id FROM blocked_users WHERE userId = :uid AND blockedUserId = :bid",
            [':uid' => $userId, ':bid' => $checkUserId]
        );

        return self::db()->getOne($stmt) ? true : false;
    }

    /**
     * Filter out blocked users from query results
     */
    public static function filterBlockedUsers($users, $currentUserId)
    {
        $blockedIds = self::getBlockedUserIds($currentUserId);

        return array_filter($users, function ($user) use ($blockedIds) {
            return !in_array($user['id'], $blockedIds);
        });
    }

    /**
     * Get array of blocked user IDs
     */
    public static function getBlockedUserIds($userId)
    {
        $stmt = self::db()->query(
            "SELECT blockedUserId FROM blocked_users WHERE userId = :uid",
            [':uid' => $userId]
        );

        $results = self::db()->getAll($stmt);
        return array_column($results, 'blockedUserId');
    }
}
