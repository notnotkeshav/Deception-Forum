<?php
/**
 * Secure browser detection for desktop Firefox
 * Validates and sanitizes user agent before processing
 */

// Input validation and sanitization
$userAgent = '';
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    // Sanitize user agent string
    $userAgent = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
    
    // Additional length check to prevent extremely long user agents
    if (strlen($userAgent) > 1000) {
        $userAgent = substr($userAgent, 0, 1000);
    }
} else {
    // Handle missing user agent (some bots/curl requests)
    $userAgent = '';
}

/**
 * Check if the request is from desktop Firefox
 * @param string $ua User agent string
 * @return bool True if desktop Firefox, false otherwise
 */
function isDesktopFirefox($ua) {
    // Convert to lowercase for case-insensitive comparison
    $ua = strtolower($ua);
    
    // Must contain 'firefox'
    if (strpos($ua, 'firefox') === false) {
        return false;
    }
    
    // Exclude mobile/tablet variants
    $mobileIndicators = [
        'mobile',
        'tablet',
        'android',
        'iphone',
        'ipad',
        'ipod',
        'blackberry',
        'windows phone',
        'opera mini',
        'opera mobi'
    ];
    
    foreach ($mobileIndicators as $indicator) {
        if (strpos($ua, $indicator) !== false) {
            return false;
        }
    }
    
    // Additional check: Firefox on desktop typically contains 'gecko'
    if (strpos($ua, 'gecko') === false) {
        return false;
    }
    
    return true;
}

// Perform browser check
if (!isDesktopFirefox($userAgent)) {
    // Log the attempt for security monitoring (optional)
    error_log("Browser check failed for User-Agent: " . $userAgent, 0);
    
    // Prevent any potential XSS by not passing user input directly to view
    $safeData = [
        "heading" => "Homepage",
        "error_type" => "unsupported_browser",
        "timestamp" => date('Y-m-d H:i:s')
    ];
    
    $viewFile = base_path('/frontend/views/browser-check.view.php');
    if (file_exists($viewFile)) {
        http_response_code(406);
        view("browser-check.view.php", $safeData);
    } else {
        http_response_code(406);
        echo "<!DOCTYPE html><html><head><title>Unsupported Browser</title></head>";
        echo "<body><h1>Unsupported Browser</h1>";
        echo "<p>This application requires desktop Firefox.</p></body></html>";
    }
    exit;
}

// If we reach here, the browser check passed
// Continue with normal application flow
?>