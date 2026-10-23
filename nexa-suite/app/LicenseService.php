<?php

declare(strict_types=1);

namespace Nexa;

final class LicenseService
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private readonly Database $db)
    {
    }

    public function create(
        string $plan,
        ?int $days,
        int $maxActivations,
        string $email,
        string $note,
        ?int $createdBy = null
    ): array {
        $plan = strtolower(trim($plan));
        if ($plan === '' || !preg_match('/^[a-z0-9_\-]{1,32}$/', $plan)) {
            $plan = 'custom';
        }

        $maxActivations = max(1, min(1000, $maxActivations));
        if ($days !== null) {
            $days = max(1, min(36500, $days));
        }

        $email = trim($email);
        $note = trim($note);
        $note = strlen($note) > 500 ? substr($note, 0, 500) : $note;

        $expiresAt = $days !== null ? date('Y-m-d H:i:s', time() + ($days * 86400)) : null;

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $key = $this->generateKey();
            $hash = self::hashKey($key);

            if ($this->db->get('SELECT id FROM licenses WHERE key_hash = :hash', [':hash' => $hash]) !== null) {
                continue;
            }

            $id = $this->db->insert('licenses', [
                'key_hash' => $hash,
                'key_prefix' => substr($key, 0, 12) . '…',
                'plan' => $plan,
                'email' => $email,
                'note' => $note,
                'max_activations' => $maxActivations,
                'status' => 'active',
                'expires_at' => $expiresAt,
                'created_by' => $createdBy,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $license = $this->find($id);
            if ($license !== null) {
                return ['key' => $key, 'license' => $license];
            }
        }

        throw new \RuntimeException('Could not generate a unique license key. Try again.');
    }

    public function generateKey(): string
    {
        $groups = [];
        for ($group = 0; $group < 4; $group++) {
            $chunk = '';
            for ($i = 0; $i < 5; $i++) {
                $chunk .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $groups[] = $chunk;
        }

        return 'NEXA-' . implode('-', $groups);
    }

    public static function normalizeKey(string $key): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', trim($key)));
    }

    public static function hashKey(string $key): string
    {
        return hash('sha256', self::normalizeKey($key));
    }

    public function findByKey(string $key): ?array
    {
        return $this->db->get('SELECT * FROM licenses WHERE key_hash = :hash', [
            ':hash' => self::hashKey($key),
        ]);
    }

    public function find(int $id): ?array
    {
        $license = $this->db->get('SELECT * FROM licenses WHERE id = :id', [':id' => $id]);
        if ($license === null) {
            return null;
        }

        $license['activations'] = (int) $this->db->value(
            'SELECT COUNT(*) FROM license_activations WHERE license_id = :id',
            [':id' => $id]
        );

        return $license;
    }

    public function list(bool $includeRevoked = true, string $search = ''): array
    {
        $sql = 'SELECT l.*, (SELECT COUNT(*) FROM license_activations a WHERE a.license_id = l.id) AS activations
                FROM licenses l';
        $params = [];
        $conditions = [];

        if (!$includeRevoked) {
            $conditions[] = "l.status = 'active'";
        }

        if ($search !== '') {
            $conditions[] = '(l.email LIKE :search OR l.key_prefix LIKE :search OR l.note LIKE :search OR l.plan LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY l.id DESC LIMIT 500';

        return $this->db->all($sql, $params);
    }

    public function validate(string $key, ?string $domain = null, ?string $instance = null): array
    {
        $license = $this->findByKey($key);

        if ($license === null) {
            return ['valid' => false, 'reason' => 'not_found', 'license' => null, 'activation' => null];
        }

        $license = $this->find((int) $license['id']) ?? $license;

        $this->db->update('licenses', ['last_checked_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $license['id']]);

        if ($license['status'] !== 'active') {
            return ['valid' => false, 'reason' => 'revoked', 'license' => $this->publicData($license), 'activation' => null];
        }

        if ($license['expires_at'] !== null && strtotime((string) $license['expires_at']) < time()) {
            return ['valid' => false, 'reason' => 'expired', 'license' => $this->publicData($license), 'activation' => null];
        }

        $activation = null;
        $activated = null;
        if ($domain !== null && $domain !== '') {
            $normalized = self::normalizeDomain($domain);
            $instanceValue = $instance !== null ? trim($instance) : '';
            $activationRow = $this->db->get(
                'SELECT * FROM license_activations WHERE license_id = :license AND domain = :domain AND instance_id = :instance',
                [':license' => $license['id'], ':domain' => $normalized, ':instance' => $instanceValue]
            );
            $activated = $activationRow !== null;
            if ($activationRow !== null) {
                $this->db->update(
                    'license_activations',
                    ['last_seen_at' => date('Y-m-d H:i:s')],
                    'id = :id',
                    [':id' => $activationRow['id']]
                );
                $activation = [
                    'domain' => $activationRow['domain'],
                    'instance' => $activationRow['instance_id'],
                    'activated_at' => $activationRow['created_at'],
                    'last_seen_at' => $activationRow['last_seen_at'],
                ];
            }
        }

        return [
            'valid' => true,
            'reason' => 'active',
            'activated' => $activated,
            'license' => $this->publicData($license),
            'activation' => $activation,
        ];
    }

    public function activate(string $key, string $domain, string $instance = '', string $ipHash = ''): array
    {
        $domain = self::normalizeDomain($domain);
        $instance = trim($instance);
        if ($domain === '' || strlen($domain) > 190) {
            return ['activated' => false, 'reason' => 'invalid_domain', 'license' => null];
        }

        $check = $this->validate($key);
        if (!$check['valid']) {
            return ['activated' => false, 'reason' => $check['reason'], 'license' => $check['license']];
        }

        $license = $this->findByKey($key);
        if ($license === null) {
            return ['activated' => false, 'reason' => 'not_found', 'license' => null];
        }

        $existing = $this->db->get(
            'SELECT * FROM license_activations WHERE license_id = :license AND domain = :domain AND instance_id = :instance',
            [':license' => $license['id'], ':domain' => $domain, ':instance' => $instance]
        );

        if ($existing !== null) {
            $this->db->update(
                'license_activations',
                ['last_seen_at' => date('Y-m-d H:i:s')],
                'id = :id',
                [':id' => $existing['id']]
            );

            return ['activated' => true, 'reason' => 'already_active', 'license' => $this->publicData($license)];
        }

        $count = (int) $this->db->value(
            'SELECT COUNT(*) FROM license_activations WHERE license_id = :license',
            [':license' => $license['id']]
        );

        if ($count >= (int) $license['max_activations']) {
            return ['activated' => false, 'reason' => 'activation_limit', 'license' => $this->publicData($license)];
        }

        $this->db->insert('license_activations', [
            'license_id' => $license['id'],
            'domain' => $domain,
            'instance_id' => $instance,
            'ip_hash' => $ipHash,
            'created_at' => date('Y-m-d H:i:s'),
            'last_seen_at' => date('Y-m-d H:i:s'),
        ]);

        return ['activated' => true, 'reason' => 'activated', 'license' => $this->publicData($this->find((int) $license['id']))];
    }

    public function deactivate(string $key, string $domain, string $instance = ''): bool
    {
        $license = $this->findByKey($key);
        if ($license === null) {
            return false;
        }

        $this->db->run(
            'DELETE FROM license_activations WHERE license_id = :license AND domain = :domain AND instance_id = :instance',
            [
                ':license' => $license['id'],
                ':domain' => self::normalizeDomain($domain),
                ':instance' => trim($instance),
            ]
        );

        return true;
    }

    public function activations(int $licenseId): array
    {
        return $this->db->all(
            'SELECT id, domain, instance_id, created_at, last_seen_at FROM license_activations WHERE license_id = :id ORDER BY id DESC',
            [':id' => $licenseId]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['active', 'revoked'], true)) {
            throw new \InvalidArgumentException('Unknown license status.');
        }

        $this->db->update('licenses', ['status' => $status], 'id = :id', [':id' => $id]);
    }

    public function regenerate(int $id): ?string
    {
        $license = $this->find($id);
        if ($license === null) {
            return null;
        }

        $key = $this->generateKey();
        $this->db->update('licenses', [
            'key_hash' => self::hashKey($key),
            'key_prefix' => substr($key, 0, 12) . '…',
        ], 'id = :id', [':id' => $id]);

        return $key;
    }

    public function delete(int $id): void
    {
        $this->db->run('DELETE FROM licenses WHERE id = :id', [':id' => $id]);
    }

    public function stats(): array
    {
        $now = date('Y-m-d H:i:s');

        return [
            'total' => (int) $this->db->value('SELECT COUNT(*) FROM licenses'),
            'active' => (int) $this->db->value(
                "SELECT COUNT(*) FROM licenses WHERE status = 'active' AND (expires_at IS NULL OR expires_at > :now)",
                [':now' => $now]
            ),
            'revoked' => (int) $this->db->value("SELECT COUNT(*) FROM licenses WHERE status = 'revoked'"),
            'expired' => (int) $this->db->value(
                "SELECT COUNT(*) FROM licenses WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at <= :now",
                [':now' => $now]
            ),
            'activations' => (int) $this->db->value('SELECT COUNT(*) FROM license_activations'),
        ];
    }

    public function publicData(array $license): array
    {
        $expires = $license['expires_at'] ?? null;

        return [
            'plan' => (string) $license['plan'],
            'plan_name' => license_plan((string) $license['plan'])['name'],
            'status' => (string) $license['status'],
            'key_prefix' => (string) $license['key_prefix'],
            'expires_at' => $expires,
            'days_left' => days_left($expires),
            'max_activations' => (int) $license['max_activations'],
            'activations' => isset($license['activations']) ? (int) $license['activations'] : null,
        ];
    }

    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        if ($domain === '') {
            return '';
        }

        if (!preg_match('#^[a-z][a-z0-9+.\-]*://#i', $domain)) {
            $domain = 'https://' . $domain;
        }

        $host = parse_url($domain, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return '';
        }

        $host = strtolower($host);
        if (!preg_match('/^[a-z0-9.\-\[\]:]{1,190}$/', $host)) {
            return '';
        }

        return $host;
    }
}
