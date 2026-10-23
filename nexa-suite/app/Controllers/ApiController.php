<?php

declare(strict_types=1);

namespace Nexa\Controllers;

use Nexa\App;
use Nexa\PterodactylException;

final class ApiController
{
    public static function health(): string
    {
        return json_response([
            'ok' => true,
            'data' => [
                'service' => 'Nexa Suite',
                'version' => NEXA_VERSION,
                'time' => date(DATE_ATOM),
                'installed' => app()->installed(),
            ],
        ]);
    }

    public static function validateLicense(): string
    {
        $app = app();
        $app->rateLimiter()->enforce('license-validate:' . ip_hash(), 30, 60);

        $data = self::body();
        $license = trim((string) ($data['license'] ?? $data['key'] ?? ''));
        if ($license === '') {
            abort(422, 'The "license" field is required.', 'validation_error');
        }

        $domain = isset($data['domain']) ? (string) $data['domain'] : null;
        $instance = isset($data['instance']) ? (string) $data['instance'] : null;
        $result = $app->licenses()->validate($license, $domain, $instance);

        return json_response(['ok' => true, 'data' => $result]);
    }

    public static function activateLicense(): string
    {
        $app = app();
        $app->rateLimiter()->enforce('license-activate:' . ip_hash(), 20, 60);

        $data = self::body();
        $license = trim((string) ($data['license'] ?? $data['key'] ?? ''));
        $domain = trim((string) ($data['domain'] ?? ''));
        $instance = trim((string) ($data['instance'] ?? ''));

        if ($license === '' || $domain === '') {
            abort(422, 'Both "license" and "domain" are required.', 'validation_error');
        }

        $result = $app->licenses()->activate($license, $domain, $instance, ip_hash());
        if (!$result['activated']) {
            $status = match ($result['reason']) {
                'not_found' => 404,
                'activation_limit' => 409,
                'invalid_domain' => 422,
                default => 422,
            };
            abort($status, self::activationMessage((string) $result['reason']), (string) $result['reason']);
        }

        return json_response(['ok' => true, 'data' => $result]);
    }

    public static function deactivateLicense(): string
    {
        $app = app();
        $app->rateLimiter()->enforce('license-deactivate:' . ip_hash(), 20, 60);

        $data = self::body();
        $license = trim((string) ($data['license'] ?? $data['key'] ?? ''));
        $domain = trim((string) ($data['domain'] ?? ''));
        if ($license === '' || $domain === '') {
            abort(422, 'Both "license" and "domain" are required.', 'validation_error');
        }

        $removed = $app->licenses()->deactivate($license, $domain, trim((string) ($data['instance'] ?? '')));
        if (!$removed) {
            abort(404, 'License not found.', 'not_found');
        }

        return json_response(['ok' => true, 'data' => ['deactivated' => true]]);
    }

    public static function listLicenses(): string
    {
        $licenses = app()->licenses()->list(true, (string) query('q'));

        return json_response([
            'ok' => true,
            'data' => array_map([self::class, 'licenseData'], $licenses),
            'meta' => ['count' => count($licenses)],
        ]);
    }

    public static function createLicense(): string
    {
        $app = app();
        $data = self::body();
        $plan = strtolower(trim((string) ($data['plan'] ?? 'pro')));
        if ($plan === '') {
            $plan = 'pro';
        }

        $days = 365;
        if (array_key_exists('days', $data)) {
            if ($data['days'] === null || $data['days'] === '' || (int) $data['days'] === 0) {
                $days = null;
            } else {
                $days = max(1, min(36500, (int) $data['days']));
            }
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email !== '' && !is_email($email)) {
            abort(422, 'The "email" field is not valid.', 'validation_error');
        }

        $activations = max(1, min(1000, (int) ($data['activations'] ?? $data['max_activations'] ?? 3)));
        $result = $app->licenses()->create($plan, $days, $activations, $email, (string) ($data['note'] ?? ''), (int) (app()->currentApiKey()['created_by'] ?? 0) ?: null);

        return json_response([
            'ok' => true,
            'data' => [
                'license' => self::licenseData($result['license']),
                'key' => $result['key'],
                'notice' => 'Store this key now. Only its hash is saved.',
            ],
        ], 201);
    }

