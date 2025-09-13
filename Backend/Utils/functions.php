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
        // Special case for CSRF token mismatch
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
    $queueDir = __DIR__ . "/../core/email_queue";
    
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
 * Create a new notification for a user
 */
function createNotification(
    string $userId,
    string $type,
    string $title,
    string $message,
    array $data = null
): bool {
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');
        
        $sql = "INSERT INTO notifications (userId, type, title, message, data) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->query($sql, [$userId, $type, $title, $message, $data ? json_encode($data) : null]);
        
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
        
        $sql = "SELECT * FROM notifications 
                WHERE userId = ? AND read_at IS NULL 
                ORDER BY created_at DESC LIMIT ?";
        
        return $db->query($sql, [$userId, $limit])->fetchAll();
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
        
        $sql = "SELECT * FROM notifications 
                WHERE userId = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $db->query($sql, [$userId, $limit, $offset])->fetchAll();
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
            $sql = "UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE userId = ? AND read_at IS NULL";
            $db->query($sql, [$userId]);
        } else {
            // Mark specific notifications as read
            $placeholders = str_repeat('?,', count($notificationIds) - 1) . '?';
            $sql = "UPDATE notifications SET read_at = CURRENT_TIMESTAMP 
                    WHERE userId = ? AND id IN ($placeholders) AND read_at IS NULL";
            $params = array_merge([$userId], $notificationIds);
            $db->query($sql, $params);
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
        
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE userId = ? AND read_at IS NULL";
        $result = $db->query($sql, [$userId])->fetch();
        
        return (int) $result['count'];
    } catch (Exception $e) {
        error_log("Failed to get unread notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if user should receive notifications of a specific type
 */
function shouldReceiveNotification(string $userId, string $type): bool
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');
        
        $sql = "SELECT $type FROM notification_settings WHERE userId = ?";
        $result = $db->query($sql, [$userId])->fetch();
        
        return $result ? (bool) $result[$type] : true; // Default to true if no settings found
    } catch (Exception $e) {
        error_log("Failed to check notification settings: " . $e->getMessage());
        return true; // Default to allowing notifications
    }
}

/**
 * Get notification settings for a user
 */
function getNotificationSettings(string $userId): array
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');
        
        $sql = "SELECT * FROM notification_settings WHERE userId = ?";
        $result = $db->query($sql, [$userId])->fetch();
        
        if (!$result) {
            // Create default settings if they don't exist
            $defaultSettings = [
                'userId' => $userId,
                'thread_comment' => true,
                'comment_reply' => true,
                'thread_vote' => true,
                'comment_vote' => true,
                'new_thread' => false,
                'mention' => true,
                'system' => true
            ];
            
            $insertSql = "INSERT INTO notification_settings (userId, thread_comment, comment_reply, thread_vote, comment_vote, new_thread, mention, system) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $db->query($insertSql, array_values($defaultSettings));
            
            return $defaultSettings;
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
        $params = [];
        
        foreach ($allowedSettings as $setting) {
            if (isset($settings[$setting])) {
                $updateFields[] = "$setting = ?";
                $params[] = (bool) $settings[$setting];
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $params[] = $userId;
        $sql = "UPDATE notification_settings SET " . implode(', ', $updateFields) . " WHERE userId = ?";
        
        $db->query($sql, $params);
        return true;
    } catch (Exception $e) {
        error_log("Failed to update notification settings: " . $e->getMessage());
        return false;
    }
}

/**
 * Long polling helper for notifications
 */
function longPollNotifications(string $userId, int $lastCheckTime = 0, int $timeout = 30): array
{
    $startTime = time();
    $maxTime = $startTime + $timeout;
    
    while (time() < $maxTime) {
        try {
            $db = \Backend\Core\App::container()->resolve('Core\Database');
            
            // Check for new notifications since last check
            $sql = "SELECT * FROM notifications 
                    WHERE userId = ? AND UNIX_TIMESTAMP(created_at) > ? 
                    ORDER BY created_at DESC";
            
            $notifications = $db->query($sql, [$userId, $lastCheckTime])->fetchAll();
            
            if (!empty($notifications)) {
                return [
                    'success' => true,
                    'notifications' => $notifications,
                    'unread_count' => getUnreadNotificationCount($userId),
                    'timestamp' => time()
                ];
            }
            
            // Sleep for a short interval before checking again
            usleep(500000); // 0.5 seconds
            
        } catch (Exception $e) {
            error_log("Long polling error: " . $e->getMessage());
            break;
        }
    }
    
    // Timeout reached, return empty result
    return [
        'success' => true,
        'notifications' => [],
        'unread_count' => getUnreadNotificationCount($userId),
        'timestamp' => time()
    ];
}

/**
 * Clean old notifications (keep only recent ones)
 */
function cleanOldNotifications(int $daysToKeep = 30): bool
{
    try {
        $db = \Backend\Core\App::container()->resolve('Core\Database');
        
        $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $db->query($sql, [$daysToKeep]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to clean old notifications: " . $e->getMessage());
        return false;
    }
}