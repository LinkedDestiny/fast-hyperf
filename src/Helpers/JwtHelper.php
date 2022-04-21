<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Helpers;

use Firebase\JWT\JWT;

class JwtHelper
{
    /**
     * @param array $message
     * @param string $key
     * @param int $expire
     * @return string
     */
    public static function generateToken(array $message, string $key, int $expire): string
    {
        // generate token
        return JWT::encode([
            'message' => $message,
            'exp' => $message['expireAt'] ?? time() + $expire,
        ], $key);
    }

    /**
     * @param $token
     * @param string $key
     * @return array
     */
    public static function verifyToken($token,  string $key = ''): array
    {
        $message = JWT::decode($token, $key, ['HS256']);
        $message = (array)($message);
        return (array)($message['message']);
    }
}