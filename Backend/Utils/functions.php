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
    http_response_code($httpCode);
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