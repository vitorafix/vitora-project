<?php

// app/Helpers/SecurityHelper.php

if (!function_exists('hashForCache')) {
    /**
     * Hashes data ONLY for cache keys and logs - NOT for database or SMS.
     * Use this when you need to hide sensitive data in cache/logs.
     *
     * @param string $data The data to hash (e.g., phone number, IP address).
     * @param string $type The context type (e.g., 'otp', 'rate_limit', 'cache').
     * @return string The SHA256 hash.
     */
    function hashForCache(string $data, string $type = 'cache'): string
    {
        // Cleans the data (e.g., removes non-numeric characters from phone numbers, or ensures valid IP format)
        // For phone numbers, keeps only digits. For IPs, keeps digits and dots.
        // This is a basic cleaning; stronger validation might be needed depending on the context.
        $cleanData = preg_replace('/[^0-9.]/', '', $data);
        return hash('sha256', $cleanData . $type . config('app.key'));
    }
}

if (!function_exists('maskForLog')) {
    /**
     * Masks sensitive data for logging (shows partial info).
     *
     * @param string $data The data to mask.
     * @param string $type Type of data: 'phone' or 'ip'.
     * @return string Masked data.
     */
    function maskForLog(string $data, string $type = 'phone'): string
    {
        if ($type === 'phone') {
            $clean = preg_replace('/[^0-9]/', '', $data);
            // Masks the middle part of the phone number
            if (strlen($clean) > 8) { // Ensure enough characters to mask
                return substr($clean, 0, 4) . '***' . substr($clean, -4);
            }
            return $clean; // Return as is if too short
        }
        
        if ($type === 'ip') {
            // Check for IPv6
            if (strpos($data, ':') !== false) {
                $parts = explode(':', $data);
                // For IPv6, mask the middle parts, keep first and last
                if (count($parts) > 2) {
                    return $parts[0] . ':' . '***:' . end($parts);
                }
                return $data; // Return as is if not enough parts to mask meaningfully
            }
            
            // Handle IPv4
            $parts = explode('.', $data);
            // Masks the second and third octets of the IPv4 address
            if (count($parts) === 4) {
                return $parts[0] . '.***.***.' . $parts[3];
            }
            return $data; // Return as is if not a valid IPv4 format
        }
        
        return $data; // Return original data if type is not recognized
    }
}

if (!function_exists('cleanMobileNumber')) {
    /**
     * Converts Persian/Arabic digits in a string to English digits and removes non-numeric characters.
     * This method converts Persian/Arabic digits to English and removes non-numeric characters.
     *
     * @param string $mobile The mobile number string.
     * @return string The cleaned mobile number.
     */
    function cleanMobileNumber(string $mobile): string
    {
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $englishDigits = ['0','1','2','3','4','5','6','7','8','9'];
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

        $mobile = str_replace($persianDigits, $englishDigits, $mobile);
        $mobile = str_replace($arabicDigits, $englishDigits, $mobile);

        // Remove extra (non-numeric) characters
        return preg_replace('/[^0-9]/', '', $mobile);
    }
}

if (!function_exists('cleanOtp')) {
    /**
     * Removes non-numeric characters from the OTP string and converts Persian/Arabic digits to English.
     * This method removes non-numeric characters from the OTP string and converts Persian/Arabic digits to English.
     *
     * @param string $otp The OTP string.
     * @return string The cleaned OTP.
     */
    function cleanOtp(string $otp): string
    {
        $normalizedOtp = str_replace([' ', '-'], '', $otp); // Remove spaces and unnecessary characters
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $englishDigits = ['0','1','2','3','4','5','6','7','8','9'];
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

        $normalizedOtp = str_replace($persianDigits, $englishDigits, $normalizedOtp);
        $normalizedOtp = str_replace($arabicDigits, $englishDigits, $normalizedOtp);

        return preg_replace('/[^0-9]/', '', $normalizedOtp); // Ensure any non-numeric characters are removed after conversion
    }
}

if (!function_exists('convertPersianDigitsToEnglish')) {
    /**
     * Converts Persian/Arabic digits in a string to English digits.
     * This method converts Persian/Arabic digits in a string to English digits.
     *
     * @param string $input The string containing digits.
     * @return string The string with converted digits.
     */
    function convertPersianDigitsToEnglish(string $input): string
    {
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $englishDigits = ['0','1','2','3','4','5','6','7','8','9'];
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

        $input = str_replace($persianDigits, $englishDigits, $input);
        $input = str_replace($arabicDigits, $englishDigits, $input);

        return $input;
    }
}
