<?php

/**
 * Dump variable and terminate execution
 */
function dumpAndDie($value): void
{
    echo "<pre>";
    var_dump($value);
    echo "</pre>";
    die();
}

/**
 * Get current request URI
 */
function getURL(): string
{
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
}

/**
 * Abort with error page
 */
function abort(int $code = 404, array $data = []): void
{
    http_response_code($code);

    if ($code === 419) {
        $data['message'] = $data['message'] ?? 'Page Expired';
    }

    $errorView = "errors/{$code}.php";
    if (function_exists('view_path') && file_exists(view_path($errorView))) {
        view($errorView, $data);
    } else {
        echo "<h1>Error {$code}</h1>";
        if (!empty($data['message'])) {
            echo "<p>{$data['message']}</p>";
        }
    }
    die();
}

/**
 * Get absolute path with base directory
 */
function base_path(string $path = ''): string
{
    return BASE_PATH . ltrim($path, "/");
}

/**
 * Get view file path
 */
function view_path(string $path = ''): string
{
    return base_path('frontend/views/' . ltrim($path, "/"));
}

/**
 * Render a view with data
 */
function view(string $path, array $args = []): void
{
    $viewFile = view_path($path);

    if (!file_exists($viewFile)) {
        throw new RuntimeException("View file not found: {$viewFile}");
    }

    extract($args);
    require $viewFile;
}

/**
 * Redirect to URL
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit();
}

/**
 * Get query parameters from URL
 */
function getQueryParams(): array
{
    $url = $_SERVER['REQUEST_URI'] ?? '';
    $url_components = parse_url($url);

    if (isset($url_components['query'])) {
        parse_str($url_components['query'], $params);
        return $params;
    }

    return [];
}

/**
 * Get JSON request body
 */
function getRequestBody(): array
{
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    return json_last_error() === JSON_ERROR_NONE ? $data : [];
}

/**
 * Send JSON response
 */
function sendJsonResponse(
    bool $success,
    string $message,
    array $details = [],
    int $httpCode = 200
): void {
    http_response_code((int)$httpCode);
    header('Content-Type: application/json');

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "details" => $details
    ]);

    exit();
}

/**
 * Get Bearer token from Authorization header
 */
function getBearerToken(): ?string
{
    $headers = getallheaders();

    if (isset($headers['Authorization'])) {
        if (str_starts_with($headers['Authorization'], 'Bearer ')) {
            return substr($headers['Authorization'], 7);
        }
    }

    return null;
}

/**
 * Generate secure random password
 */
function generateRandomPassword(int $length = 25): string
{
    if ($length < 25 || $length > 255) {
        throw new InvalidArgumentException("Length must be between 25 and 255 characters.");
    }

    $charsets = [
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'abcdefghijklmnopqrstuvwxyz',
        '0123456789',
        '!@#$%^&*()_-+=<>?{}[]|~:;",\'./\\'
    ];

    $password = '';

    // Ensure at least 2 uppercase, 2 lowercase, 3 digits, and 5 special chars
    $password .= substr(str_shuffle($charsets[0]), 0, 2);
    $password .= substr(str_shuffle($charsets[1]), 0, 2);
    $password .= substr(str_shuffle($charsets[2]), 0, 3);
    $password .= substr(str_shuffle($charsets[3]), 0, 5);

    // Fill remaining length with random characters from all sets
    $allChars = implode('', $charsets);
    $remaining = $length - strlen($password);

    for ($i = 0; $i < $remaining; $i++) {
        $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }

    return str_shuffle($password);
}

/**
 * Load environment variables
 */
function loadEnv(string $file): void
{
    if (!file_exists($file)) {
        throw new RuntimeException('.env file not found');
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }

            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

/**
 * Queue email for background processing
 */
function queueEmail(string $to, string $subject, string $body): bool
{
    $queueDir = __DIR__ . "/../Core/email_queue";

    if (!is_dir($queueDir)) {
        mkdir($queueDir, 0755, true);
    }

    $jobId = uniqid('email_', true);
    $file = $queueDir . "/{$jobId}.json";
    $data = [
        'to' => $to,
        'subject' => $subject,
        'body' => $body,
        'created_at' => date('Y-m-d H:i:s')
    ];

    return file_put_contents($file, json_encode($data)) !== false;
}

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken(string $token): bool
{
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        abort(419, ['message' => 'CSRF token mismatch']);
        return false;
    }
    return true;
}

/**
 * Get current authenticated user
 */
function authUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Check if user is authenticated
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Check if user has partial auth (TOTP step)
 */
function hasPartialAuth(): bool
{
    return !empty($_SESSION['partial_auth']);
}

