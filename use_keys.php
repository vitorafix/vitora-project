<?php
/**
 * Script to demonstrate RSA encryption and decryption using generated keys.
 */

// Define paths to the generated keys.
// Make sure these paths are correct relative to where this script is executed.
// If you are in a Laravel project, you might use storage_path('keys/private.pem').
// Corrected paths: If use_keys.php is in C:\xampp\htdocs\myshop
// and keys are in C:\xampp\htdocs\myshop\storage\keys,
// then the path from __DIR__ (C:\xampp\htdocs\myshop) should be 'storage/keys'.
$privateKeyPath = __DIR__ . '/storage/keys/private.pem';
$publicKeyPath = __DIR__ . '/storage/keys/public.pem';

/**
 * Function to handle errors and exit.
 * @param string $message The error message to display.
 */
function handleErrorAndExit($message) {
    echo "خطا: " . $message . "\n"; // Error:
    exit(1);
}

/**
 * Load the private key from the file system.
 */
$privateKey = file_get_contents($privateKeyPath);
if ($privateKey === false) {
    handleErrorAndExit("کلید خصوصی در مسیر '$privateKeyPath' یافت نشد یا قابل خواندن نیست."); // Private key not found or readable at path.
}

/**
 * Load the public key from the file system.
 */
$publicKey = file_get_contents($publicKeyPath);
if ($publicKey === false) {
    handleErrorAndExit("کلید عمومی در مسیر '$publicKeyPath' یافت نشد یا قابل خواندن نیست."); // Public key not found or readable at path.
}

// The original message to be encrypted.
$originalMessage = "این یک پیام بسیار مهم و محرمانه است که باید با RSA رمزنگاری شود."; // This is a very important and confidential message that needs to be encrypted with RSA.

echo "پیام اصلی: " . $originalMessage . "\n\n"; // Original Message:

$encryptedData = '';
$decryptedData = '';

/**
 * Encrypt the message using the public key.
 * This ensures that only the holder of the corresponding private key can decrypt it.
 */
echo "در حال رمزنگاری پیام با کلید عمومی...\n"; // Encrypting message with public key...
if (openssl_public_encrypt($originalMessage, $encryptedData, $publicKey)) {
    echo "✅ پیام با موفقیت رمزنگاری شد.\n"; // Message successfully encrypted.
    echo "پیام رمزنگاری شده (Base64): " . base64_encode($encryptedData) . "\n\n"; // Encrypted Message (Base64):
} else {
    handleErrorAndExit("خطا در رمزنگاری پیام: " . openssl_error_string()); // Error encrypting message:
}

/**
 * Decrypt the message using the private key.
 * Only the holder of the private key can perform this operation.
 */
echo "در حال رمزگشایی پیام با کلید خصوصی...\n"; // Decrypting message with private key...
if (openssl_private_decrypt($encryptedData, $decryptedData, $privateKey)) {
    echo "✅ پیام با موفقیت رمزگشایی شد.\n"; // Message successfully decrypted.
    echo "پیام رمزگشایی شده: " . $decryptedData . "\n\n"; // Decrypted Message:
} else {
    handleErrorAndExit("خطا در رمزگشایی پیام: " . openssl_error_string()); // Error decrypting message:
}

/**
 * Verify that the decrypted message matches the original message.
 */
if ($originalMessage === $decryptedData) {
    echo "✅ تأیید: پیام اصلی و رمزگشایی شده مطابقت دارند. (عملیات موفقیت‌آمیز بود)\n"; // Verification: Original and decrypted messages match. (Operation successful)
} else {
    echo "⚠️ هشدار: پیام اصلی و رمزگشایی شده مطابقت ندارند! (مشکلی رخ داده است)\n"; // Warning: Original and decrypted messages do not match! (Something went wrong)
}

?>
