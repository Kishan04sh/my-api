<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token {
    private static function getSecret() {
        $config = require __DIR__ . '/../config/constants.php';
        return $config['JWT_SECRET'];
    }

    public static function create($payload, $expiry = 604800) {
        $issuedAt = time();
        $expireAt = $issuedAt + $expiry;
        $payload['iat'] = $issuedAt;
        $payload['exp'] = $expireAt;

        return JWT::encode($payload, self::getSecret(), 'HS256');
    }

    public static function verify($token) {
        try {
            return JWT::decode($token, new Key(self::getSecret(), 'HS256'));
        } catch (Exception $e) {
            return false;
        }
    }



}



