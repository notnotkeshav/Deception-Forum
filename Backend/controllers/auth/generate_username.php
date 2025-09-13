<?php
// Fixed username generation endpoint

use Backend\Core\App;

$cache = App::container()->resolve('Core\Cache');
header('Content-Type: application/json');

// CORS headers for AJAX requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Rate limiting configuration
$ipAddress = $_SERVER['REMOTE_ADDR'];
$ipHash = hash('sha256', $ipAddress);
$ipRateLimitKey = "username_gen_ip:" . $ipHash;
$ipUsernamePoolKey = "username_pool_ip:" . $ipHash;
$maxRequests = 7;
$windowSeconds = 3600; // 1 hour

try {
    // Get current request log and clean old entries
    $requestLog = $cache->get($ipRateLimitKey)['value'] ?? [];
    $now = time();
    $requestLog = array_filter($requestLog, fn($timestamp) => $now - $timestamp < $windowSeconds);
    
    // Get current usernames
    $currentUsernames = $cache->get($ipUsernamePoolKey)['value'] ?? [];
    
    // Check if rate limited FIRST
    if (count($requestLog) >= $maxRequests) {
        sendJsonResponse(true, "Rate limit reached. Please choose from available usernames.", [
            'usernames' => $currentUsernames,
            'rate_limited' => true,
            'remaining_requests' => 0,
            'total_usernames' => count($currentUsernames)
        ]);
        exit();
    }
    
    if ($method === 'GET' || $method === 'POST') {
        // Generate new username
        $newUsername = generateUsername(random_int(15, 25));
        $currentUsernames[] = $newUsername;
        
        // Add to request log
        $requestLog[] = $now;
        
        // Save to cache
        $cache->set($ipRateLimitKey, $requestLog, $windowSeconds);
        $cache->set($ipUsernamePoolKey, $currentUsernames, $windowSeconds);
        
        $remainingRequests = $maxRequests - count($requestLog);
        $isInitial = (count($currentUsernames) === 1);

        sendJsonResponse(true, $isInitial ? "Initial username generated successfully." : "Username generated successfully.", [
            'usernames' => $currentUsernames,
            'new_username' => $newUsername,
            'is_initial' => $isInitial,
            'rate_limited' => ($remainingRequests === 0),
            'remaining_requests' => $remainingRequests,
            'total_usernames' => count($currentUsernames)
        ]);
    }
} catch (Exception $e) {
    error_log("Username generation error: " . $e->getMessage());
    sendJsonResponse(false, "Failed to generate username. Please try again.", [], 500);
}
?>