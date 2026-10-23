<?php

declare(strict_types=1);

namespace Nexa;

final class Crypto
{
    private string $key;

    public function __construct(string $key)
    {
        $this->key = hash('sha256', $key, true);
    }

    public function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }

        if (!function_exists('openssl_encrypt')) {
            throw new \RuntimeException('The OpenSSL PHP extension is required to store secrets securely.');
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($cipher === false) {
            throw new \RuntimeException('Could not encrypt the secret.');
        }

        return 'v1:' . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($cipher);
    }

    public function decrypt(string $payload): string
    {
        if ($payload === '') {
            return '';
        }

        if (!str_starts_with($payload, 'v1:')) {
            return $payload;
        }

        $parts = explode(':', $payload);
        if (count($parts) !== 4) {
            return '';
        }

        $iv = base64_decode($parts[1], true);
        $tag = base64_decode($parts[2], true);
        $cipher = base64_decode($parts[3], true);

        if ($iv === false || $tag === false || $cipher === false) {
            return '';
        }

        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        return $plain === false ? '' : $plain;
    }
}
