<?php

declare(strict_types=1);

namespace Nexa;

final class Pterodactyl
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function configured(): bool
    {
        return $this->baseUrl() !== '' && (string) $this->settings->get('pterodactyl_application_key') !== '';
    }

    public function clientConfigured(): bool
    {
        return $this->baseUrl() !== '' && (string) $this->settings->get('pterodactyl_client_key') !== '';
    }

    public function baseUrl(): string
    {
        $url = trim((string) $this->settings->get('pterodactyl_url'));
        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $url = rtrim($url, '/');
        $url = (string) preg_replace('#/api$#i', '', $url);

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    public function nodes(): array
    {
        $response = $this->application('GET', '/api/application/nodes?per_page=100');

        return $this->attributesList($response);
    }

    public function nests(): array
    {
        $response = $this->application('GET', '/api/application/nests?per_page=100');

        return $this->attributesList($response);
    }

    public function eggs(int $nestId): array
    {
        $response = $this->application('GET', '/api/application/nests/' . $nestId . '/eggs?per_page=100');

        return $this->attributesList($response);
    }

    public function egg(int $nestId, int $eggId): array
    {
        $response = $this->application('GET', '/api/application/nests/' . $nestId . '/eggs/' . $eggId . '?include=variables');
        $attributes = $response['attributes'] ?? null;

        if (!is_array($attributes)) {
            throw new PterodactylException('The selected egg could not be loaded from Pterodactyl.');
        }

        return $attributes;
    }

    public function allocations(int $nodeId): array
    {
        $items = [];
        for ($page = 1; $page <= 10; $page++) {
            $response = $this->application('GET', '/api/application/nodes/' . $nodeId . '/allocations?per_page=100&page=' . $page);
            $batch = $this->attributesList($response);
            $items = array_merge($items, $batch);
            $pageCount = (int) ($response['meta']['pagination']['total_pages'] ?? 1);
            if ($page >= $pageCount) {
                break;
            }
        }

        return $items;
    }

    public function freeAllocationId(int $nodeId): ?int
    {
        foreach ($this->allocations($nodeId) as $allocation) {
            if ((int) ($allocation['assigned'] ?? 1) === 0) {
                return (int) $allocation['id'];
            }
        }

        return null;
    }

    public function createServer(array $spec): array
    {
        $payload = [
            'name' => (string) $spec['name'],
            'user' => (int) $spec['owner_id'],
            'egg' => (int) $spec['egg_id'],
            'docker' => (string) $spec['docker_image'],
            'startup' => (string) $spec['startup'],
            'environment' => (object) ($spec['environment'] ?? []),
            'limits' => [
                'memory' => (int) $spec['memory'],
                'swap' => 0,
                'disk' => (int) $spec['disk'],
                'io' => 500,
                'cpu' => (int) $spec['cpu'],
            ],
            'feature_limits' => [
                'databases' => 0,
                'allocations' => 1,
                'backups' => 0,
            ],
            'allocation' => [
                'default' => (int) $spec['allocation_id'],
            ],
            'start_on_completion' => true,
        ];

        $response = $this->application('POST', '/api/application/servers', $payload);
        $attributes = $response['attributes'] ?? null;

        if (!is_array($attributes) || empty($attributes['id'])) {
            throw new PterodactylException('Pterodactyl created the server but returned an unexpected response.');
        }

        return $attributes;
    }

    public function deleteServer(int $serverId): void
    {
        $this->application('DELETE', '/api/application/servers/' . $serverId . '?force=true');
    }

    public function power(string $uuid, string $signal): void
    {
        $this->client('POST', '/api/client/servers/' . rawurlencode($uuid) . '/power', ['signal' => $signal]);
    }

    public function resources(string $uuid): array
    {
        $response = $this->client('GET', '/api/client/servers/' . rawurlencode($uuid) . '/resources');

        return is_array($response['attributes'] ?? null) ? $response['attributes'] : [];
    }

    public function testApplication(): array
    {
        $response = $this->application('GET', '/api/application/nodes?per_page=1');
        $total = (int) ($response['meta']['pagination']['total'] ?? count($this->attributesList($response)));

        return ['ok' => true, 'message' => 'Application API connected. ' . $total . ' node(s) visible.'];
    }

    public function testClient(): array
    {
        $response = $this->client('GET', '/api/client');
        $attributes = $response['attributes'] ?? [];

        return [
            'ok' => true,
            'message' => 'Client API connected as ' . (string) ($attributes['username'] ?? 'panel user') . '.',
        ];
    }

    private function application(string $method, string $path, ?array $body = null): array
    {
        $key = (string) $this->settings->get('pterodactyl_application_key');
        if ($this->baseUrl() === '' || $key === '') {
            throw new PterodactylException('Add your Pterodactyl panel URL and Application API key in Settings first.');
        }

        return $this->request($method, $path, $body, $key);
    }

    private function client(string $method, string $path, ?array $body = null): array
    {
        $key = (string) $this->settings->get('pterodactyl_client_key');
        if ($this->baseUrl() === '' || $key === '') {
            throw new PterodactylException('Add a Pterodactyl Client API key in Settings to control power state.');
        }

        return $this->request($method, $path, $body, $key);
    }

    private function request(string $method, string $path, ?array $body, string $apiKey): array
    {
        $url = $this->baseUrl() . $path;
        $verify = (string) $this->settings->get('pterodactyl_verify_tls', '1') !== '0';

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_USERAGENT => 'Nexa-Suite/' . NEXA_VERSION,
            ];

            if ($body !== null) {
                $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES);
            }

            curl_setopt_array($handle, $options);
            $raw = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $error = curl_error($handle);
            curl_close($handle);

            if ($raw === false) {
                throw new PterodactylException('Could not reach the panel: ' . ($error !== '' ? $error : 'connection failed.'));
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", [
                        'Authorization: Bearer ' . $apiKey,
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'User-Agent: Nexa-Suite/' . NEXA_VERSION,
                    ]),
                    'content' => $body !== null ? json_encode($body, JSON_UNESCAPED_SLASHES) : '',
                    'timeout' => 25,
                    'ignore_errors' => true,
                ],
                'ssl' => ['verify_peer' => $verify, 'verify_peer_name' => $verify],
            ]);

            $raw = @file_get_contents($url, false, $context);
            $status = 0;
            foreach ($http_response_header ?? [] as $header) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches)) {
                    $status = (int) $matches[1];
                }
            }

            if ($raw === false) {
                throw new PterodactylException('Could not reach the panel. Check the panel URL and TLS settings.');
            }
        }

        $decoded = json_decode((string) $raw, true);
        $json = is_array($decoded) ? $decoded : [];

        if ($status < 200 || $status >= 300) {
            $detail = $json['errors'][0]['detail'] ?? $json['error'] ?? null;
            if (!is_string($detail) || $detail === '') {
                $detail = 'Pterodactyl API returned HTTP ' . $status . '.';
            }
            throw new PterodactylException($detail, $status, $json);
        }

        return $json;
    }

    private function attributesList(array $response): array
    {
        $list = $response['data'] ?? [];
        if (!is_array($list)) {
            return [];
        }

        $attributes = [];
        foreach ($list as $item) {
            if (is_array($item) && isset($item['attributes']) && is_array($item['attributes'])) {
                $attributes[] = $item['attributes'];
            }
        }

        return $attributes;
    }
}
