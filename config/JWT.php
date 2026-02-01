<?php

class JWT {
    public static function encode($payload, $secret) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode($payload);
        
        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payload);

        $signature = self::base64UrlEncode(hash_hmac('sha256', "$base64Header.$base64Payload", $secret, true));

        return "$base64Header.$base64Payload.$signature";
    }

    private static function base64UrlEncode($data) {
        return strtr(base64_encode($data), '+/', '-_');
    }
}
