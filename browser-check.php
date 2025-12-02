<?php
/**
 * Hardened browser detection for desktop Firefox
 * Multi-layered validation to prevent spoofing
 */

// Input validation and sanitization
$userAgent = '';
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    // Use htmlspecialchars instead of deprecated FILTER_SANITIZE_STRING
    $userAgent = htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
    
    // Length check to prevent extremely long user agents
    if (strlen($userAgent) > 1000) {
        $userAgent = substr($userAgent, 0, 1000);
    }
} else {
    // No user agent = reject
    blockBrowser('missing_user_agent', '');
}

/**
 * Advanced Firefox detection with anti-spoofing checks
 * @param string $ua User agent string
 * @return bool True if legitimate desktop Firefox
 */
function isLegitimateDesktopFirefox($ua) {
    $ua = strtolower($ua);
    
    // === LAYER 1: Basic Firefox Requirements ===
    if (strpos($ua, 'firefox') === false) {
        return false;
    }
    
    if (strpos($ua, 'gecko') === false) {
        return false;
    }
    
    // === LAYER 2: Exclude Mobile/Tablet ===
    $mobileIndicators = [
        'mobile', 'tablet', 'android', 'iphone', 'ipad', 'ipod',
        'blackberry', 'windows phone', 'opera mini', 'opera mobi',
        'fennec', 'maemo', 'symbian'
    ];
    
    foreach ($mobileIndicators as $indicator) {
        if (strpos($ua, $indicator) !== false) {
            return false;
        }
    }
    
    // === LAYER 3: Detect Common Spoofing Patterns ===
    
    // Real Firefox never contains these together
    $invalidCombinations = [
        ['firefox', 'chrome'],  // Firefox doesn't report Chrome
        ['firefox', 'edg'],     // Firefox doesn't report Edge
        ['firefox', 'safari', 'chrome'], // Apple WebKit with Chrome indicates spoofing
        ['firefox', 'opr'],     // Firefox doesn't report Opera
    ];
    
    foreach ($invalidCombinations as $combo) {
        $matchCount = 0;
        foreach ($combo as $keyword) {
            if (strpos($ua, $keyword) !== false) {
                $matchCount++;
            }
        }
        if ($matchCount === count($combo)) {
            return false; // All keywords present = spoofed
        }
    }
    
    // === LAYER 4: Version Format Validation ===
    // Real Firefox: "Firefox/115.0" or "Firefox/120.0.1"
    if (!preg_match('/firefox\/\d{2,3}\.\d+(\.\d+)?/i', $ua)) {
        return false; // Invalid or missing version format
    }
    
    // === LAYER 5: Gecko Version Validation ===
    // Real Firefox has "Gecko/YYYYMMDD" or "Gecko/20100101"
    if (!preg_match('/gecko\/\d{8,}/i', $ua)) {
        return false; // Missing or malformed Gecko date
    }
    
    // === LAYER 6: OS Validation ===
    // Firefox must run on a real desktop OS
    $validOSPatterns = [
        'windows nt',
        'macintosh',
        'x11.*linux',
        'x11.*freebsd',
        'x11.*openbsd'
    ];
    
    $hasValidOS = false;
    foreach ($validOSPatterns as $osPattern) {
        if (preg_match('/' . $osPattern . '/i', $ua)) {
            $hasValidOS = true;
            break;
        }
    }
    
    if (!$hasValidOS) {
        return false; // No recognized desktop OS
    }
    
    // === LAYER 7: Bot/Scraper Detection ===
    $botIndicators = [
        'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
        'python', 'java', 'apache', 'perl', 'ruby', 'go-http',
        'postman', 'insomnia', 'httpie', 'axios', 'node-fetch'
    ];
    
    foreach ($botIndicators as $bot) {
        if (strpos($ua, $bot) !== false) {
            return false;
        }
    }
    
    // === LAYER 8: Character Sequence Analysis ===
    // Real Firefox UA has specific ordering
    $geckoPos = strpos($ua, 'gecko');
    $firefoxPos = strpos($ua, 'firefox');
    
    // Gecko must come before Firefox in the string
    if ($geckoPos === false || $firefoxPos === false || $geckoPos >= $firefoxPos) {
        return false;
    }
    
    return true;
}

/**
 * Additional JavaScript-based checks via headers
 * Real Firefox sends specific Accept headers
 */
function validateFirefoxHeaders() {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    
    // Real browsers send proper Accept headers
    if (empty($accept)) {
        return false;
    }
    
    // Check for Accept-Language (bots often omit this)
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return false;
    }
    
    // Check for Accept-Encoding (real browsers always send this)
    if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
        return false;
    }
    
    return true;
}

/**
 * Block non-Firefox browsers
 */
function blockBrowser($errorType, $userAgent) {
    error_log("Browser check failed ($errorType) for User-Agent: " . $userAgent);
    
    $safeData = [
        "heading" => "Access Denied",
        "error_type" => $errorType,
        "timestamp" => date('Y-m-d H:i:s'),
        "user_agent" => htmlspecialchars($userAgent, ENT_QUOTES, 'UTF-8')
    ];
    
    http_response_code(406); // Not Acceptable
    
    // Try to load view file
    $viewFile = base_path('frontend/views/browser-check.view.php');
    if (file_exists($viewFile)) {
        extract($safeData); // Makes array keys available as variables
        require $viewFile;
    } else {
        // Fallback HTML if view file missing
        echo '<!DOCTYPE html><html><head><title>Unsupported Browser</title>';
        echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<style>body{font-family:system-ui,sans-serif;text-align:center;padding:50px;background:#f5f5f5;}';
        echo '.container{max-width:600px;margin:0 auto;background:white;padding:40px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}';
        echo 'h1{color:#e74c3c;margin-bottom:20px;}a{color:#3498db;text-decoration:none;}';
        echo '.error-code{color:#7f8c8d;font-size:12px;margin-top:30px;}</style></head>';
        echo '<body><div class="container">';
        echo '<h1>🦊 Firefox Desktop Required</h1>';
        echo '<p>This application requires <strong>Mozilla Firefox Desktop Browser</strong> for security reasons.</p>';
        echo '<p style="margin:30px 0;"><a href="https://www.mozilla.org/firefox/" target="_blank" style="background:#0060df;color:white;padding:12px 24px;border-radius:4px;display:inline-block;">Download Firefox</a></p>';
        echo '<p style="color:#7f8c8d;font-size:14px;">Using a different browser? This site only supports Firefox Desktop.</p>';
        echo '<div class="error-code">Error: ' . htmlspecialchars($errorType) . '</div>';
        echo '</div></body></html>';
    }
    exit;
}

// === PERFORM ALL CHECKS ===

// Check 1: User Agent validation
if (!isLegitimateDesktopFirefox($userAgent)) {
    blockBrowser('invalid_user_agent', $userAgent);
}

// Check 2: HTTP Headers validation
if (!validateFirefoxHeaders()) {
    blockBrowser('invalid_headers', $userAgent);
}

// If we reach here, all checks passed
// Continue with normal application flow
