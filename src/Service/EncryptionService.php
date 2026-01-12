<?php

namespace App\Service;

use Random\RandomException;
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

    /**
     * @throws RandomException
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Chiffre un texte en clair
     *
     * @param string $plaintext Le texte en clair à chiffrer
     * @return string Le texte chiffré encodé en base64
     * @throws RandomException Si la génération de l'IV échoue
     */
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

    /**
     * Déchiffre un texte chiffré
     *
     * @param string $encrypted Le texte chiffré encodé en base64
     * @return string Le texte en clair
     * @throws RuntimeException Si le déchiffrement échoue ou si les données ont été modifiées
     */
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