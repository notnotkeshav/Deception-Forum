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
            return; // Don't notify if commenting on own thread
        }
        
        if (!shouldReceiveNotification($threadAuthorId, 'thread_comment')) {
            return;
        }
        
        // Get thread and author details
        $threadData = $this->getThreadDetails($threadId);
        $commentAuthor = $this->getUserDetails($commentAuthorId);
        
        if (!$threadData || !$commentAuthor) {
            return;
        }
        
        $title = "New comment on your thread";
        $message = "{$commentAuthor['username']} commented on your thread \"{$threadData['title']}\"";
        $data = [
            'type' => 'thread_comment',
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
            return; // Don't notify if replying to own comment
        }
        
        if (!shouldReceiveNotification($parentCommentAuthorId, 'comment_reply')) {
            return;
        }
        
        // Get comment and thread details
        $commentData = $this->getCommentDetails($parentCommentId);
        $replyAuthor = $this->getUserDetails($replyAuthorId);
        
        if (!$commentData || !$replyAuthor) {
            return;
        }
        
        $title = "New reply to your comment";
        $message = "{$replyAuthor['username']} replied to your comment";
        $data = [
            'type' => 'comment_reply',
            'parent_comment_id' => $parentCommentId,
            'reply_author_id' => $replyAuthorId,
            'thread_id' => $commentData['thread_id']
        ];
        
        createNotification($parentCommentAuthorId, 'comment_reply', $title, $message, $data);
    }
    
    /**
     * Notify about thread vote
     */
    public function notifyThreadVote(string $threadId, string $voterId, string $threadAuthorId, string $voteType): void
    {
        if ($voterId === $threadAuthorId) {
            return; // Don't notify if voting on own thread
        }
        
        if (!shouldReceiveNotification($threadAuthorId, 'thread_vote')) {
            return;
        }
        
        // Only notify for upvotes to avoid spam
        if ($voteType !== 'upvote') {
            return;
        }
        
        $threadData = $this->getThreadDetails($threadId);
        $voter = $this->getUserDetails($voterId);
        
        if (!$threadData || !$voter) {
            return;
        }
        
        $title = "Your thread received an upvote";
        $message = "{$voter['username']} upvoted your thread \"{$threadData['title']}\"";
        $data = [
            'type' => 'thread_vote',
            'thread_id' => $threadId,
            'voter_id' => $voterId,
            'vote_type' => $voteType
        ];
        
        createNotification($threadAuthorId, 'thread_vote', $title, $message, $data);
    }
    
    /**
     * Notify about comment vote
     */
    public function notifyCommentVote(string $commentId, string $voterId, string $commentAuthorId, string $voteType): void
    {
        if ($voterId === $commentAuthorId) {
            return; // Don't notify if voting on own comment
        }
        
        if (!shouldReceiveNotification($commentAuthorId, 'comment_vote')) {
            return;
        }
        
        // Only notify for upvotes to avoid spam
        if ($voteType !== 'upvote') {
            return;
        }
        
        $commentData = $this->getCommentDetails($commentId);
        $voter = $this->getUserDetails($voterId);
        
        if (!$commentData || !$voter) {
            return;
        }
        
        $title = "Your comment received an upvote";
        $message = "{$voter['username']} upvoted your comment";
        $data = [
            'type' => 'comment_vote',
            'comment_id' => $commentId,
            'voter_id' => $voterId,
            'vote_type' => $voteType,
            'thread_id' => $commentData['thread_id']
        ];
        
        createNotification($commentAuthorId, 'comment_vote', $title, $message, $data);
    }
    
    /**
     * Notify users about new threads (only if they have it enabled)
     */
    public function notifyNewThread(string $threadId, string $authorId): void
    {
        $threadData = $this->getThreadDetails($threadId);
        $author = $this->getUserDetails($authorId);
        
        if (!$threadData || !$author) {
            return;
        }
        
        // Get all users who want new thread notifications (excluding the author)
        $sql = "SELECT userId FROM notification_settings 
                WHERE new_thread = 1 AND userId != ?";
        $users = $this->db->query($sql, [$authorId])->fetchAll();
        
        $title = "New thread posted";
        $message = "{$author['username']} posted a new thread: \"{$threadData['title']}\"";
        $data = [
            'type' => 'new_thread',
            'thread_id' => $threadId,
            'author_id' => $authorId
        ];
        
        foreach ($users as $user) {
            createNotification($user['userId'], 'new_thread', $title, $message, $data);
        }
    }
    
    /**
     * Notify user when mentioned in a comment
     */
    public function notifyMention(string $mentionedUserId, string $mentionerUserId, string $threadId, string $commentId = null): void
    {
        if ($mentionedUserId === $mentionerUserId) {
            return; // Don't notify if mentioning yourself
        }
        
        if (!shouldReceiveNotification($mentionedUserId, 'mention')) {
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
            'type' => 'mention',
            'mentioner_id' => $mentionerUserId,
            'thread_id' => $threadId,
            'comment_id' => $commentId
        ];
        
        createNotification($mentionedUserId, 'mention', $title, $message, $data);
    }
    
    /**
     * Send system notification to a user
     */
    public function notifySystem(string $userId, string $title, string $message, array $data = []): void
    {
        if (!shouldReceiveNotification($userId, 'system')) {
            return;
        }
        
        $data['type'] = 'system';
        createNotification($userId, 'system', $title, $message, $data);
    }
    
    /**
     * Send system notification to all users
     */
    public function notifyAllUsers(string $title, string $message, array $data = []): void
    {
        $sql = "SELECT userId FROM notification_settings WHERE system = 1
                UNION 
                SELECT id as userId FROM users WHERE id NOT IN (SELECT userId FROM notification_settings)";
        $users = $this->db->query($sql)->fetchAll();
        
        $data['type'] = 'system';
        
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
            $sql = "SELECT id, title, authorId FROM threads WHERE id = ?";
            return $this->db->query($sql, [$threadId])->fetch() ?: null;
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
            $sql = "SELECT id, authorId, threadId as thread_id FROM comments WHERE id = ?";
            return $this->db->query($sql, [$commentId])->fetch() ?: null;
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
            $sql = "SELECT id, username, name FROM users WHERE id = ?";
            return $this->db->query($sql, [$userId])->fetch() ?: null;
        } catch (\Exception $e) {
            error_log("Failed to get user details: " . $e->getMessage());
            return null;
        }
    }
}
