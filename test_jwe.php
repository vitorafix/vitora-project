<?php

// Load Composer's autoloader
require __DIR__ . '/vendor/autoload.php';

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Encryption\JWEDecrypter; // For decryption test

echo "Starting JWE test with A256GCM...\n";

try {
    // --- 1. Load Keys ---
    // Make sure your keys exist in storage/keys/private.pem and storage/keys/public.pem
    $privateKeyPath = __DIR__ . '/storage/keys/private.pem';
    $publicKeyPath = __DIR__ . '/storage/keys/public.pem';

    if (!file_exists($privateKeyPath) || !file_exists($publicKeyPath)) {
        throw new Exception("Private or Public key file not found. Please ensure keys are in storage/keys/.");
    }

    $privateJwk = JWKFactory::createFromKey(file_get_contents($privateKeyPath));
    $publicJwk = JWKFactory::createFromKey(file_get_contents($publicKeyPath));

    echo "Keys loaded successfully.\n";

    // --- 2. Define Algorithms ---
    $keyEncryptionAlgorithms = new AlgorithmManager([
        new RSAOAEP(),
    ]);
    $contentEncryptionAlgorithms = new AlgorithmManager([
        new A256GCM(), // This is the algorithm we are testing
    ]);

    echo "Algorithm Managers initialized.\n";

    // --- 3. Initialize JWE Builder and Serializer ---
    $jweBuilder = new JWEBuilder($keyEncryptionAlgorithms, $contentEncryptionAlgorithms);
    $serializer = new CompactSerializer();
    $jweDecrypter = new JWEDecrypter($keyEncryptionAlgorithms, $contentEncryptionAlgorithms); // For decryption test

    echo "JWE Builder, Serializer, and Decrypter initialized.\n";

    // --- 4. Define Payload ---
    $payload = 'This is a secret message to be encrypted using A256GCM.';
    echo "Payload: '" . $payload . "'\n";

    // --- 5. Build JWE ---
    $jwe = $jweBuilder
        ->create()
        ->withPayload($payload)
        ->withSharedProtectedHeader([
            'alg' => 'RSA-OAEP', // Key encryption algorithm
            'enc' => 'A256GCM',  // Content encryption algorithm
            'kid' => 'my-app-rsa-key-1' // Key ID (optional, but good practice)
        ])
        ->addRecipient($publicJwk) // Encrypt for the public key
        ->build();

    echo "JWE built successfully.\n";

    // --- 6. Serialize JWE to Token String ---
    $token = $serializer->serialize($jwe, 0); // 0 for compact serialization
    echo "Generated JWE Token: " . $token . "\n";

    // --- 7. Decrypt JWE Token (Optional Test) ---
    echo "Attempting to decrypt the token...\n";
    $deserializedJwe = $serializer->unserialize($token);
    if (!$jweDecrypter->decrypt($deserializedJwe, $privateJwk)) {
        throw new Exception("Failed to decrypt JWE token during test.");
    }
    $decryptedPayload = $deserializedJwe->getPayload();
    echo "Decrypted Payload: '" . $decryptedPayload . "'\n";

    if ($payload === $decryptedPayload) {
        echo "Decryption successful and payload matches original. JWE with A256GCM works!\n";
    } else {
        echo "Decryption successful, but payload does NOT match original. There might be an issue.\n";
    }

} catch (Exception $e) {
    echo "Error during JWE test: " . $e->getMessage() . "\n";
    // If you need the full stack trace for debugging:
    // echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "JWE test finished.\n";
