<?php

namespace App\Service;

class Keys
{
    public function generateKeyPair(): array
    {
        $res = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($res, $privKey);
        $pubKey = openssl_pkey_get_details($res)['key'];

        return ['private' => $privKey, 'public' => $pubKey];
    }

    public function encrypt(string $data, string $publicKey): string
    {
        openssl_public_encrypt($data, $encrypted, $publicKey);
        return base64_encode($encrypted);
    }

    public function decrypt(string $encrypted, string $privateKey): string
    {
        openssl_private_decrypt(base64_decode($encrypted), $decrypted, $privateKey);
        return $decrypted;
    }
}
