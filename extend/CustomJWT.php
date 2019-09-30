<?php

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class CustomJWT extends JWT
{
    public static function decode($jwt, $key, array $allowed_algs = array())
    {
        try {
            $payload = parent::decode($jwt, $key, $allowed_algs);
        } catch (ExpiredException $e) {
            $tks = explode('.', $jwt);
            list($headb64, $bodyb64, $cryptob64) = $tks;
            $payload = static::jsonDecode(static::urlsafeB64Decode($bodyb64));
        }

        return $payload;
    }
}