/**
 * Create a notification for a user
 * 
 * @param string $userId - Recipient user ID
 * @param string $type - Notification type (thread_comment, comment_reply, etc.)
 * @param string $title - Notification title
 * @param string $message - Notification message
 * @param array|null $data - Additional JSON data (e.g., thread_id, comment_id)
 * @return bool
 */
function createNotification($userId, $type, $title, $message, $data = null): bool
{
    $db = \Backend\Core\App::container()->resolve('Core\Database');

    try {
        // Check user's notification settings
        $settingsStmt = $db->query(
            "SELECT {$type} as enabled FROM notification_settings WHERE userId = :userId",
            [':userId' => $userId]
        );
        $settings = $db->getOne($settingsStmt);

        // If user has disabled this notification type, don't create it
        if ($settings && !$settings['enabled']) {
            return false;
        }

        // Create notification
        $db->query(
            "INSERT INTO notifications (userId, type, title, message, data) 
             VALUES (:userId, :type, :title, :message, :data)",
            [
                ':userId' => $userId,
                ':type' => $type,
                ':title' => $title,
                ':message' => $message,
                ':data' => $data ? json_encode($data) : null
            ]
        );

        return true;
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notifications for a user
 */
function getUnreadNotifications(string $userId, int $limit = 50): array
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        $stmt = $db->query(
            "SELECT id, type, title, message, data, created_at 
             FROM notifications 
             WHERE userId = :userId AND read_at IS NULL 
             ORDER BY created_at DESC 
             LIMIT :limit",
            [':userId' => $userId, ':limit' => $limit]
        );

        return $db->getAll($stmt);
    } catch (Exception $e) {
        error_log("Failed to get unread notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all notifications for a user (with pagination)
 */
function getUserNotifications(string $userId, int $offset = 0, int $limit = 20): array
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        $stmt = $db->query(
            "SELECT id, type, title, message, data, read_at, created_at 
             FROM notifications 
             WHERE userId = :userId 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset",
            [':userId' => $userId, ':limit' => $limit, ':offset' => $offset]
        );

        return $db->getAll($stmt);
    } catch (Exception $e) {
        error_log("Failed to get user notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark notification(s) as read
 */
function markNotificationsAsRead(string $userId, array $notificationIds = null): bool
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        if ($notificationIds === null) {
            // Mark all notifications as read
            $db->query(
                "UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE userId = :userId AND read_at IS NULL",
                [':userId' => $userId]
            );
        } else {
            // Mark specific notifications as read
            $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
            $params = [':userId' => $userId];

            foreach ($notificationIds as $i => $id) {
                $params[":id{$i}"] = $id;
            }

            $placeholderStr = implode(',', array_map(fn($i) => ":id{$i}", array_keys($notificationIds)));

            $db->query(
                "UPDATE notifications SET read_at = CURRENT_TIMESTAMP 
                 WHERE userId = :userId AND id IN ({$placeholderStr}) AND read_at IS NULL",
                $params
            );
        }

        return true;
    } catch (Exception $e) {
        error_log("Failed to mark notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotificationCount(string $userId): int
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        $stmt = $db->query(
            "SELECT COUNT(*) as count FROM notifications WHERE userId = :userId AND read_at IS NULL",
            [':userId' => $userId]
        );
        $result = $db->getOne($stmt);

        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        error_log("Failed to get unread notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get notification settings for a user
 */
function getNotificationSettings(string $userId): array
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        $stmt = $db->query(
            "SELECT thread_comment, comment_reply, thread_vote, comment_vote, new_thread, mention, system 
             FROM notification_settings 
             WHERE userId = :userId",
            [':userId' => $userId]
        );
        $result = $db->getOne($stmt);

        if (!$result) {
            // Create default settings
            $db->query(
                "INSERT INTO notification_settings (userId) VALUES (:userId)",
                [':userId' => $userId]
            );

            return [
                'thread_comment' => true,
                'comment_reply' => true,
                'thread_vote' => true,
                'comment_vote' => true,
                'new_thread' => false,
                'mention' => true,
                'system' => true
            ];
        }

        return $result;
    } catch (Exception $e) {
        error_log("Failed to get notification settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Update notification settings for a user
 */
function updateNotificationSettings(string $userId, array $settings): bool
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        $allowedSettings = ['thread_comment', 'comment_reply', 'thread_vote', 'comment_vote', 'new_thread', 'mention', 'system'];
        $updateFields = [];
        $params = [':userId' => $userId];

        foreach ($allowedSettings as $setting) {
            if (isset($settings[$setting])) {
                $updateFields[] = "{$setting} = :{$setting}";
                $params[":{$setting}"] = (bool)$settings[$setting] ? 1 : 0;
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE notification_settings SET " . implode(', ', $updateFields) . " WHERE userId = :userId";
        $db->query($sql, $params);

        return true;
    } catch (Exception $e) {
        error_log("Failed to update notification settings: " . $e->getMessage());
        return false;
    }
}

/**
 * Simple polling helper for notifications - OPTIMIZED VERSION
 * Returns new notifications since last check without blocking
 * 
 * @param string $userId - User ID
 * @param int $lastCheckTime - Unix timestamp of last check
 * @return array - Response with new notifications and count
 */
function pollNotifications(string $userId, int $lastCheckTime = 0): array
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        // Get new notifications since last check
        $newNotifications = [];
        if ($lastCheckTime > 0) {
            $stmt = $db->query(
                "SELECT id, type, title, message, data, created_at 
                 FROM notifications 
                 WHERE userId = :userId 
                 AND UNIX_TIMESTAMP(created_at) > :lastCheck 
                 ORDER BY created_at DESC 
                 LIMIT 10",
                [
                    ':userId' => $userId,
                    ':lastCheck' => $lastCheckTime
                ]
            );
            $newNotifications = $db->getAll($stmt);
        }

        // Get unread count
        $unreadCount = getUnreadNotificationCount($userId);

        return [
            'success' => true,
            'new_notifications' => $newNotifications,
            'unread_count' => $unreadCount,
            'timestamp' => time()
        ];
    } catch (Exception $e) {
        error_log("Polling error: " . $e->getMessage());
        return [
            'success' => false,
            'new_notifications' => [],
            'unread_count' => 0,
            'timestamp' => time()
        ];
    }
}

/**
 * Clean old notifications (keep only recent ones)
 */
function cleanOldNotifications(int $daysToKeep = 30): bool
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        $db->query(
            "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)",
            [':days' => $daysToKeep]
        );

        return true;
    } catch (Exception $e) {
        error_log("Failed to clean old notifications: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all members of a group chat
 * 
 * @param string $groupId - The group chat ID
 * @param bool $activeOnly - Only return active members (default: true)
 * @return array - Array of group members with their details
 */
function getGroupMembers(string $groupId, bool $activeOnly = true): array
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        $query = "SELECT gm.userId, gm.role, gm.status, gm.joinedAt,
                         u.username, u.name
                  FROM groupMembers gm
                  JOIN users u ON gm.userId = u.id
                  WHERE gm.groupId = :groupId";

        if ($activeOnly) {
            $query .= " AND gm.status = 'active'";
        }

        $query .= " ORDER BY gm.joinedAt ASC";

        $stmt = $db->query($query, [':groupId' => $groupId]);
        return $db->getAll($stmt);
    } catch (Exception $e) {
        error_log("Failed to get group members: " . $e->getMessage());
        return [];
    }
}

/**
 * Get group details
 * 
 * @param string $groupId - The group chat ID
 * @return array|null - Group details or null if not found
 */
function getGroupDetails(string $groupId): ?array
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        $stmt = $db->query(
            "SELECT id, groupName, createdBy, createdAt 
             FROM chatGroups 
             WHERE id = :groupId",
            [':groupId' => $groupId]
        );

        return $db->getOne($stmt) ?: null;
    } catch (Exception $e) {
        error_log("Failed to get group details: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if user is a member of a group
 * 
 * @param string $groupId - The group chat ID
 * @param string $userId - The user ID
 * @return bool - True if user is an active member
 */
function isGroupMember(string $groupId, string $userId): bool
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        $stmt = $db->query(
            "SELECT id FROM groupMembers 
             WHERE groupId = :groupId 
             AND userId = :userId 
             AND status = 'active'",
            [':groupId' => $groupId, ':userId' => $userId]
        );

        return $db->getOne($stmt) !== false;
    } catch (Exception $e) {
        error_log("Failed to check group membership: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's role in a group
 * 
 * @param string $groupId - The group chat ID
 * @param string $userId - The user ID
 * @return string|null - User's role or null if not a member
 */
function getGroupMemberRole(string $groupId, string $userId): ?string
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');

        $stmt = $db->query(
            "SELECT role FROM groupMembers 
             WHERE groupId = :groupId 
             AND userId = :userId 
             AND status = 'active'",
            [':groupId' => $groupId, ':userId' => $userId]
        );

        $result = $db->getOne($stmt);
        return $result ? $result['role'] : null;
    } catch (Exception $e) {
        error_log("Failed to get member role: " . $e->getMessage());
        return null;
    }
}
