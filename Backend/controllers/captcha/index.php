<?php
class CaptchaGenerator
{
    private int $width;
    private int $height;
    private int $length;
    private array $fonts;
    private array $colors;
    
    public function __construct(int $width = 200, int $height = 80, int $length = 10)
    {
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
        
        // Define colors for text and background
        $this->colors = [
            'background' => [255, 255, 255],
            'text' => [
                [0, 0, 0],       // Black
                [255, 0, 0],     // Red
                [0, 0, 255],     // Blue
                [0, 128, 0],     // Green
                [128, 0, 128],   // Purple
                [255, 165, 0],   // Orange
            ],
            'noise' => [200, 200, 200], // Light gray
            'line' => [150, 150, 150],   // Gray
        ];
    }
    
    public function generateCode(): string
    {
        // Use alphanumeric characters, excluding confusing ones
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        
        for ($i = 0; $i < $this->length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
    
    public function createImage(string $code): void
    {
        // Create image
        $image = imagecreate($this->width, $this->height);
        
        // Set background color
        $bgColor = imagecolorallocate(
            $image,
            $this->colors['background'][0],
            $this->colors['background'][1],
            $this->colors['background'][2]
        );
        
        // Add noise points
        $this->addNoise($image);
        
        // Add interference lines
        $this->addLines($image);
        
        // Add text
        $this->addText($image, $code);
        
        // Output image
        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        imagepng($image);
        imagedestroy($image);
    }
    
    private function addNoise($image): void
    {
        $noiseColor = imagecolorallocate(
            $image,
            $this->colors['noise'][0],
            $this->colors['noise'][1],
            $this->colors['noise'][2]
        );
        
        // Add random noise points
        for ($i = 0; $i < 100; $i++) {
            imagesetpixel(
                $image,
                random_int(0, $this->width),
                random_int(0, $this->height),
                $noiseColor
            );
        }
    }
    
    private function addLines($image): void
    {
        $lineColor = imagecolorallocate(
            $image,
            $this->colors['line'][0],
            $this->colors['line'][1],
            $this->colors['line'][2]
        );
        
        // Add random interference lines
        for ($i = 0; $i < 5; $i++) {
            imageline(
                $image,
                random_int(0, $this->width),
                random_int(0, $this->height),
                random_int(0, $this->width),
                random_int(0, $this->height),
                $lineColor
            );
        }
    }
    
    private function addText($image, string $code): void
    {
        $fontSize = 5; // Built-in font size (1-5)
        $fontWidth = imagefontwidth($fontSize);
        $fontHeight = imagefontheight($fontSize);
        
        // Calculate starting position to center the text
        $totalWidth = strlen($code) * $fontWidth;
        $startX = ($this->width - $totalWidth) / 2;
        $startY = ($this->height - $fontHeight) / 2;
        
        // Add each character with slight variations
        for ($i = 0; $i < strlen($code); $i++) {
            // Random color for each character
            $colorIndex = random_int(0, count($this->colors['text']) - 1);
            $textColor = imagecolorallocate(
                $image,
                $this->colors['text'][$colorIndex][0],
                $this->colors['text'][$colorIndex][1],
                $this->colors['text'][$colorIndex][2]
            );
            
            // Add slight random positioning
            $x = $startX + ($i * $fontWidth) + random_int(-3, 3);
            $y = $startY + random_int(-5, 5);
            
            // Ensure coordinates are within bounds
            $x = max(0, min($x, $this->width - $fontWidth));
            $y = max(0, min($y, $this->height - $fontHeight));
            
            imagestring($image, $fontSize, $x, $y, $code[$i], $textColor);
        }
    }
}

class CaptchaVerifier
{
    private string $sessionKey;
    private int $expireTime;
    
    public function __construct(string $sessionKey = 'captcha_code', int $expireTime = 12)
    {
        $this->sessionKey = $sessionKey;
        $this->expireTime = $expireTime; // 2 minutes default
    }
    
    public function storeCode(string $code): void
    {
        $_SESSION[$this->sessionKey] = [
            'code' => strtoupper($code),
            'timestamp' => time()
        ];
    }
    
    public function verify(string $userInput): bool
    {
        if (!isset($_SESSION[$this->sessionKey])) {
            return false;
        }
        
        $stored = $_SESSION[$this->sessionKey];
        
        // Check if expired
        if (time() - $stored['timestamp'] > $this->expireTime) {
            $this->clearCode();
            return false;
        }
        
        // Verify code (case-insensitive)
        $isValid = strtoupper(trim($userInput)) === $stored['code'];
        
        // Clear the code after verification attempt (one-time use)
        $this->clearCode();
        
        return $isValid;
    }
    
    public function clearCode(): void
    {
        unset($_SESSION[$this->sessionKey]);
    }
    
    public function isExpired(): bool
    {
        if (!isset($_SESSION[$this->sessionKey])) {
            return true;
        }
        
        return time() - $_SESSION[$this->sessionKey]['timestamp'] > $this->expireTime;
    }
}

// Main logic
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Generate and display CAPTCHA image
        $generator = new CaptchaGenerator();
        $verifier = new CaptchaVerifier();
        
        // Generate new code
        $code = $generator->generateCode();
        
        // Store in session
        $verifier->storeCode($code);
        
        // Output image
        $generator->createImage($code);
        
    } elseif ($method === 'POST') {
        // Verify CAPTCHA
        header('Content-Type: application/json');
        
        $verifier = new CaptchaVerifier();
        $input = $_POST['captcha'] ?? '';
        
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        if (empty($input)) {
            $response['message'] = 'Please enter the CAPTCHA code.';
        } elseif ($verifier->verify($input)) {
            $response['success'] = true;
            $response['message'] = 'CAPTCHA verified successfully.';
        } else {
            $response['message'] = 'Invalid or expired CAPTCHA code.';
        }
        
        echo json_encode($response);
    }
    
} catch (Exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ]);
    } else {
        // For GET requests, create a simple error image
        $image = imagecreate(200, 80);
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 255, 0, 0);
        imagestring($image, 3, 50, 30, 'Error', $textColor);
        
        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }
}