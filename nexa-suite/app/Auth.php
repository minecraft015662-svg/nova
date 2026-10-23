<?php

declare(strict_types=1);

namespace Nexa;

final class Auth
{
    private bool $resolved = false;
    private ?array $user = null;

    public function __construct(private readonly Database $db)
    {
    }

    public function user(): ?array
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;
        $id = (int) ($_SESSION['nexa_uid'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        $this->user = $this->db->get('SELECT * FROM users WHERE id = :id', [':id' => $id]);
        if ($this->user === null) {
            unset($_SESSION['nexa_uid']);
        }

        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->db->get('SELECT * FROM users WHERE email = :email', [
            ':email' => strtolower(trim($email)),
        ]);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $this->db->update('users', [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ], 'id = :id', [':id' => $user['id']]);
        }

        $this->login((int) $user['id']);

        return true;
    }

    public function login(int $userId): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_regenerate_id(true);
        $_SESSION['nexa_uid'] = $userId;
        app()->csrf()->rotate();
        $this->resolved = false;
        $this->user = null;
    }

    public function logout(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        unset($_SESSION['nexa_uid']);
        $this->resolved = true;
        $this->user = null;

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
