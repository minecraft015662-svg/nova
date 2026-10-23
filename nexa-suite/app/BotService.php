<?php

declare(strict_types=1);

namespace Nexa;

final class BotService
{
    public function __construct(
        private readonly Database $db,
        private readonly Pterodactyl $pterodactyl,
        private readonly Settings $settings
    ) {
    }

    public function create(array $input, ?int $createdBy = null): array
    {
        if (!$this->pterodactyl->configured()) {
            throw new \RuntimeException('Connect your Pterodactyl panel in Settings before hosting bots.');
        }

        $name = trim((string) ($input['name'] ?? ''));
        if (strlen($name) < 2 || strlen($name) > 60) {
            throw new \RuntimeException('Bot name must be between 2 and 60 characters.');
        }
        if (!preg_match('/^[A-Za-z0-9 ._\-]+$/', $name)) {
            throw new \RuntimeException('Bot name may only contain letters, numbers, spaces, dots, dashes and underscores.');
        }

        $memory = $this->clamp((int) ($input['memory'] ?? 1024), 256, 131072);
        $disk = $this->clamp((int) ($input['disk'] ?? 4096), 256, 524288);
        $cpu = $this->clamp((int) ($input['cpu'] ?? 100), 10, 3200);

        $nodeId = (int) ($input['node_id'] ?? 0) ?: (int) $this->settings->get('pterodactyl_default_node');
        $nestId = (int) ($input['nest_id'] ?? 0) ?: (int) $this->settings->get('pterodactyl_default_nest');
        $eggId = (int) ($input['egg_id'] ?? 0) ?: (int) $this->settings->get('pterodactyl_default_egg');
        $ownerId = (int) ($input['owner_id'] ?? 0) ?: (int) $this->settings->get('pterodactyl_owner_id');

        if ($nodeId < 1) {
            throw new \RuntimeException('Choose a node or set a default node in Settings.');
        }
        if ($eggId < 1) {
            throw new \RuntimeException('Choose an egg or set a default egg in Settings.');
        }
        if ($ownerId < 1) {
            throw new \RuntimeException('Set the Pterodactyl owner user ID in Settings.');
        }

        if ($nestId < 1) {
            $nestId = $this->findNestForEgg($eggId);
        }

        $egg = $this->pterodactyl->egg($nestId, $eggId);
        $dockerImage = $this->dockerImage($egg);
        if ($dockerImage === '') {
            throw new \RuntimeException('The selected egg has no Docker image configured.');
        }

        $allocationId = (int) ($input['allocation_id'] ?? 0);
        if ($allocationId < 1) {
            $allocationId = (int) $this->pterodactyl->freeAllocationId($nodeId);
        }
        if ($allocationId < 1) {
            throw new \RuntimeException('No free allocation on this node. Create a free port in Pterodactyl first.');
        }

        $environment = $this->environmentDefaults($egg);
        if (isset($input['environment']) && is_array($input['environment'])) {
            foreach ($input['environment'] as $key => $value) {
                if (is_string($key) && is_scalar($value)) {
                    $environment[$key] = (string) $value;
                }
            }
        }

        $server = $this->pterodactyl->createServer([
            'name' => $name,
            'owner_id' => $ownerId,
            'egg_id' => $eggId,
            'node_id' => $nodeId,
            'memory' => $memory,
            'disk' => $disk,
            'cpu' => $cpu,
            'allocation_id' => $allocationId,
            'docker_image' => $dockerImage,
            'startup' => (string) ($egg['startup'] ?? ''),
            'environment' => $environment,
        ]);

        try {
            $id = $this->db->insert('bots', [
                'name' => $name,
                'server_id' => (int) $server['id'],
                'server_uuid' => (string) ($server['uuid'] ?? ''),
                'server_identifier' => (string) ($server['identifier'] ?? ''),
                'node_id' => $nodeId,
                'nest_id' => $nestId,
                'egg_id' => $eggId,
                'memory' => $memory,
                'disk' => $disk,
                'cpu' => $cpu,
                'owner_id' => $ownerId,
                'owner_email' => trim((string) ($input['owner_email'] ?? '')),
                'status' => (string) ($server['status'] ?? 'installing'),
                'created_by' => $createdBy,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $exception) {
            try {
                $this->pterodactyl->deleteServer((int) $server['id']);
            } catch (\Throwable) {
            }
            throw $exception;
        }

        return $this->find($id) ?? [];
    }

    public function list(): array
    {
        return $this->db->all('SELECT * FROM bots ORDER BY id DESC');
    }

    public function find(int $id): ?array
    {
        return $this->db->get('SELECT * FROM bots WHERE id = :id', [':id' => $id]);
    }

    public function refresh(array $bots): array
    {
        if (!$this->pterodactyl->clientConfigured()) {
            return $bots;
        }

        $checked = 0;
        foreach ($bots as &$bot) {
            if ($checked >= 8) {
                break;
            }
            if (($bot['server_uuid'] ?? '') === '') {
                continue;
            }

            $checked++;
            try {
                $resources = $this->pterodactyl->resources((string) $bot['server_uuid']);
                $status = (string) ($resources['current_state'] ?? $bot['status']);
                if ($status !== '') {
                    $bot['status'] = $status;
                    $this->db->update('bots', [
                        'status' => $status,
                        'status_checked_at' => date('Y-m-d H:i:s'),
                    ], 'id = :id', [':id' => $bot['id']]);
                }
            } catch (\Throwable $exception) {
                app()->log('Bot status refresh failed for bot #' . $bot['id'] . ': ' . $exception->getMessage());
            }
        }
        unset($bot);

        return $bots;
    }

    public function power(int $id, string $signal): array
    {
        $signals = ['start', 'stop', 'restart', 'kill'];
        if (!in_array($signal, $signals, true)) {
            throw new \RuntimeException('Invalid power signal.');
        }

        $bot = $this->find($id);
        if ($bot === null) {
            throw new \RuntimeException('Bot not found.');
        }
        if (!$this->pterodactyl->clientConfigured()) {
            throw new \RuntimeException('Add a Pterodactyl Client API key in Settings to send power actions.');
        }

        $this->pterodactyl->power((string) $bot['server_uuid'], $signal);
        $status = match ($signal) {
            'start' => 'starting',
            'stop' => 'stopping',
            'restart' => 'restarting',
            'kill' => 'offline',
            default => $bot['status'],
        };

        $this->db->update('bots', ['status' => $status, 'status_checked_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $id]);
        $bot['status'] = $status;

        return $bot;
    }

    public function delete(int $id): void
    {
        $bot = $this->find($id);
        if ($bot === null) {
            throw new \RuntimeException('Bot not found.');
        }

        try {
            $this->pterodactyl->deleteServer((int) $bot['server_id']);
        } catch (PterodactylException $exception) {
            if ($exception->status !== 404) {
                throw new \RuntimeException('Pterodactyl refused to delete the server: ' . $exception->getMessage());
            }
        }

        $this->db->run('DELETE FROM bots WHERE id = :id', [':id' => $id]);
    }

    public function stats(): array
    {
        return [
            'total' => (int) $this->db->value('SELECT COUNT(*) FROM bots'),
            'running' => (int) $this->db->value("SELECT COUNT(*) FROM bots WHERE status IN ('running','online')"),
        ];
    }

    public function apiData(array $bot): array
    {
        return [
            'id' => (int) $bot['id'],
            'name' => (string) $bot['name'],
            'server_id' => (int) $bot['server_id'],
            'server_uuid' => (string) $bot['server_uuid'],
            'identifier' => (string) $bot['server_identifier'],
            'node_id' => (int) $bot['node_id'],
            'nest_id' => (int) $bot['nest_id'],
            'egg_id' => (int) $bot['egg_id'],
            'limits' => [
                'memory_mb' => (int) $bot['memory'],
                'disk_mb' => (int) $bot['disk'],
                'cpu_percent' => (int) $bot['cpu'],
            ],
            'status' => (string) $bot['status'],
            'created_at' => (string) $bot['created_at'],
        ];
    }

    private function findNestForEgg(int $eggId): int
    {
        foreach ($this->pterodactyl->nests() as $nest) {
            $nestId = (int) ($nest['id'] ?? 0);
            if ($nestId < 1) {
                continue;
            }
            foreach ($this->pterodactyl->eggs($nestId) as $egg) {
                if ((int) ($egg['id'] ?? 0) === $eggId) {
                    return $nestId;
                }
            }
        }

        throw new \RuntimeException('Could not find nest for egg #' . $eggId . '. Set a default nest in Settings.');
    }

    private function dockerImage(array $egg): string
    {
        $images = $egg['docker_images'] ?? [];
        if (is_array($images) && $images !== []) {
            $image = reset($images);
            return is_string($image) ? $image : '';
        }

        return '';
    }

    private function environmentDefaults(array $egg): array
    {
        $variables = $egg['relationships']['variables']['data'] ?? [];
        $environment = [];

        if (is_array($variables)) {
            foreach ($variables as $variable) {
                $attributes = $variable['attributes'] ?? $variable;
                $key = (string) ($attributes['env_variable'] ?? '');
                if ($key !== '') {
                    $environment[$key] = (string) ($attributes['default_value'] ?? '');
                }
            }
        }

        return $environment;
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
