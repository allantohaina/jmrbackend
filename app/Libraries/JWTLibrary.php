<?php

namespace App\Libraries;

class JWTLibrary
{
    private string $secretKey;
    private string $algorithm = 'HS256';
    private string $issuer;
    private string $audience;
    private int $leeway;

    public function __construct()
    {
        $this->secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key-change-this-in-production';
        $this->issuer = getenv('JWT_ISSUER') ?: 'jmr-textile';
        $this->audience = getenv('JWT_AUDIENCE') ?: 'jmr-textile-client';
        $this->leeway = (int) (getenv('JWT_LEEWAY') ?: 0);
    }

    /**
     * Generate JWT token
     */
    public function encode(array $payload, array $options = []): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];

        $now = time();
        $ttl = (int) ($options['ttl'] ?? 60 * 60 * 24);

        $payload['iss'] = $options['iss'] ?? $this->issuer;
        $payload['aud'] = $options['aud'] ?? $this->audience;
        $payload['jti'] = $payload['jti'] ?? $this->generateJti();
        $payload['nbf'] = $options['nbf'] ?? $now;

        // Add issued at and expiration time
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttl;

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $this->secretKey, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    /**
     * Decode and verify JWT token
     */
    public function decode(string $token, array $options = []): ?object
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Verify signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $this->secretKey, true);
        $signatureCheck = $this->base64UrlEncode($signature);

        if (!hash_equals($signatureEncoded, $signatureCheck)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($payloadEncoded));

        $now = time();

        // Check issuer/audience if provided
        $iss = $options['iss'] ?? $this->issuer;
        $aud = $options['aud'] ?? $this->audience;
        if (isset($payload->iss) && $payload->iss !== $iss) {
            return null;
        }
        if (isset($payload->aud) && $payload->aud !== $aud) {
            return null;
        }

        // Check not-before
        if (isset($payload->nbf) && $payload->nbf > ($now + $this->leeway)) {
            return null;
        }

        // Check expiration
        if (isset($payload->exp) && $payload->exp < ($now - $this->leeway)) {
            return null;
        }

        return $payload;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private function generateJti(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }
}
