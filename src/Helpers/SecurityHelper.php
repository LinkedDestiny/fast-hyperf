<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Helpers;

class SecurityHelper
{
    const AES_128_CBC = 'AES-128-CBC';

    public static function encrypt($message, $secret_key, $cipher): string
    {
        $iv = md5(time() . uniqid(), true);
        $raw = openssl_encrypt($message, $cipher, $secret_key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($raw);
    }

    public static function decrypt($message, $secret_key, $cipher): bool|string
    {
        $iv = md5(time() . uniqid(), true);
        return openssl_decrypt(base64_decode($message), $cipher, $secret_key, OPENSSL_RAW_DATA, $iv);
    }

    public static function signMD5WithRSA(string $private_key, $data): bool|string
    {
        if (empty($private_key) || empty($data)) {
            return false;
        }

        $pkeyId = openssl_get_privatekey($private_key);
        if (empty($pkeyId)) {
            return false;
        }
        $result = openssl_sign($data, $signature, $pkeyId, OPENSSL_ALGO_MD5);
        if (!$result) {
            return false;
        }
        return base64_encode($signature);
    }

    public static function verifyMD5WithRSA(string $public_key, $data, $signature): bool
    {
        if (empty($public_key) || empty($data) || empty($signature)) {
            return false;
        }

        $pkeyId = openssl_get_publickey($public_key);
        if (empty($pkeyId)) {
            return false;
        }

        $ret = openssl_verify($data, base64_decode($signature), $pkeyId, OPENSSL_ALGO_MD5);
        return $ret == 1;
    }

    public static function encryptRsa(string $message, string $publicKey): string
    {
        openssl_public_encrypt($message, $decrypted, openssl_pkey_get_public($publicKey), OPENSSL_PKCS1_PADDING);
        return base64_encode($decrypted);
    }

    public static function decryptRsa(string $message, string $secretKey): string
    {
        $data = base64_decode($message);
        $encrypted = '';
        openssl_private_decrypt($data, $encrypted, openssl_pkey_get_private($secretKey), OPENSSL_PKCS1_PADDING);
        return $encrypted;
    }
}