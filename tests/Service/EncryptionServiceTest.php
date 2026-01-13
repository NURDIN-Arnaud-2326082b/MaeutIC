<?php

/**
 * Tests unitaires pour EncryptionService
 *
 * Teste les méthodes du service de chiffrement :
 * - Génération de clés de chiffrement
 * - Chiffrement de données
 * - Déchiffrement de données
 * - Validation de l'intégrité des données
 */

namespace App\Tests\Service;

use App\Service\EncryptionService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EncryptionServiceTest extends TestCase
{
    private string $validKey;
    private EncryptionService $service;

    /**
     * Teste la génération de clé de chiffrement
     */
    public function testGenerateKey(): void
    {
        $key = EncryptionService::generateKey();

        $this->assertIsString($key);
        $this->assertEquals(32, strlen($key)); // 16 bytes * 2 (hex)
    }

    /**
     * Teste le constructeur avec une clé valide
     */
    public function test__construct(): void
    {
        $service = new EncryptionService($this->validKey);

        $this->assertInstanceOf(EncryptionService::class, $service);
    }

    /**
     * Teste que le constructeur lève une exception avec une clé invalide
     */
    public function testConstructWithInvalidKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Encryption key must be exactly 32 bytes');

        new EncryptionService('invalid_short_key');
    }

    /**
     * Teste le chiffrement d'un texte
     */
    public function testEncrypt(): void
    {
        $plaintext = 'Secret message';

        $encrypted = $this->service->encrypt($plaintext);

        $this->assertIsString($encrypted);
        $this->assertNotEquals($plaintext, $encrypted);
        // Vérifie que c'est bien encodé en base64
        $this->assertNotFalse(base64_decode($encrypted, true));
    }

    /**
     * Teste le déchiffrement d'un texte
     */
    public function testDecrypt(): void
    {
        $plaintext = 'Secret message';

        // Chiffrer puis déchiffrer
        $encrypted = $this->service->encrypt($plaintext);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * Teste le cycle complet de chiffrement/déchiffrement
     */
    public function testEncryptDecryptCycle(): void
    {
        $originalText = 'This is a test message with special chars: éàç@#$%';

        $encrypted = $this->service->encrypt($originalText);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertEquals($originalText, $decrypted);
    }

    /**
     * Teste que le déchiffrement lève une exception avec des données invalides
     */
    public function testDecryptWithInvalidData(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->decrypt('invalid_encrypted_data');
    }

    /**
     * Teste que chaque chiffrement produit un résultat différent (à cause de l'IV aléatoire)
     */
    public function testEncryptProducesDifferentOutputs(): void
    {
        $plaintext = 'Same message';

        $encrypted1 = $this->service->encrypt($plaintext);
        $encrypted2 = $this->service->encrypt($plaintext);

        // Les résultats doivent être différents à cause de l'IV aléatoire
        $this->assertNotEquals($encrypted1, $encrypted2);

        // Mais les deux doivent pouvoir être déchiffrés correctement
        $this->assertEquals($plaintext, $this->service->decrypt($encrypted1));
        $this->assertEquals($plaintext, $this->service->decrypt($encrypted2));
    }

    protected function setUp(): void
    {
        // Génère une clé valide de 32 bytes
        $this->validKey = str_repeat('a', 32);
        $this->service = new EncryptionService($this->validKey);
    }
}
