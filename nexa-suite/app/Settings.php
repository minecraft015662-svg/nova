<?php

declare(strict_types=1);

namespace Nexa;

final class Settings
{
    private const SECRET_KEYS = [
        'pterodactyl_application_key',
        'pterodactyl_client_key',
    ];

    private const DEFAULTS = [
        'site_name' => 'Nexa Suite',
        'license_product' => 'Nexa Theme',
        'support_email' => '',
        'suite_url' => '',
        'pterodactyl_url' => '',
        'pterodactyl_application_key' => '',
        'pterodactyl_client_key' => '',
        'pterodactyl_owner_id' => '',
        'pterodactyl_default_nest' => '',
        'pterodactyl_default_egg' => '',
        'pterodactyl_default_node' => '',
        'pterodactyl_verify_tls' => '1',
    ];

    private ?array $cache = null;

    public function __construct(
        private readonly Database $db,
        private readonly Crypto $crypto
    ) {
    }

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $values = self::DEFAULTS;
        foreach ($this->db->all('SELECT key, value FROM settings') as $row) {
            $key = (string) $row['key'];
            $value = (string) $row['value'];
            if (in_array($key, self::SECRET_KEYS, true)) {
                $value = $this->crypto->decrypt($value);
            }
            $values[$key] = $value;
        }

        return $this->cache = $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        return $values[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $value = (string) $value;
        if (in_array($key, self::SECRET_KEYS, true)) {
            $value = $this->crypto->encrypt($value);
        }

        $this->db->run(
            'INSERT INTO settings (key, value) VALUES (:key, :value)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value',
            [':key' => $key, ':value' => $value]
        );

        $this->cache = null;
    }

    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value);
        }
    }

    public function isSecret(string $key): bool
    {
        return in_array($key, self::SECRET_KEYS, true);
    }

    public function defaults(): array
    {
        return self::DEFAULTS;
    }

    public function secretKeys(): array
    {
        return self::SECRET_KEYS;
    }

    public function panelConfigured(): bool
    {
        return $this->get('pterodactyl_url') !== '' && $this->get('pterodactyl_application_key') !== '';
    }

    public function suiteUrl(): string
    {
        $url = (string) $this->get('suite_url');
        return $url !== '' ? rtrim($url, '/') : rtrim(app()->url('/'), '/');
    }
}
