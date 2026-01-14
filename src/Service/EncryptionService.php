<?php

namespace App\Service;

use RuntimeException;

class EncryptionService
{
    private const CIPHER_METHOD = 'aes-256-gcm';
    private string $encryptionKey;

    public function __construct(string $encryptionKey)
    {
        if (strlen($encryptionKey) !== 32) {
            throw new RuntimeException('Encryption key must be exactly 32 bytes');
        }
        $this->encryptionKey = $encryptionKey;
    }

    public static function generateKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER_METHOD,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $encrypted): string
    {
        $data = base64_decode($encrypted, true);

        if ($data === false) {
            throw new RuntimeException('Invalid encrypted data');
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER_METHOD,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed or data tampered');
        }

        return $plaintext;
    }
}