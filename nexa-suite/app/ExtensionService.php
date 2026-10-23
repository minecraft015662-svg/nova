<?php

declare(strict_types=1);

namespace Nexa;

final class ExtensionService
{
    private ?array $cache = null;

    public function __construct(private readonly string $file)
    {
    }

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (!is_file($this->file)) {
            $this->save($this->defaults());
        }

        $decoded = json_decode((string) file_get_contents($this->file), true);
        $entries = is_array($decoded) ? $decoded : [];
        $byId = [];
        foreach ($entries as $entry) {
            if (is_array($entry) && isset($entry['id'])) {
                $byId[(string) $entry['id']] = $entry;
            }
        }

        foreach ($this->defaults() as $default) {
            if (!isset($byId[$default['id']])) {
                $byId[$default['id']] = $default;
            }
        }

        return $this->cache = array_values($byId);
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $entry) {
            if ((string) $entry['id'] === $id) {
                return $entry;
            }
        }

        return null;
    }

    public function toggle(string $id, bool $enabled): bool
    {
        $entries = $this->all();
        $found = false;
        foreach ($entries as &$entry) {
            if ((string) $entry['id'] === $id) {
                $entry['status'] = $enabled ? 'enabled' : 'disabled';
                $found = true;
                break;
            }
        }
        unset($entry);

        if ($found) {
            $this->save($entries);
        }

        return $found;
    }

    public function add(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || strlen($name) > 80) {
            throw new \InvalidArgumentException('Extension name is required (max 80 characters).');
        }

        $entries = $this->all();
        $baseId = slugify($name);
        $id = $baseId;
        $counter = 2;
        $existing = array_column($entries, 'id');
        while (in_array($id, $existing, true)) {
            $id = $baseId . '-' . $counter;
            $counter++;
        }

        $sourceUrl = trim((string) ($input['source_url'] ?? ''));
        if ($sourceUrl !== '' && !filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('The source URL must be a valid URL.');
        }

        $entry = [
            'id' => $id,
            'name' => $name,
            'description' => substr(trim((string) ($input['description'] ?? '')), 0, 500),
            'category' => substr(trim((string) ($input['category'] ?? 'Custom')), 0, 40) ?: 'Custom',
            'version' => substr(trim((string) ($input['version'] ?? '1.0.0')), 0, 30) ?: '1.0.0',
            'status' => 'enabled',
            'bundled' => false,
            'source_url' => $sourceUrl,
            'install' => substr(trim((string) ($input['install'] ?? '')), 0, 300),
            'requires' => substr(trim((string) ($input['requires'] ?? '')), 0, 120),
            'added_at' => date('Y-m-d H:i:s'),
        ];

        $entries[] = $entry;
        $this->save($entries);

        return $entry;
    }

    public function stats(): array
    {
        $all = $this->all();

        return [
            'total' => count($all),
            'enabled' => count(array_filter($all, static fn (array $entry): bool => ($entry['status'] ?? '') === 'enabled')),
        ];
    }

    public function defaults(): array
    {
        $now = date('Y-m-d H:i:s');

        return [
            [
                'id' => 'nexa-theme',
                'name' => 'Nexa Theme',
                'description' => 'The Nexa glass panel skin for Pterodactyl 1.x: custom dashboard, server fleet grid, quick switcher, privacy mode, light and dark modes and the admin-only Nexa Editor.',
                'category' => 'Theme',
                'version' => '1.0.0',
                'status' => 'enabled',
                'bundled' => true,
                'source_url' => '',
                'install' => 'Pterodactyl Admin → Extensions → Import nexa-theme.blueprint',
                'requires' => 'Blueprint beta-2026-06',
                'added_at' => $now,
            ],
            [
                'id' => 'nexa-editor',
                'name' => 'Nexa Editor',
                'description' => 'Admin-only live theme studio inside the panel. Edits branding, logos, presets, accent, glass blur, sidebar position, backgrounds (image or GIF URL), effects, support links, Nexa Suite URL and more.',
                'category' => 'Theme',
                'version' => '1.0.0',
                'status' => 'enabled',
                'bundled' => true,
                'source_url' => '',
                'install' => 'Bundled with Nexa Theme',
                'requires' => 'Nexa Theme',
                'added_at' => $now,
            ],
            [
                'id' => 'nexa-suite',
                'name' => 'Nexa Suite',
                'description' => 'This self-hosted control deck: license issuing, API keys, extension catalog, Pterodactyl bot hosting and a command line interface. Runs on port 4411.',
                'category' => 'Suite',
                'version' => '1.0.0',
                'status' => 'enabled',
                'bundled' => true,
                'source_url' => '',
                'install' => 'php bin/nexa install && php bin/nexa serve',
                'requires' => 'PHP 8.1+',
                'added_at' => $now,
            ],
            [
                'id' => 'blueprint',
                'name' => 'Blueprint framework',
                'description' => 'Extension loader used by Nexa Theme. Install the Blueprint build that targets your Pterodactyl version before uploading the theme.',
                'category' => 'Framework',
                'version' => 'beta-2026-06',
                'status' => 'enabled',
                'bundled' => false,
                'source_url' => '',
                'install' => 'Pterodactyl Admin → Extensions → Blueprint loader',
                'requires' => 'Pterodactyl 1.x',
                'added_at' => $now,
            ],
            [
                'id' => 'discord-auth',
                'name' => 'Discord Auth',
                'description' => 'Catalog slot for a Discord OAuth login extension. Register any compatible Blueprint extension bundle here, then enable it after installing on the panel.',
                'category' => 'Community',
                'version' => '—',
                'status' => 'disabled',
                'bundled' => false,
                'source_url' => '',
                'install' => 'Admin → Extensions → Import → upload .blueprint',
                'requires' => 'Blueprint',
                'added_at' => $now,
            ],
            [
                'id' => 'status-page',
                'name' => 'Status Page',
                'description' => 'Catalog slot for a public node and service status extension. Track panel health and show incident history to your community.',
                'category' => 'Community',
                'version' => '—',
                'status' => 'disabled',
                'bundled' => false,
                'source_url' => '',
                'install' => 'Admin → Extensions → Import → upload .blueprint',
                'requires' => 'Blueprint',
                'added_at' => $now,
            ],
            [
                'id' => 'ticket-center',
                'name' => 'Ticket Center',
                'description' => 'Catalog slot for a support ticket extension. Let customers open tickets and keep every reply inside the panel.',
                'category' => 'Community',
                'version' => '—',
                'status' => 'disabled',
                'bundled' => false,
                'source_url' => '',
                'install' => 'Admin → Extensions → Import → upload .blueprint',
                'requires' => 'Blueprint',
                'added_at' => $now,
            ],
            [
                'id' => 'subdomain-manager',
                'name' => 'Subdomain Manager',
                'description' => 'Catalog slot for an automatic subdomain extension. Connect your DNS provider and hand out clean addresses to new servers.',
                'category' => 'Community',
                'version' => '—',
                'status' => 'disabled',
                'bundled' => false,
                'source_url' => '',
                'install' => 'Admin → Extensions → Import → upload .blueprint',
                'requires' => 'Blueprint',
                'added_at' => $now,
            ],
            [
                'id' => 'backup-vault',
                'name' => 'Backup Vault',
                'description' => 'Catalog slot for off-node backup storage. Archive server backups to S3 compatible object storage on a schedule.',
                'category' => 'Community',
                'version' => '—',
                'status' => 'disabled',
                'bundled' => false,
                'source_url' => '',
                'install' => 'Admin → Extensions → Import → upload .blueprint',
                'requires' => 'Blueprint',
                'added_at' => $now,
            ],
        ];
    }

    private function save(array $entries): void
    {
        $directory = dirname($this->file);
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $json = json_encode(array_values($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false || file_put_contents($this->file, $json, LOCK_EX) === false) {
            throw new \RuntimeException('Could not write the extension catalog.');
        }

        $this->cache = array_values($entries);
    }
}
