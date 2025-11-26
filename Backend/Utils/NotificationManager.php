<?php

namespace Backend\Utils;

use Backend\Core\App;

class NotificationManager
{
    private $db;

    public function __construct()
    {
        $this->db = App::container()->resolve('Core\Database');
    }

    /**
     * Notify about new thread comment
     */
    public function notifyThreadComment(string $threadId, string $commentAuthorId, string $threadAuthorId): void
    {
        if ($commentAuthorId === $threadAuthorId) {
            return;
        }

        $threadData = $this->getThreadDetails($threadId);
        $commentAuthor = $this->getUserDetails($commentAuthorId);

        if (!$threadData || !$commentAuthor) {
            return;
        }

        $title = "New comment on your thread";
        $message = "{$commentAuthor['username']} commented on your thread \"{$threadData['title']}\"";
        $data = [
            'thread_id' => $threadId,
            'comment_author_id' => $commentAuthorId,
            'thread_title' => $threadData['title']
        ];

        createNotification($threadAuthorId, 'thread_comment', $title, $message, $data);
    }

    /**
     * Notify about comment reply
     */
    public function notifyCommentReply(string $parentCommentId, string $replyAuthorId, string $parentCommentAuthorId): void
    {
        if ($replyAuthorId === $parentCommentAuthorId) {
            return;
        }

        $commentData = $this->getCommentDetails($parentCommentId);
        $replyAuthor = $this->getUserDetails($replyAuthorId);

        if (!$commentData || !$replyAuthor) {
            return;
        }

        $title = "New reply to your comment";
        $message = "{$replyAuthor['username']} replied to your comment";
        $data = [
            'parent_comment_id' => $parentCommentId,
            'reply_author_id' => $replyAuthorId,
            'thread_id' => $commentData['thread_id']
        ];

        createNotification($parentCommentAuthorId, 'comment_reply', $title, $message, $data);
    }

    /**
     * Notify user when mentioned in a comment
     */
    public function notifyMention(string $mentionedUserId, string $mentionerUserId, string $threadId, string $commentId = ""): void
    {
        if ($mentionedUserId === $mentionerUserId) {
            return;
        }

        $mentioner = $this->getUserDetails($mentionerUserId);
        $threadData = $this->getThreadDetails($threadId);

        if (!$mentioner || !$threadData) {
            return;
        }

        $title = "You were mentioned";
        $message = "{$mentioner['username']} mentioned you in \"{$threadData['title']}\"";
        $data = [
            'mentioner_id' => $mentionerUserId,
            'thread_id' => $threadId,
            'comment_id' => $commentId
        ];

        createNotification($mentionedUserId, 'mention', $title, $message, $data);
    }

    /**
     * Notify group members about new message
     */
    public function notifyGroupMessage(string $groupId, string $messageId, string $senderId): void
    {
        $group = getGroupDetails($groupId);
        $sender = $this->getUserDetails($senderId);
        $members = getGroupMembers($groupId);

        if (!$group || !$sender) {
            return;
        }

        $title = "New message in {$group['groupName']}";
        $message = "{$sender['username']} sent a message";
        $data = [
            'group_id' => $groupId,
            'message_id' => $messageId,
            'sender_id' => $senderId
        ];

        foreach ($members as $member) {
            if ($member['userId'] !== $senderId) {
                createNotification($member['userId'], 'system', $title, $message, $data);
            }
        }
    }

    /**
     * Notify user when added to group
     */
    public function notifyGroupMemberAdded(string $groupId, string $newMemberId, string $addedById): void
    {
        $group = getGroupDetails($groupId);
        $addedBy = $this->getUserDetails($addedById);

        if (!$group || !$addedBy) {
            return;
        }

        $title = "Added to group chat";
        $message = "{$addedBy['username']} added you to \"{$group['groupName']}\"";
        $data = [
            'group_id' => $groupId,
            'added_by' => $addedById
        ];

        createNotification($newMemberId, 'system', $title, $message, $data);
    }

    /**
     * Send system notification to a user
     */
    public function notifySystem(string $userId, string $title, string $message, array $data = []): void
    {
        createNotification($userId, 'system', $title, $message, $data);
    }

    /**
     * Send system notification to all users
     */
    public function notifyAllUsers(string $title, string $message, array $data = []): void
    {
        try {
            $stmt = $this->db->query(
                "SELECT userId FROM notification_settings WHERE system = 1
                 UNION 
                 SELECT id as userId FROM users WHERE id NOT IN (SELECT userId FROM notification_settings)",
                []
            );
            $users = $this->db->getAll($stmt);
        } catch (\Exception $e) {
            error_log("Failed to get users for system notification: " . $e->getMessage());
            return;
        }

        foreach ($users as $user) {
            createNotification($user['userId'], 'system', $title, $message, $data);
        }
    }

    /**
     * Get thread details
     */
    private function getThreadDetails(string $threadId): ?array
    {
        try {
            $stmt = $this->db->query(
                "SELECT id, title, authorId FROM threads WHERE id = :threadId",
                [':threadId' => $threadId]
            );
            $result = $this->db->getOne($stmt);
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("Failed to get thread details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get comment details
     */
    private function getCommentDetails(string $commentId): ?array
    {
        try {
            $stmt = $this->db->query(
                "SELECT id, authorId, threadId as thread_id FROM comments WHERE id = :commentId",
                [':commentId' => $commentId]
            );
            $result = $this->db->getOne($stmt);
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("Failed to get comment details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user details
     */
    private function getUserDetails(string $userId): ?array
    {
        try {
            $stmt = $this->db->query(
                "SELECT id, username, name FROM users WHERE id = :userId",
                [':userId' => $userId]
            );
            $result = $this->db->getOne($stmt);
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("Failed to get user details: " . $e->getMessage());
            return null;
        }
    }
}
