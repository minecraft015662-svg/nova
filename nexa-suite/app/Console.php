<?php

declare(strict_types=1);

namespace Nexa;

final class Console
{
    private const FLAG_OPTIONS = [
        'json',
        'force',
        'help',
        'no-interaction',
        'lifetime',
    ];

    private array $args = [];
    private array $options = [];
    private bool $json = false;
    private bool $color = false;

    public function run(array $argv): int
    {
        array_shift($argv);
        $this->parse($argv);
        $this->json = (bool) $this->option('json', false);
        $this->color = $this->supportsColor() && !$this->json;

        $command = array_shift($this->args);
        if ($command === null || $command === 'help' || $this->option('help', false)) {
            $this->printHelp();
            return 0;
        }

        try {
            return match ($command) {
                'install' => $this->install(),
                'serve' => $this->serve(),
                'user:create' => $this->userCreate(),
                'user:list' => $this->userList(),
                'license:create' => $this->licenseCreate(),
                'license:list' => $this->licenseList(),
                'license:revoke' => $this->licenseStatus('revoked'),
                'license:restore' => $this->licenseStatus('active'),
                'license:regenerate' => $this->licenseRegenerate(),
                'license:validate' => $this->licenseValidate(),
                'license:delete' => $this->licenseDelete(),
                'key:create' => $this->keyCreate(),
                'key:list' => $this->keyList(),
                'key:revoke' => $this->keyRevoke(),
                'extension:list' => $this->extensionList(),
                'extension:enable' => $this->extensionToggle(true),
                'extension:disable' => $this->extensionToggle(false),
                'extension:add' => $this->extensionAdd(),
                'bot:create' => $this->botCreate(),
                'bot:list' => $this->botList(),
                'bot:start' => $this->botPower('start'),
                'bot:stop' => $this->botPower('stop'),
                'bot:restart' => $this->botPower('restart'),
                'bot:kill' => $this->botPower('kill'),
                'bot:delete' => $this->botDelete(),
                'settings:show' => $this->settingsShow(),
                'settings:set' => $this->settingsSet(),
                default => $this->unknownCommand($command),
            };
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());
            return 1;
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());
            return 1;
        } catch (\Throwable $exception) {
            $this->error('Unexpected error: ' . $exception->getMessage());
            if (app()->isDebug()) {
                $this->line($exception->getFile() . ':' . $exception->getLine());
            }
            return 1;
        }
    }

    /* ------------------------------------------------------------- install */

    private function install(): int
    {
        $app = app();
        $this->banner();

        $site = trim((string) $this->option('site', ''));
        $url = trim((string) $this->option('url', ''));
        $name = trim((string) $this->option('name', 'Admin'));
        $email = strtolower(trim((string) $this->option('email', '')));
        $password = (string) $this->option('password', '');
        $adminCount = (int) $app->db()->value('SELECT COUNT(*) FROM users');

        if ($this->interactive()) {
            if ($site === '') {
                $site = $this->prompt('Site name', (string) $app->settings()->get('site_name', 'Nexa Suite'));
            }
            if ($url === '') {
                $url = $this->prompt('Public suite URL (optional, e.g. https://panel.example.com:4411)', (string) $app->settings()->get('suite_url', ''));
            }
            if ($adminCount === 0 && $email === '') {
                $name = $this->prompt('Admin name', 'Admin');
                $email = strtolower($this->prompt('Admin email'));
                while (true) {
                    $password = $this->secretPrompt('Admin password (min 10 characters)');
                    if (strlen($password) < 10) {
                        $this->warn('Use at least 10 characters.');
                        continue;
                    }
                    $confirm = $this->secretPrompt('Confirm password');
                    if ($password !== $confirm) {
                        $this->warn('Passwords did not match. Try again.');
                        continue;
                    }
                    break;
                }
            }
        }

        if ($name === '') {
            $name = 'Admin';
        }
        if (strlen($name) > 80) {
            throw new \InvalidArgumentException('The admin name may not exceed 80 characters.');
        }
        if ($site !== '' && strlen($site) > 80) {
            throw new \InvalidArgumentException('The site name may not exceed 80 characters.');
        }
        if ($url !== '') {
            $url = rtrim($this->validateUrl($url, 'suite URL'), '/');
        }

        $createdAdmin = false;
        if ($adminCount === 0) {
            if ($email === '' || $password === '') {
                throw new \InvalidArgumentException('Create the first admin with --email and --password (or run install in an interactive terminal).');
            }
            if (!is_email($email)) {
                throw new \InvalidArgumentException('Enter a valid admin email address.');
            }
            if (strlen($password) < 10) {
                throw new \InvalidArgumentException('Use an admin password with at least 10 characters.');
            }

            $app->db()->insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => $app->auth()->hashPassword($password),
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $createdAdmin = true;
        }

        $siteName = $site !== '' ? $site : (string) $app->settings()->get('site_name', 'Nexa Suite');
        $app->settings()->set('site_name', $siteName);
        if ($url !== '') {
            $app->settings()->set('suite_url', $url);
        }
        $app->settings()->get('license_product', 'Nexa Theme');

        if ($url !== '' || !is_file(base_path('config/config.php'))) {
            $this->writeConfig($app, $url);
        }

        if ($createdAdmin) {
            $this->success('Admin account created for ' . $email . '.');
        } elseif ($adminCount > 0) {
            $this->info('Console already has ' . $adminCount . ' admin account' . ($adminCount === 1 ? '' : 's') . '. Use user:create to add another.');
        }

        $this->success('Nexa Suite is installed. Database: ' . $app->storagePath((string) $app->config['database']));
        $this->info('Start it with: php bin/nexa serve --port=4411');
        $this->info('Panel URL: ' . $app->url('/login'));

        return 0;
    }

    private function writeConfig(App $app, string $url): void
    {
        $file = base_path('config/config.php');
        $config = $app->config;
        if ($url !== '') {
            $config['url'] = $url;
        }
        $config['url'] = rtrim((string) ($config['url'] ?? ''), '/');
        $config['port'] = (int) ($config['port'] ?? 4411);

        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
        if (file_put_contents($file, $contents, LOCK_EX) === false) {
            throw new \RuntimeException('Could not write config/config.php. Make the config directory writable.');
        }
        @chmod($file, 0640);

        $app->config = $config;
    }

    /* --------------------------------------------------------------- serve */

    private function serve(): int
    {
        $host = (string) $this->option('host', '127.0.0.1');
        $port = (int) $this->option('port', (string) app()->config['port']);
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('The port must be between 1 and 65535.');
        }
        if (!preg_match('/^[A-Za-z0-9.\-\[\]:]+$/', $host)) {
            throw new \InvalidArgumentException('The host contains unsupported characters.');
        }
        if (!is_file(base_path('public/router.php'))) {
            throw new \RuntimeException('public/router.php is missing from the suite.');
        }

        $address = $host . ':' . $port;
        $command = escapeshellarg(PHP_BINARY)
            . ' -S ' . escapeshellarg($address)
            . ' -t ' . escapeshellarg(base_path('public'))
            . ' ' . escapeshellarg(base_path('public/router.php'));

        $this->banner();
        $this->success('Nexa Suite listening on http://' . $address);
        $this->info('Press Ctrl+C to stop.');
        if ($host === '0.0.0.0') {
            $this->warn('Bound to all interfaces. Put TLS in front of it before exposing it to the internet.');
        }
        $this->line('');

        passthru($command, $exitCode);

        return (int) $exitCode;
    }

    /* --------------------------------------------------------------- users */

    private function userCreate(): int
    {
        $app = app();
        $name = trim((string) $this->option('name', 'Admin'));
        $email = strtolower(trim((string) $this->option('email', '')));
        $password = (string) $this->option('password', '');
        $force = (bool) $this->option('force', false);

        if ($email === '' && $this->interactive()) {
            $email = strtolower($this->prompt('Admin email'));
        }
        if ($password === '' && $this->interactive()) {
            $password = $this->secretPrompt('Password (min 10 characters)');
        }

        if (!is_email($email)) {
            throw new \InvalidArgumentException('Pass a valid --email address.');
        }
        if (strlen($password) < 10) {
            throw new \InvalidArgumentException('Use a password with at least 10 characters.');
        }
        if (strlen($name) < 1 || strlen($name) > 80) {
            throw new \InvalidArgumentException('The name must be between 1 and 80 characters.');
        }

        $existing = $app->db()->get('SELECT * FROM users WHERE email = :email', [':email' => $email]);
        if ($existing !== null && !$force) {
            throw new \RuntimeException('That email already exists. Pass --force to reset its password.');
        }

        if ($existing !== null) {
            $app->db()->update('users', [
                'name' => $name,
                'password_hash' => $app->auth()->hashPassword($password),
                'role' => 'admin',
            ], 'id = :id', [':id' => $existing['id']]);
            $id = (int) $existing['id'];
            $this->success('Admin password reset for ' . $email . '.');
        } else {
            $id = $app->db()->insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => $app->auth()->hashPassword($password),
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->success('Admin account created for ' . $email . '.');
        }

        if ($this->json) {
            $this->jsonOutput(['user' => ['id' => $id, 'name' => $name, 'email' => $email, 'role' => 'admin']]);
        }

        return 0;
    }

    private function userList(): int
    {
        $users = app()->db()->all('SELECT id, name, email, role, created_at FROM users ORDER BY id');
        if ($this->json) {
            $this->jsonOutput(['users' => $users, 'count' => count($users)]);
            return 0;
        }

        $rows = array_map(static fn (array $user): array => [
            (string) $user['id'],
            (string) $user['name'],
            (string) $user['email'],
            (string) $user['role'],
            nice_date((string) $user['created_at']),
        ], $users);

        $this->table(['ID', 'Name', 'Email', 'Role', 'Created'], $rows);
        $this->info(count($users) . ' console account' . (count($users) === 1 ? '' : 's') . '.');

        return 0;
    }

    /* ------------------------------------------------------------ licenses */

    private function licenseCreate(): int
    {
        $app = app();
        $plan = strtolower(trim((string) $this->option('plan', 'pro')));
        $email = strtolower(trim((string) $this->option('email', '')));
        $note = trim((string) $this->option('note', ''));
        $daysRaw = $this->option('days', null);
        $lifetime = (bool) $this->option('lifetime', false);

        if ($email !== '' && !is_email($email)) {
            throw new \InvalidArgumentException('The customer email is not valid.');
        }

        $days = 365;
        if ($lifetime || $daysRaw === 'lifetime' || $daysRaw === '0') {
            $days = null;
        } elseif ($daysRaw !== null) {
            if (!preg_match('/^\d+$/', (string) $daysRaw)) {
                throw new \InvalidArgumentException('--days must be a whole number, 0 for lifetime, or "lifetime".');
            }
            $days = (int) $daysRaw;
            if ($days < 1) {
                $days = null;
            }
        }

        $planConfig = $app->plans()[$plan] ?? null;
        $defaultActivations = $planConfig !== null ? (int) $planConfig['activations'] : 3;
        $maxActivations = (int) $this->option('activations', (string) $defaultActivations);
        if ($maxActivations < 1 || $maxActivations > 1000) {
            throw new \InvalidArgumentException('--activations must be between 1 and 1000.');
        }

        $result = $app->licenses()->create($plan, $days, $maxActivations, $email, $note, null);
        $license = $result['license'];

        if ($this->json) {
            $this->jsonOutput([
                'key' => $result['key'],
                'license' => $this->licenseJson($license),
            ]);
            return 0;
        }

        $this->success('License created.');
        $this->line('');
        $this->line('  ' . $this->bold($result['key']));
        $this->line('');
        $this->info('Only the SHA-256 hash is stored. Copy this key now; it will not be shown again.');
        $this->info('Plan: ' . $plan . ' · Activations: ' . $maxActivations . ' · Expires: ' . ($days === null ? 'never' : nice_date((string) $license['expires_at'])));

        return 0;
    }

    private function licenseList(): int
    {
        $search = trim((string) $this->option('search', ''));
        $licenses = app()->licenses()->list(true, $search);

        if ($this->json) {
            $data = array_map(fn (array $license): array => $this->licenseJson($license), $licenses);
            $this->jsonOutput(['licenses' => $data, 'count' => count($data)]);
            return 0;
        }

        $rows = [];
        foreach ($licenses as $license) {
            $expired = $license['expires_at'] !== null && strtotime((string) $license['expires_at']) < time();
            $rows[] = [
                (string) $license['id'],
                (string) $license['plan'],
                (string) $license['key_prefix'],
                $expired && $license['status'] === 'active' ? 'expired' : (string) $license['status'],
                $license['activations'] . '/' . $license['max_activations'],
                $license['expires_at'] === null ? 'lifetime' : nice_date((string) $license['expires_at']),
                (string) ($license['email'] !== '' ? $license['email'] : '—'),
            ];
        }

        $this->table(['ID', 'Plan', 'Key prefix', 'Status', 'Uses', 'Expires', 'Customer'], $rows);
        $this->info(count($licenses) . ' license' . (count($licenses) === 1 ? '' : 's') . '.');

        return 0;
    }

    private function licenseStatus(string $status): int
    {
        $id = $this->requireId('license');
        $license = app()->licenses()->find($id);
        if ($license === null) {
            throw new \RuntimeException('License #' . $id . ' was not found.');
        }

        app()->licenses()->setStatus($id, $status);
        $this->success('License #' . $id . ' is now ' . $status . '.');
        if ($this->json) {
            $this->jsonOutput(['license' => $this->licenseJson(app()->licenses()->find($id) ?? $license)]);
        }

        return 0;
    }

    private function licenseRegenerate(): int
    {
        $id = $this->requireId('license');
        $key = app()->licenses()->regenerate($id);
        if ($key === null) {
            throw new \RuntimeException('License #' . $id . ' was not found.');
        }

        if ($this->json) {
            $this->jsonOutput(['id' => $id, 'key' => $key]);
            return 0;
        }

        $this->success('New key generated for license #' . $id . '.');
        $this->line('');
        $this->line('  ' . $this->bold($key));
        $this->line('');
        $this->info('The previous key stops working immediately.');

        return 0;
    }

    private function licenseValidate(): int
    {
        $key = (string) ($this->args[0] ?? $this->option('key', ''));
        $domain = (string) $this->option('domain', '');
        $instance = (string) $this->option('instance', '');

        if ($key === '') {
            throw new \InvalidArgumentException('Pass a license key: license:validate NEXA-XXXXX-XXXXX-XXXXX-XXXXX');
        }

        $result = app()->licenses()->validate($key, $domain !== '' ? $domain : null, $instance);
        $payload = [
            'valid' => (bool) $result['valid'],
            'reason' => (string) $result['reason'],
            'activated' => $result['activated'] ?? null,
            'license' => $result['license'],
            'activation' => $result['activation'],
        ];

        if ($this->json) {
            $this->jsonOutput($payload, $result['valid'] ? 0 : 1);
            return $result['valid'] ? 0 : 1;
        }

        if (!$result['valid']) {
            $this->error('License is not valid: ' . $result['reason'] . '.');
            return 1;
        }

        $license = (array) $result['license'];
        $this->success('License is valid.');
        $this->line('  Plan:        ' . ($license['plan_name'] ?? $license['plan']));
        $this->line('  Status:      ' . $license['status']);
        $this->line('  Expires:     ' . ($license['expires_at'] ?? 'never'));
        $this->line('  Activations: ' . ($license['activations'] ?? '?') . ' / ' . $license['max_activations']);
        if ($domain !== '') {
            $this->line('  Domain:      ' . $domain . ' — ' . ($result['activated'] ? 'activated' : 'not activated'));
        }

        return 0;
    }

    private function licenseDelete(): int
    {
        $id = $this->requireId('license');
        $license = app()->licenses()->find($id);
        if ($license === null) {
            throw new \RuntimeException('License #' . $id . ' was not found.');
        }

        $this->guard('Delete license #' . $id . ' and all of its activations?');
        app()->licenses()->delete($id);
        $this->success('License #' . $id . ' deleted.');

        return 0;
    }

    /* ------------------------------------------------------------ api keys */

    private function keyCreate(): int
    {
        $name = trim((string) $this->option('name', 'API key'));
        $scopeRaw = trim((string) $this->option('scopes', ''));

        if ($scopeRaw === '' || $scopeRaw === '*') {
            $scopes = ApiKeyService::SCOPES;
        } else {
            $scopes = array_values(array_filter(array_map('trim', explode(',', $scopeRaw))));
            foreach ($scopes as $scope) {
                if (!in_array($scope, ApiKeyService::SCOPES, true)) {
                    throw new \InvalidArgumentException('Unknown scope "' . $scope . '". Allowed: ' . implode(', ', ApiKeyService::SCOPES));
                }
            }
        }

        $result = app()->apiKeys()->create($name, $scopes, null);
        $key = $result['api_key'];

        if ($this->json) {
            $this->jsonOutput([
                'key' => $result['key'],
                'api_key' => $this->keyJson($key),
            ]);
            return 0;
        }

        $this->success('API key created.');
        $this->line('');
        $this->line('  ' . $this->bold($result['key']));
        $this->line('');
        $this->info('Scopes: ' . implode(', ', $this->scopesOf($key)));
        $this->info('Send it as: Authorization: Bearer <key>');
        $this->info('The full key is not stored and cannot be recovered.');

        return 0;
    }

    private function keyList(): int
    {
        $keys = app()->apiKeys()->list();
        if ($this->json) {
            $this->jsonOutput([
                'api_keys' => array_map(fn (array $key): array => $this->keyJson($key), $keys),
                'count' => count($keys),
            ]);
            return 0;
        }

        $rows = array_map(fn (array $key): array => [
            (string) $key['id'],
            (string) $key['name'],
            (string) $key['key_prefix'],
            implode(', ', $this->scopesOf($key)),
            (string) $key['status'],
            time_ago($key['last_used_at'] !== null ? (string) $key['last_used_at'] : null),
        ], $keys);

        $this->table(['ID', 'Name', 'Prefix', 'Scopes', 'Status', 'Last used'], $rows);
        $this->info(count($keys) . ' API key' . (count($keys) === 1 ? '' : 's') . '.');

        return 0;
    }

    private function keyRevoke(): int
    {
        $id = $this->requireId('API key');
        $key = app()->apiKeys()->find($id);
        if ($key === null) {
            throw new \RuntimeException('API key #' . $id . ' was not found.');
        }

        app()->apiKeys()->revoke($id);
        $this->success('API key #' . $id . ' revoked.');
        if ($this->json) {
            $this->jsonOutput(['api_key' => $this->keyJson(app()->apiKeys()->find($id) ?? $key)]);
        }

        return 0;
    }

    /* ----------------------------------------------------------- extensions */

    private function extensionList(): int
    {
        $extensions = app()->extensions()->all();
        if ($this->json) {
            $this->jsonOutput(['extensions' => $extensions, 'count' => count($extensions)]);
            return 0;
        }

        $rows = array_map(static fn (array $extension): array => [
            (string) $extension['id'],
            (string) $extension['name'],
            (string) $extension['category'],
            (string) $extension['version'],
            (string) $extension['status'],
            !empty($extension['bundled']) ? 'bundled' : 'catalog',
        ], $extensions);

        $this->table(['ID', 'Name', 'Category', 'Version', 'Status', 'Source'], $rows);
        $stats = app()->extensions()->stats();
        $this->info($stats['enabled'] . ' of ' . $stats['total'] . ' extensions enabled.');

        return 0;
    }

    private function extensionToggle(bool $enabled): int
    {
        $id = (string) ($this->args[0] ?? '');
        if ($id === '') {
            throw new \InvalidArgumentException('Pass an extension ID: extension:' . ($enabled ? 'enable' : 'disable') . ' discord-auth');
        }

        if (!app()->extensions()->toggle($id, $enabled)) {
            throw new \RuntimeException('Extension "' . $id . '" was not found in the catalog.');
        }

        $this->success('Extension "' . $id . '" is now ' . ($enabled ? 'enabled' : 'disabled') . '.');
        if ($this->json) {
            $this->jsonOutput(['extension' => app()->extensions()->find($id)]);
        }

        return 0;
    }

    private function extensionAdd(): int
    {
        $input = [
            'name' => (string) $this->option('name', ''),
            'description' => (string) $this->option('description', ''),
            'category' => (string) $this->option('category', 'Community'),
            'version' => (string) $this->option('version', '1.0.0'),
            'source_url' => (string) $this->option('url', $this->option('source-url', '')),
            'install' => (string) $this->option('install', ''),
            'requires' => (string) $this->option('requires', 'Blueprint'),
        ];

        $extension = app()->extensions()->add($input);
        if ($this->json) {
            $this->jsonOutput(['extension' => $extension]);
            return 0;
        }

        $this->success('Extension "' . $extension['name'] . '" added as ' . $extension['id'] . '.');
        $this->info('Toggle it with: php bin/nexa extension:enable ' . $extension['id']);

        return 0;
    }

    /* ---------------------------------------------------------------- bots */

    private function botCreate(): int
    {
        $input = [
            'name' => trim((string) $this->option('name', '')),
            'memory' => (int) $this->option('memory', '1024'),
            'disk' => (int) $this->option('disk', '4096'),
            'cpu' => (int) $this->option('cpu', '100'),
            'node_id' => (int) $this->option('node', '0'),
            'nest_id' => (int) $this->option('nest', '0'),
            'egg_id' => (int) $this->option('egg', '0'),
            'owner_id' => (int) $this->option('owner', '0'),
            'owner_email' => (string) $this->option('owner-email', ''),
            'allocation_id' => (int) $this->option('allocation', '0'),
        ];

        if ($input['name'] === '' && $this->interactive()) {
            $input['name'] = $this->prompt('Bot server name');
        }

        $bot = app()->bots()->create($input, null);
        if ($this->json) {
            $this->jsonOutput(['bot' => app()->bots()->apiData($bot)]);
            return 0;
        }

        $this->success('Bot server created.');
        $this->line('  ID:         ' . $bot['id']);
        $this->line('  Name:       ' . $bot['name']);
        $this->line('  Server:     ' . $bot['server_identifier'] . ' (#' . $bot['server_id'] . ')');
        $this->line('  Node/Egg:   node ' . $bot['node_id'] . ' · egg ' . $bot['egg_id']);
        $this->line('  Status:     ' . $bot['status']);
        $this->info('Power it with: php bin/nexa bot:start ' . $bot['id']);

        return 0;
    }

    private function botList(): int
    {
        $bots = app()->bots()->list();
        if ($this->json) {
            $this->jsonOutput([
                'bots' => array_map(fn (array $bot): array => app()->bots()->apiData($bot), $bots),
                'count' => count($bots),
            ]);
            return 0;
        }

        $rows = array_map(static fn (array $bot): array => [
            (string) $bot['id'],
            (string) $bot['name'],
            (string) $bot['server_identifier'],
            'node ' . $bot['node_id'],
            (string) $bot['memory'] . ' MB',
            (string) $bot['status'],
            nice_datetime((string) $bot['created_at']),
        ], $bots);

        $this->table(['ID', 'Name', 'Server', 'Node', 'Memory', 'Status', 'Created'], $rows);
        $stats = app()->bots()->stats();
        $this->info($stats['total'] . ' bot server' . ($stats['total'] === 1 ? '' : 's') . ' · ' . $stats['running'] . ' running.');

        return 0;
    }

    private function botPower(string $signal): int
    {
        $id = $this->requireId('bot');
        $bot = app()->bots()->power($id, $signal);

        if ($this->json) {
            $this->jsonOutput(['bot' => app()->bots()->apiData($bot)]);
            return 0;
        }

        $this->success('Sent ' . $signal . ' to bot #' . $id . ' (' . $bot['name'] . ').');
        $this->info('Reported status: ' . $bot['status']);

        return 0;
    }

    private function botDelete(): int
    {
        $id = $this->requireId('bot');
        $bot = app()->bots()->find($id);
        if ($bot === null) {
            throw new \RuntimeException('Bot #' . $id . ' was not found.');
        }

        $this->guard('Delete bot #' . $id . ' (' . $bot['name'] . ') from Pterodactyl? This deletes its files and backups.');
        app()->bots()->delete($id);
        $this->success('Bot #' . $id . ' deleted.');

        return 0;
    }

    /* ------------------------------------------------------------ settings */

    private function settingsShow(): int
    {
        $app = app();
        $values = $app->settings()->all();
        $masked = [];
        foreach ($values as $key => $value) {
            $masked[$key] = $app->settings()->isSecret($key) ? mask_secret((string) $value) : $value;
        }

        if ($this->json) {
            $this->jsonOutput([
                'settings' => $masked,
                'meta' => [
                    'version' => NEXA_VERSION,
                    'database' => (string) $app->config['database'],
                    'url' => $app->url('/'),
                    'port' => (int) $app->config['port'],
                    'installed' => $app->installed(),
                ],
            ]);
            return 0;
        }

        $rows = [];
        foreach ($masked as $key => $value) {
            $rows[] = [$key, (string) ($value === '' ? '—' : $value)];
        }

        $this->table(['Setting', 'Value'], $rows);
        $this->info('Secrets are encrypted at rest and masked here.');
        $this->info('Update with: php bin/nexa settings:set key=value');

        return 0;
    }

    private function settingsSet(): int
    {
        $pairs = [];
        foreach ($this->args as $argument) {
            if (str_contains($argument, '=')) {
                [$key, $value] = explode('=', $argument, 2);
                $pairs[$key] = $value;
            }
        }

        if ($pairs === [] && count($this->args) === 2) {
            $pairs[$this->args[0]] = $this->args[1];
        }

        if ($pairs === []) {
            throw new \InvalidArgumentException('Pass settings as key=value, for example: settings:set pterodactyl_url=https://panel.example.com');
        }

        $allowed = array_keys(app()->settings()->defaults());
        $saved = [];

        foreach ($pairs as $key => $value) {
            $key = trim($key);
            $value = trim((string) $value);

            if (!in_array($key, $allowed, true)) {
                throw new \InvalidArgumentException('Unknown setting "' . $key . '". Allowed: ' . implode(', ', $allowed));
            }

            $value = $this->validateSetting($key, $value);
            app()->settings()->set($key, $value);
            $saved[$key] = app()->settings()->isSecret($key) ? mask_secret($value) : $value;
        }

        if ($this->json) {
            $this->jsonOutput(['settings' => $saved]);
            return 0;
        }

        foreach ($saved as $key => $value) {
            $this->success($key . ' = ' . ($value === '' ? '(empty)' : $value));
        }

        return 0;
    }

    private function validateSetting(string $key, string $value): string
    {
        if (in_array($key, ['pterodactyl_url', 'suite_url'], true) && $value !== '') {
            $value = rtrim($this->validateUrl($value, $key), '/');
        }

        if ($key === 'support_email' && $value !== '' && !is_email($value)) {
            throw new \InvalidArgumentException('support_email must be a valid email address.');
        }

        if ($key === 'site_name' && ($value === '' || strlen($value) > 80)) {
            throw new \InvalidArgumentException('site_name must be between 1 and 80 characters.');
        }

        if ($key === 'pterodactyl_verify_tls') {
            $normalized = strtolower($value);
            if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
                return '0';
            }
            return '1';
        }

        if (in_array($key, [
            'pterodactyl_owner_id',
            'pterodactyl_default_nest',
            'pterodactyl_default_egg',
            'pterodactyl_default_node',
        ], true) && $value !== '' && !preg_match('/^\d+$/', $value)) {
            throw new \InvalidArgumentException($key . ' must be a numeric Pterodactyl ID.');
        }

        return $value;
    }

    /* --------------------------------------------------------------- utils */

    private function parse(array $tokens): void
    {
        $count = count($tokens);
        for ($index = 0; $index < $count; $index++) {
            $token = (string) $tokens[$index];

            if ($token === '--') {
                for ($rest = $index + 1; $rest < $count; $rest++) {
                    $this->args[] = (string) $tokens[$rest];
                }
                return;
            }

            if ($token === '-h') {
                $this->options['help'] = true;
                continue;
            }

            if (!str_starts_with($token, '--')) {
                $this->args[] = $token;
                continue;
            }

            $body = substr($token, 2);
            if ($body === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $body, 2), 2, null);
            $key = str_replace('_', '-', $key);

            if ($value === null) {
                if (in_array($key, self::FLAG_OPTIONS, true)) {
                    $value = '1';
                } elseif ($index + 1 < $count && !str_starts_with((string) $tokens[$index + 1], '--')) {
                    $index++;
                    $value = (string) $tokens[$index];
                } else {
                    $value = '1';
                }
            }

            $this->options[$key] = $value;
        }
    }

    private function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    private function requireId(string $label): int
    {
        $raw = (string) ($this->args[0] ?? $this->option('id', ''));
        if ($raw === '' || !preg_match('/^\d+$/', $raw) || (int) $raw < 1) {
            throw new \InvalidArgumentException('Pass a numeric ' . $label . ' ID as the first argument.');
        }

        return (int) $raw;
    }

    private function guard(string $question): void
    {
        if ((bool) $this->option('force', false)) {
            return;
        }

        if (!$this->interactive()) {
            throw new \InvalidArgumentException('This is destructive. Re-run with --force in a non-interactive shell.');
        }

        $answer = strtolower(trim($this->prompt($question . ' Type "yes" to continue', 'no')));
        if (!in_array($answer, ['yes', 'y'], true)) {
            throw new \RuntimeException('Cancelled.');
        }
    }

    private function validateUrl(string $value, string $label): string
    {
        $parts = parse_url($value);
        if ($parts === false || !isset($parts['scheme'], $parts['host']) || !in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('The ' . $label . ' must be a full http:// or https:// URL.');
        }

        return $value;
    }

    private function licenseJson(array $license): array
    {
        return [
            'id' => (int) $license['id'],
            'plan' => (string) $license['plan'],
            'status' => (string) $license['status'],
            'key_prefix' => (string) $license['key_prefix'],
            'email' => (string) $license['email'],
            'note' => (string) $license['note'],
            'max_activations' => (int) $license['max_activations'],
            'activations' => (int) ($license['activations'] ?? 0),
            'expires_at' => $license['expires_at'],
            'last_checked_at' => $license['last_checked_at'],
            'created_at' => (string) $license['created_at'],
        ];
    }

    private function keyJson(array $key): array
    {
        return [
            'id' => (int) $key['id'],
            'name' => (string) $key['name'],
            'key_prefix' => (string) $key['key_prefix'],
            'scopes' => $this->scopesOf($key),
            'status' => (string) $key['status'],
            'last_used_at' => $key['last_used_at'],
            'created_at' => (string) $key['created_at'],
        ];
    }

    private function scopesOf(array $key): array
    {
        $scopes = json_decode((string) ($key['scopes'] ?? '[]'), true);

        return is_array($scopes) ? array_values($scopes) : [];
    }

    private function jsonOutput(array $data, int $exitCode = 0): void
    {
        $this->line((string) json_encode(
            ['ok' => $exitCode === 0] + $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
    }

    private function unknownCommand(string $command): int
    {
        $this->error('Unknown command "' . $command . '".');
        $this->printHelp();
        return 1;
    }

    private function printHelp(): void
    {
        $this->line($this->bold('Nexa Suite CLI') . ' v' . NEXA_VERSION);
        $this->line('Usage: php bin/nexa <command> [arguments] [options]');
        $this->line('');
        $this->line('Setup');
        $this->line('  install                    Create config, database and the first admin');
        $this->line('  serve                      Run the built-in server (default 127.0.0.1:4411)');
        $this->line('  user:create                Add an admin (or reset one with --force)');
        $this->line('  user:list                  List console accounts');
        $this->line('');
        $this->line('Licenses');
        $this->line('  license:create             Create a key  --plan --days --activations --email --note');
        $this->line('  license:list               List keys       --search --json');
        $this->line('  license:validate <key>     Validate a key --domain --instance');
        $this->line('  license:revoke <id>        Disable a key');
        $this->line('  license:restore <id>       Re-enable a key');
        $this->line('  license:regenerate <id>    Replace the key value');
        $this->line('  license:delete <id>        Delete a key and its activations (--force)');
        $this->line('');
        $this->line('API keys');
        $this->line('  key:create                 Mint a key     --name --scopes');
        $this->line('  key:list                   List keys');
        $this->line('  key:revoke <id>            Revoke a key');
        $this->line('');
        $this->line('Extensions');
        $this->line('  extension:list             Show the catalog');
        $this->line('  extension:enable <id>      Enable an entry');
        $this->line('  extension:disable <id>     Disable an entry');
        $this->line('  extension:add              Add an entry   --name --category --url --description');
        $this->line('');
        $this->line('Bots');
        $this->line('  bot:create                 Provision a server --name --memory --disk --cpu --node --nest --egg');
        $this->line('  bot:list                   List bot servers');
        $this->line('  bot:start|stop|restart <id>  Send a power signal');
        $this->line('  bot:kill <id>              Force stop a bot');
        $this->line('  bot:delete <id>            Delete from Pterodactyl (--force)');
        $this->line('');
        $this->line('Settings');
        $this->line('  settings:show              Show masked configuration');
        $this->line('  settings:set key=value     Update one or more settings');
        $this->line('');
        $this->line('Options');
        $this->line('  --json                     Machine-readable output where supported');
        $this->line('  --force                    Confirm destructive commands or reset a user');
        $this->line('  --no-interaction           Never prompt (for cron and CI)');
        $this->line('  -h, --help                 Show this help');
        $this->line('');
        $this->line('Examples');
        $this->line('  php bin/nexa install --site="Nexa Suite" --url="https://nexa.example.com" \\');
        $this->line('    --name="Admin" --email="admin@example.com" --password="a-long-password"');
        $this->line('  php bin/nexa license:create --plan=pro --days=365 --activations=3 --email=customer@example.com');
        $this->line('  php bin/nexa key:create --name="Billing bot" --scopes=bots:write,licenses:read');
        $this->line('  php bin/nexa bot:create --name="Music Bot" --memory=1024 --disk=4096 --cpu=100');
    }

    private function banner(): void
    {
        if ($this->json) {
            return;
        }

        $this->line($this->bold('Nexa Suite') . ' · v' . NEXA_VERSION);
        $this->line('');
    }

    private function table(array $headers, array $rows): void
    {
        if ($rows === []) {
            $this->line('  (nothing to show)');
            return;
        }

        $widths = [];
        foreach ($headers as $index => $header) {
            $widths[$index] = $this->width((string) $header);
        }
        foreach ($rows as $row) {
            foreach (array_values($row) as $index => $value) {
                $widths[$index] = max($widths[$index] ?? 0, $this->width((string) $value));
            }
        }

        $headerLine = [];
        foreach ($headers as $index => $header) {
            $headerLine[] = $this->pad((string) $header, $widths[$index]);
        }
        $this->line('  ' . $this->bold(implode('  ', $headerLine)));
        $this->line('  ' . implode('  ', array_map(static fn (int $width): string => str_repeat('-', $width), $widths)));

        foreach ($rows as $row) {
            $line = [];
            foreach (array_values($row) as $index => $value) {
                $line[] = $this->pad((string) $value, $widths[$index]);
            }
            $this->line('  ' . implode('  ', $line));
        }
    }

    private function width(string $value): int
    {
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);

        return function_exists('mb_strwidth') ? mb_strwidth($value, 'UTF-8') : strlen($value);
    }

    private function pad(string $value, int $width): string
    {
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        $current = $this->width($value);

        return $value . str_repeat(' ', max(0, $width - $current));
    }

    private function interactive(): bool
    {
        if ((bool) $this->option('no-interaction', false)) {
            return false;
        }

        return function_exists('stream_isatty') && @stream_isatty(STDIN);
    }

    private function prompt(string $question, string $default = ''): string
    {
        $suffix = $default !== '' ? ' [' . $default . ']' : '';
        $this->write($question . $suffix . ': ');
        $line = fgets(STDIN);
        if ($line === false) {
            return $default;
        }
        $value = trim($line);

        return $value === '' ? $default : $value;
    }

    private function secretPrompt(string $question): string
    {
        $this->write($question . ': ');
        $hidden = false;
        if (DIRECTORY_SEPARATOR !== '\\' && function_exists('shell_exec')) {
            @shell_exec('stty -echo 2>/dev/null');
            $hidden = true;
        }

        $line = fgets(STDIN);

        if ($hidden) {
            @shell_exec('stty echo 2>/dev/null');
            $this->line('');
        }

        return $line === false ? '' : trim($line);
    }

    private function supportsColor(): bool
    {
        if (getenv('NO_COLOR') !== false) {
            return false;
        }
        if (getenv('TERM') === 'dumb') {
            return false;
        }

        return function_exists('stream_isatty') && @stream_isatty(STDOUT);
    }

    private function colorize(string $text, string $code): string
    {
        return $this->color ? "\033[" . $code . 'm' . $text . "\033[0m" : $text;
    }

    private function bold(string $text): string
    {
        return $this->colorize($text, '1');
    }

    private function success(string $text): void
    {
        $this->line($this->colorize('OK', '32') . '  ' . $text);
    }

    private function info(string $text): void
    {
        $this->line($this->colorize('->', '36') . '  ' . $text);
    }

    private function warn(string $text): void
    {
        $this->line($this->colorize('WARN', '33') . '  ' . $text);
    }

    private function error(string $text): void
    {
        $line = $this->colorize('ERROR', '31') . '  ' . $text;
        if (defined('STDERR')) {
            fwrite(STDERR, $line . PHP_EOL);
        } else {
            $this->line($line);
        }
    }

    private function line(string $text = ''): void
    {
        $this->write($text . PHP_EOL);
    }

    private function write(string $text): void
    {
        if (defined('STDOUT')) {
            fwrite(STDOUT, $text);
        } else {
            echo $text;
        }
    }
}
