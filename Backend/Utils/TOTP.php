<?php

namespace Backend\Utils;

class TOTP
{
    /**
     * Generate a random secret key for TOTP
     * 
     * @param int $length Secret length (default: 32)
     * @return string Base32 encoded secret
     */
    public static function generateSecret($length = 32)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $secret;
    }

    /**
     * Generate QR code URL for TOTP setup
     * 
     * @param string $username User's username or email
     * @param string $secret Base32 encoded secret
     * @param string $issuer Application name
     * @return string QR code URL
     */
    public static function getQRCodeUrl($username, $secret, $issuer = 'YourApp')
    {
        $encodedUsername = urlencode($username);
        $encodedIssuer = urlencode($issuer);

        return "otpauth://totp/{$encodedIssuer}:{$encodedUsername}?secret={$secret}&issuer={$encodedIssuer}";
    }

    /**
     * Verify a TOTP code
     * 
     * @param string $secret Base32 encoded secret
     * @param string $code 6-digit code to verify
     * @param int $window Time window (default: 1 = ±30 seconds)
     * @return bool True if code is valid
     */
    public static function verify($secret, $code, $window = 1)
    {
        $currentTime = time();
        $timeStep = 30; // TOTP time step in seconds

        // Check current time slot and adjacent time slots
        for ($i = -$window; $i <= $window; $i++) {
            $testTime = $currentTime + ($i * $timeStep);
            $testCode = self::generateCode($secret, $testTime);

            if (hash_equals($testCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate TOTP code for a given time
     * 
     * @param string $secret Base32 encoded secret
     * @param int $time Unix timestamp (default: current time)
     * @return string 6-digit TOTP code
     */
    public static function generateCode($secret, $time = null)
    {
        if ($time === null) {
            $time = time();
        }

        $timeStep = 30;
        $counter = floor($time / $timeStep);

        // Convert secret from base32 to binary
        $binarySecret = self::base32Decode($secret);

        // Pack counter as 64-bit big-endian
        $counterBytes = pack('J', $counter);

        // Generate HMAC-SHA1 hash
        $hash = hash_hmac('sha1', $counterBytes, $binarySecret, true);

        // Dynamic truncation
        $offset = ord($hash[19]) & 0x0F;
        $truncatedHash = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        // Generate 6-digit code
        $code = $truncatedHash % 1000000;

        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode base32 string to binary
     * 
     * @param string $base32 Base32 encoded string
     * @return string Binary data
     */
    private static function base32Decode($base32)
    {
        $base32 = strtoupper($base32);
        $base32 = str_replace(['0', '1'], ['O', 'I'], $base32); // Handle common mistakes

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            $val = strpos($alphabet, $char);

            if ($val === false) {
                continue; // Skip invalid characters
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $output .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }

        return $output;
    }

    /**
     * Get current TOTP code for testing purposes
     * 
     * @param string $secret Base32 encoded secret
     * @return string Current 6-digit code
     */
    public static function getCurrentCode($secret)
    {
        return self::generateCode($secret);
    }

    /**
     * Get time remaining until next code generation
     * 
     * @return int Seconds remaining
     */
    public static function getTimeRemaining()
    {
        return 30 - (time() % 30);
    }

    /**
     * Validate secret format
     * 
     * @param string $secret Secret to validate
     * @return bool True if valid base32 secret
     */
    public static function isValidSecret($secret)
    {
        // Check if secret is valid base32
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);

        for ($i = 0; $i < strlen($secret); $i++) {
            if (strpos($alphabet, $secret[$i]) === false) {
                return false;
            }
        }

        return strlen($secret) >= 16; // Minimum recommended length
    }

    /**
     * Generate backup recovery codes
     * 
     * @param int $count Number of codes to generate (default 10)
     * @param int $length Length of each code segment (default 4)
     * @return array Array of backup codes
     */
    public static function generateBackupCodes($count = 10, $length = 4)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $segments = [];
            for ($j = 0; $j < 4; $j++) {
                $segment = '';
                for ($k = 0; $k < $length; $k++) {
                    $segment .= strtoupper(dechex(random_int(0, 15)));
                }
                $segments[] = $segment;
            }
            $codes[] = implode('-', $segments);
        }
        return $codes;
    }

    /**
     * Hash backup codes for storage
     * 
     * @param array $codes Array of plain backup codes
     * @return string JSON encoded array of hashed codes
     */
    public static function hashBackupCodes(array $codes)
    {
        $hashed = array_map(function ($code) {
            return [
                'hash' => password_hash($code, PASSWORD_ARGON2ID),
                'used' => false
            ];
        }, $codes);
        return json_encode($hashed);
    }

    /**
     * Verify a backup code
     * 
     * @param string $inputCode The code provided by user
     * @param string $storedCodesJson JSON string of hashed codes from database
     * @return bool|int Returns the index of matched code, or false if no match
     */
    public static function verifyBackupCode($inputCode, $storedCodesJson)
    {
        if (empty($storedCodesJson)) {
            return false;
        }

        $storedCodes = json_decode($storedCodesJson, true);
        if (!is_array($storedCodes)) {
            return false;
        }

        // Normalize input (remove spaces, convert to uppercase)
        $inputCode = strtoupper(str_replace([' ', '-'], '', $inputCode));

        foreach ($storedCodes as $index => $codeData) {
            if ($codeData['used']) {
                continue; // Skip already used codes
            }

            // Extract just the hash for comparison
            $hash = $codeData['hash'];

            // Reconstruct code format for verification (remove dashes from stored format)
            $normalizedStored = str_replace('-', '', $inputCode);

            if (password_verify($inputCode, $hash)) {
                return $index; // Return the index of the matched code
            }
        }

        return false;
    }

    /**
     * Mark a backup code as used
     * 
     * @param string $storedCodesJson JSON string of codes
     * @param int $index Index of code to mark as used
     * @return string Updated JSON string
     */
    public static function markBackupCodeUsed($storedCodesJson, $index)
    {
        $codes = json_decode($storedCodesJson, true);
        if (isset($codes[$index])) {
            $codes[$index]['used'] = true;
        }
        return json_encode($codes);
    }

    /**
     * Get count of remaining backup codes
     * 
     * @param string $storedCodesJson JSON string of codes
     * @return int Number of unused codes
     */
    public static function getRemainingBackupCodesCount($storedCodesJson)
    {
        if (empty($storedCodesJson)) {
            return 0;
        }

        $codes = json_decode($storedCodesJson, true);
        if (!is_array($codes)) {
            return 0;
        }

        return count(array_filter($codes, function ($code) {
            return !$code['used'];
        }));
    }
}