    public static function revokeLicense(string $id): string
    {
        $app = app();
        $license = $app->licenses()->find((int) $id);
        if ($license === null) {
            abort(404, 'License not found.', 'not_found');
        }

        $app->licenses()->setStatus((int) $id, 'revoked');

        return json_response(['ok' => true, 'data' => ['id' => (int) $id, 'status' => 'revoked']]);
    }

    public static function listExtensions(): string
    {
        $extensions = app()->extensions()->all();

        return json_response([
            'ok' => true,
            'data' => array_map(static fn (array $entry): array => [
                'id' => $entry['id'],
                'name' => $entry['name'],
                'description' => $entry['description'],
                'category' => $entry['category'],
                'version' => $entry['version'],
                'status' => $entry['status'],
                'bundled' => (bool) $entry['bundled'],
            ], $extensions),
            'meta' => ['count' => count($extensions)],
        ]);
    }

    public static function listBots(): string
    {
        $app = app();
        $bots = $app->bots()->list();
        if (query('refresh') === '1' && $app->pterodactyl()->clientConfigured()) {
            $bots = $app->bots()->refresh($bots);
        }

        return json_response([
            'ok' => true,
            'data' => array_map(static fn (array $bot): array => $app->bots()->apiData($bot), $bots),
            'meta' => ['count' => count($bots)],
        ]);
    }

    public static function createBot(): string
    {
        $data = self::body();
        try {
            $bot = app()->bots()->create($data, (int) (app()->currentApiKey()['created_by'] ?? 0) ?: null);
        } catch (PterodactylException $exception) {
            app()->log('API bot creation rejected by Pterodactyl: ' . $exception->getMessage());
            abort(422, $exception->getMessage(), 'panel_error');
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage(), 'provision_failed');
        } catch (\Throwable $exception) {
            app()->log('API bot creation failed: ' . $exception->getMessage());
            abort(500, 'The bot could not be created. Check the Nexa Suite logs.', 'server_error');
        }

        return json_response(['ok' => true, 'data' => app()->bots()->apiData($bot)], 201);
    }

    public static function showBot(string $id): string
    {
        $bot = app()->bots()->find((int) $id);
        if ($bot === null) {
            abort(404, 'Bot not found.', 'not_found');
        }

        return json_response(['ok' => true, 'data' => app()->bots()->apiData($bot)]);
    }

    public static function powerBot(string $id): string
    {
        $data = self::body();
        $signal = strtolower(trim((string) ($data['signal'] ?? '')));
        if (!in_array($signal, ['start', 'stop', 'restart', 'kill'], true)) {
            abort(422, 'The "signal" field must be start, stop, restart or kill.', 'validation_error');
        }

        try {
            $bot = app()->bots()->power((int) $id, $signal);
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage(), 'power_failed');
        }

        return json_response(['ok' => true, 'data' => app()->bots()->apiData($bot)]);
    }

    public static function deleteBot(string $id): string
    {
        try {
            app()->bots()->delete((int) $id);
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage(), 'delete_failed');
        }

        return json_response(['ok' => true, 'data' => ['deleted' => true, 'id' => (int) $id]]);
    }

    private static function body(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));

        if (str_contains($contentType, 'application/json')) {
            $raw = (string) file_get_contents('php://input');
            if (strlen($raw) > 131072) {
                abort(413, 'The JSON body is too large.', 'payload_too_large');
            }
            if (trim($raw) === '') {
                return [];
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                abort(400, 'The request body is not valid JSON.', 'invalid_json');
            }

            return $decoded;
        }

        return $_POST;
    }

    private static function licenseData(array $license): array
    {
        return [
            'id' => (int) $license['id'],
            'plan' => $license['plan'],
            'key_prefix' => $license['key_prefix'],
            'status' => $license['status'],
            'email' => $license['email'],
            'note' => $license['note'],
            'max_activations' => (int) $license['max_activations'],
            'activations' => (int) ($license['activations'] ?? 0),
            'expires_at' => $license['expires_at'],
            'last_checked_at' => $license['last_checked_at'],
            'created_at' => $license['created_at'],
        ];
    }

    private static function activationMessage(string $reason): string
    {
        return match ($reason) {
            'not_found' => 'This license key does not exist.',
            'revoked' => 'This license has been revoked.',
            'expired' => 'This license has expired.',
            'activation_limit' => 'This license reached its activation limit. Deactivate another install first.',
            'invalid_domain' => 'The domain is not valid.',
            default => 'The license could not be activated.',
        };
    }
}
