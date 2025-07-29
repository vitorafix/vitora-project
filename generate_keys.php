<?php
/**
 * RSA Key Generator with Enhanced Capabilities
 */

// Configuration for RSA key generation
$config = [
    "private_key_bits" => 2048, // Private key length in bits (commonly 2048 or 4096)
    "private_key_type" => OPENSSL_KEYTYPE_RSA, // Key type: RSA
    "encrypt_key" => false, // Do not encrypt the private key (can be set to true to add a passphrase)
];

/**
 * Check for OpenSSL extension availability
 */
if (!extension_loaded('openssl')) {
    die("خطا: OpenSSL extension نصب نیست.\n"); // Error: OpenSSL extension is not installed.
}

/**
 * Generate a new private and public key pair
 */
$res = openssl_pkey_new($config);
if ($res === false) {
    $errors = [];
    while (($error = openssl_error_string()) !== false) {
        $errors[] = $error;
    }
    die("خطا در تولید کلید: " . implode(", ", $errors) . "\n"); // Error in key generation:
}

/**
 * Export the private key
 */
$privateKeyExported = openssl_pkey_export($res, $privateKey);
if (!$privateKeyExported) {
    die("خطا در استخراج کلید خصوصی: " . openssl_error_string() . "\n"); // Error exporting private key:
}

/**
 * Get key details and extract the public key
 */
$keyDetails = openssl_pkey_get_details($res);
if ($keyDetails === false) {
    die("خطا در دریافت جزئیات کلید: " . openssl_error_string() . "\n"); // Error getting key details:
}
$publicKey = $keyDetails["key"];

/**
 * Check and create the storage/keys folder
 */
$keysPath = 'storage/keys';
if (!file_exists($keysPath)) {
    // 0700: Access only for the owner (read, write, execute)
    // true: Create nested directories if needed
    if (!mkdir($keysPath, 0700, true)) {
        die("خطا در ایجاد فولدر: $keysPath - " . error_get_last()['message'] . "\n"); // Error creating folder:
    }
    echo "فولدر $keysPath ایجاد شد.\n"; // Folder created.
}

/**
 * Save the private key to a file
 */
$privateKeyPath = $keysPath . '/private.pem';
if (file_put_contents($privateKeyPath, $privateKey) === false) {
    die("خطا در ذخیره کلید خصوصی در مسیر: $privateKeyPath\n"); // Error saving private key to path:
}

// Set permissions for the private key file (owner only)
chmod($privateKeyPath, 0600);

/**
 * Save the public key to a file
 */
$publicKeyPath = $keysPath . '/public.pem';
if (file_put_contents($publicKeyPath, $publicKey) === false) {
    die("خطا در ذخیره کلید عمومی در مسیر: $publicKeyPath\n"); // Error saving public key to path:
}

// Set appropriate permissions for the public key
chmod($publicKeyPath, 0644);

/**
 * Display success information
 */
echo "✅ کلیدها با موفقیت ساخته شدند:\n"; // Keys successfully generated:
echo "📁 مسیر: $keysPath\n"; // Path:
echo "🔐 کلید خصوصی: $privateKeyPath\n"; // Private Key:
echo "🔓 کلید عمومی: $publicKeyPath\n"; // Public Key:
echo "📏 طول کلید: {$keyDetails['bits']} بیت\n"; // Key Length: ... bits
echo "🔧 نوع کلید: {$keyDetails['type']}\n"; // Key Type:

/**
 * Quick test of the keys
 */
$testData = "تست رمزگذاری"; // Encryption test
if (openssl_public_encrypt($testData, $encrypted, $publicKey)) {
    if (openssl_private_decrypt($encrypted, $decrypted, $privateKey)) {
        if ($testData === $decrypted) {
            echo "✅ تست کلیدها موفقیت‌آمیز بود.\n"; // Key test was successful.
        } else {
            echo "⚠️  هشدار: کلیدها درست کار نمی‌کنند.\n"; // Warning: Keys are not working correctly.
        }
    }
}

/**
 * Free up key resources
 */
openssl_free_key($res);

/**
 * Display usage guide
 */
echo "\n📖 راهنمای استفاده:\n"; // Usage Guide:
echo "برای خواندن کلید خصوصی: \$privateKey = file_get_contents('$privateKeyPath');\n"; // To read private key:
echo "برای خواندن کلید عمومی: \$publicKey = file_get_contents('$publicKeyPath');\n"; // To read public key:
echo "برای رمزگذاری: openssl_public_encrypt(\$data, \$encrypted, \$publicKey);\n"; // For encryption:
echo "برای رمزگشایی: openssl_private_decrypt(\$encrypted, \$decrypted, \$privateKey);\n"; // For decryption:
?>
