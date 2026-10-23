<?php

declare(strict_types=1);

namespace Nexa;

final class ApiKeyService
{
    public const SCOPES = [
        'licenses:read',
        'licenses:write',
        'bots:read',
        'bots:write',
        'extensions:read',
    ];

    public function __construct(private readonly Database $db)
    {
    }

    public function create(string $name, array $scopes, ?int $createdBy = null): array
    {
        $name = trim($name);
        if ($name === '') {
            $name = 'API key';
        }
        if (strlen($name) > 80) {
            $name = substr($name, 0, 80);
        }

        $scopes = array_values(array_intersect($scopes, self::SCOPES));
        if ($scopes === [] || in_array('*', $scopes, true)) {
            $scopes = self::SCOPES;
        }

        $key = 'nexa_' . bin2hex(random_bytes(24));
        $id = $this->db->insert('api_keys', [
            'name' => $name,
            'key_hash' => hash('sha256', $key),
            'key_prefix' => substr($key, 0, 13) . '…',
            'scopes' => json_encode($scopes),
            'status' => 'active',
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['key' => $key, 'api_key' => $this->find($id)];
    }

    public function find(int $id): ?array
    {
        return $this->db->get('SELECT * FROM api_keys WHERE id = :id', [':id' => $id]);
    }

    public function list(): array
    {
        return $this->db->all('SELECT * FROM api_keys ORDER BY id DESC');
    }

    public function authenticate(?string $plainKey): ?array
    {
        if ($plainKey === null || $plainKey === '') {
            return null;
        }

        $key = $this->db->get('SELECT * FROM api_keys WHERE key_hash = :hash AND status = :status', [
            ':hash' => hash('sha256', trim($plainKey)),
            ':status' => 'active',
        ]);

        if ($key === null) {
            return null;
        }

        $this->db->update('api_keys', ['last_used_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $key['id']]);

        return $key;
    }

    public function allows(array $apiKey, string $scope): bool
    {
        $scopes = $this->scopes($apiKey);
        if (in_array('*', $scopes, true) || in_array($scope, $scopes, true)) {
            return true;
        }

        if (str_ends_with($scope, ':read')) {
            $write = str_replace(':read', ':write', $scope);
            return in_array($write, $scopes, true);
        }

        return false;
    }

    public function scopes(array $apiKey): array
    {
        $scopes = json_decode((string) ($apiKey['scopes'] ?? '[]'), true);

        return is_array($scopes) ? $scopes : [];
    }

    public function revoke(int $id): void
    {
        $this->db->update('api_keys', ['status' => 'revoked'], 'id = :id', [':id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->run('DELETE FROM api_keys WHERE id = :id', [':id' => $id]);
    }

    public function stats(): array
    {
        return [
            'total' => (int) $this->db->value('SELECT COUNT(*) FROM api_keys'),
            'active' => (int) $this->db->value("SELECT COUNT(*) FROM api_keys WHERE status = 'active'"),
        ];
    }
}
