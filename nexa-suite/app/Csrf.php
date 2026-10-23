<?php

declare(strict_types=1);

namespace Nexa;

final class Csrf
{
    public function token(): string
    {
        if (PHP_SAPI === 'cli') {
            return '';
        }

        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public function verify(?string $token): bool
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }

        $expected = (string) ($_SESSION['_csrf'] ?? '');

        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }

    public function rotate(): void
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
    }
}
