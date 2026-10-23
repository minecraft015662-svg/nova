<?php

declare(strict_types=1);

namespace Nexa\Controllers;

use Nexa\App;

final class AdminController
{
    public static function overview(): string
    {
        $app = app();
        $licenses = $app->licenses()->list(true);
        $bots = $app->bots()->list();

        return view('admin/overview', [
            'title' => 'Overview',
            'active' => 'overview',
            'licenseStats' => $app->licenses()->stats(),
            'apiStats' => $app->apiKeys()->stats(),
            'botStats' => $app->bots()->stats(),
            'extStats' => $app->extensions()->stats(),
            'recentLicenses' => array_slice($licenses, 0, 5),
            'recentBots' => array_slice($bots, 0, 4),
            'panelConfigured' => $app->pterodactyl()->configured(),
            'clientConfigured' => $app->pterodactyl()->clientConfigured(),
            'panelUrl' => (string) $app->settings()->get('pterodactyl_url'),
            'licenseProduct' => (string) $app->settings()->get('license_product', 'Nexa Theme'),
        ], 'app');
    }

    public static function licenses(): string
    {
        $app = app();
        $search = (string) query('q');
        $licenses = $app->licenses()->list(true, $search);
        $activationRows = $app->db()->all('SELECT * FROM license_activations ORDER BY id DESC LIMIT 1000');
        $activations = [];
        foreach ($activationRows as $row) {
            $activations[(int) $row['license_id']][] = $row;
        }

        $newKey = $_SESSION['_new_license'] ?? null;
        unset($_SESSION['_new_license']);

        return view('admin/licenses', [
            'title' => 'Licenses',
            'active' => 'licenses',
            'licenses' => $licenses,
            'activations' => $activations,
            'search' => $search,
            'plans' => $app->plans(),
            'newKey' => is_array($newKey) ? $newKey : null,
            'totalActivations' => $app->licenses()->stats()['activations'],
        ], 'app');
    }

    public static function createLicense(): string
    {
        $app = app();
        $plan = strtolower((string) post('plan', 'pro'));
        if (!array_key_exists($plan, $app->plans())) {
            $plan = 'custom';
        }

        $duration = (string) post('duration', '365');
        $days = match ($duration) {
            '30' => 30,
            '90' => 90,
            '180' => 180,
            '365' => 365,
            'lifetime' => null,
            'custom' => input_int('custom_days', 365, 1, 36500),
            default => 365,
        };

        $maxDefault = $plan !== 'custom' ? (int) ($app->plans()[$plan]['activations'] ?? 1) : 3;
        $maxActivations = input_int('max_activations', max(1, $maxDefault), 1, 1000);

        $email = strtolower(trim((string) post('email')));
        if ($email !== '' && !is_email($email)) {
            flash('error', 'The customer email is not valid.');
            flash_old($_POST);
            redirect('/admin/licenses');
        }

        $result = $app->licenses()->create(
            $plan,
            $days,
            $maxActivations,
            $email,
            (string) post('note'),
            (int) $app->auth()->user()['id']
        );

        $_SESSION['_new_license'] = ['key' => $result['key'], 'plan' => $plan];
        flash('success', 'License created. Copy the key now — it is only shown once.');
        redirect('/admin/licenses');

        return '';
    }

    public static function revokeLicense(string $id): string
    {
        $app = app();
        $license = $app->licenses()->find((int) $id);
        if ($license === null) {
            flash('error', 'License not found.');
            redirect('/admin/licenses');
        }

        $app->licenses()->setStatus((int) $id, 'revoked');
        flash('success', 'License ' . $license['key_prefix'] . ' revoked. Activations stay on record.');
        redirect('/admin/licenses');

        return '';
    }

    public static function restoreLicense(string $id): string
    {
        $app = app();
        $license = $app->licenses()->find((int) $id);
        if ($license === null) {
            flash('error', 'License not found.');
            redirect('/admin/licenses');
        }

        $app->licenses()->setStatus((int) $id, 'active');
        flash('success', 'License ' . $license['key_prefix'] . ' is active again.');
        redirect('/admin/licenses');

        return '';
    }

    public static function regenerateLicense(string $id): string
    {
        $app = app();
        $license = $app->licenses()->find((int) $id);
        if ($license === null) {
            flash('error', 'License not found.');
            redirect('/admin/licenses');
        }

        $key = $app->licenses()->regenerate((int) $id);
        if ($key === null) {
            flash('error', 'Could not regenerate this license.');
            redirect('/admin/licenses');
        }

        $_SESSION['_new_license'] = ['key' => $key, 'plan' => $license['plan']];
        flash('success', 'A new key was generated and appears above. The old key stopped working.');
        redirect('/admin/licenses');

        return '';
    }

    public static function apiKeys(): string
    {
        $app = app();
        $newKey = $_SESSION['_new_api_key'] ?? null;
        unset($_SESSION['_new_api_key']);

        return view('admin/api-keys', [
            'title' => 'API keys',
            'active' => 'api-keys',
            'keys' => $app->apiKeys()->list(),
            'scopes' => \Nexa\ApiKeyService::SCOPES,
            'newKey' => is_string($newKey) && $newKey !== '' ? $newKey : null,
        ], 'app');
    }

    public static function createApiKey(): string
    {
        $app = app();
        $name = (string) post('name', 'API key');
        $scopes = $_POST['scopes'] ?? [];
        if (!is_array($scopes)) {
            $scopes = [];
        }
        $scopes = array_values(array_filter($scopes, static fn ($scope): bool => is_string($scope)));

        $result = $app->apiKeys()->create($name, $scopes, (int) $app->auth()->user()['id']);
        $_SESSION['_new_api_key'] = $result['key'];
        flash('success', 'API key created. Copy it now — it cannot be shown again.');
        redirect('/admin/api-keys');

        return '';
    }

    public static function revokeApiKey(string $id): string
    {
        app()->apiKeys()->revoke((int) $id);
        flash('success', 'API key revoked.');
        redirect('/admin/api-keys');

        return '';
    }

    public static function extensions(): string
    {
        $app = app();
        $entries = $app->extensions()->all();
        $categoryRank = ['Theme' => 0, 'Suite' => 1, 'Framework' => 2, 'Community' => 3];
        $preferred = ['nexa-theme' => 0, 'nexa-editor' => 1, 'nexa-suite' => 2, 'blueprint' => 3];
        usort($entries, static function (array $a, array $b) use ($categoryRank, $preferred): int {
            $bundled = (int) empty($a['bundled']) <=> (int) empty($b['bundled']);
            if ($bundled !== 0) {
                return $bundled;
            }

            $rankA = $categoryRank[$a['category']] ?? 9;
            $rankB = $categoryRank[$b['category']] ?? 9;

            return [$rankA, $preferred[$a['id']] ?? 9, $a['name']] <=> [$rankB, $preferred[$b['id']] ?? 9, $b['name']];
        });

        return view('admin/extensions', [
            'title' => 'Extensions',
            'active' => 'extensions',
            'extensions' => $entries,
            'stats' => $app->extensions()->stats(),
        ], 'app');
    }

    public static function toggleExtension(): string
    {
        $id = (string) post('id');
        $enabled = (string) post('enabled') === '1';
        if (!app()->extensions()->toggle($id, $enabled)) {
            flash('error', 'Extension not found.');
        } else {
            flash('success', 'Extension ' . ($enabled ? 'enabled' : 'disabled') . ' in the catalog.');
        }
        redirect('/admin/extensions');

        return '';
    }

    public static function addExtension(): string
    {
        try {
            app()->extensions()->add($_POST);
            flash('success', 'Extension added to the catalog.');
        } catch (\Throwable $exception) {
            flash('error', $exception->getMessage());
            flash_old($_POST);
        }
        redirect('/admin/extensions');

        return '';
    }

    public static function bots(): string
    {
        $app = app();
        $bots = $app->bots()->list();
        if ($bots !== [] && $app->pterodactyl()->clientConfigured()) {
            $bots = $app->bots()->refresh($bots);
        }

        $nodes = [];
        $nests = [];
        $eggsByNest = [];
        $panelError = null;

        if ($app->pterodactyl()->configured()) {
            try {
                $nodes = $app->pterodactyl()->nodes();
                $nests = $app->pterodactyl()->nests();
                foreach (array_slice($nests, 0, 12) as $nest) {
                    $nestId = (int) ($nest['id'] ?? 0);
                    if ($nestId > 0) {
                        $eggsByNest[$nestId] = $app->pterodactyl()->eggs($nestId);
                    }
                }
            } catch (\Throwable $exception) {
                $panelError = $exception->getMessage();
            }
        }

        return view('admin/bots', [
            'title' => 'Bot hosting',
            'active' => 'bots',
            'bots' => $bots,
            'nodes' => $nodes,
            'nests' => $nests,
            'eggsByNest' => $eggsByNest,
            'panelError' => $panelError,
            'panelConfigured' => $app->pterodactyl()->configured(),
            'clientConfigured' => $app->pterodactyl()->clientConfigured(),
            'defaults' => $app->settings()->all(),
            'stats' => $app->bots()->stats(),
        ], 'app');
    }

    public static function createBot(): string
    {
        $app = app();
        try {
            $app->bots()->create([
                'name' => post('name'),
                'memory' => input_int('memory', 1024, 256, 131072),
                'disk' => input_int('disk', 4096, 256, 524288),
                'cpu' => input_int('cpu', 100, 10, 3200),
                'node_id' => input_int('node_id', 0, 0, PHP_INT_MAX),
                'nest_id' => input_int('nest_id', 0, 0, PHP_INT_MAX),
                'egg_id' => input_int('egg_id', 0, 0, PHP_INT_MAX),
                'owner_id' => input_int('owner_id', 0, 0, PHP_INT_MAX),
            ], (int) $app->auth()->user()['id']);
            flash('success', 'Bot server created. Pterodactyl is installing it now.');
        } catch (\Throwable $exception) {
            $app->log('Bot creation failed: ' . $exception->getMessage());
            flash('error', $exception->getMessage());
            flash_old($_POST);
        }
        redirect('/admin/bots');

        return '';
    }

    public static function powerBot(string $id): string
    {
        try {
            $signal = (string) post('signal');
            $bot = app()->bots()->power((int) $id, $signal);
            flash('success', 'Power signal "' . $signal . '" sent to ' . $bot['name'] . '.');
        } catch (\Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('/admin/bots');

        return '';
    }

    public static function deleteBot(string $id): string
    {
        try {
            app()->bots()->delete((int) $id);
            flash('success', 'Bot server deleted from Pterodactyl and removed from Nexa.');
        } catch (\Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('/admin/bots');

        return '';
    }

    public static function settings(): string
    {
        $app = app();
        $checks = PublicController::requirements();
        $checks[] = ['label' => 'cURL extension', 'ok' => extension_loaded('curl'), 'value' => extension_loaded('curl') ? 'Loaded' : 'Not loaded'];
        $checks[] = ['label' => 'SQLite database', 'ok' => is_file($app->storagePath('nexa.sqlite')), 'value' => $app->storagePath('nexa.sqlite')];

        return view('admin/settings', [
            'title' => 'Settings',
            'active' => 'settings',
            'values' => $app->settings()->all(),
            'checks' => $checks,
            'panelConfigured' => $app->pterodactyl()->configured(),
            'clientConfigured' => $app->pterodactyl()->clientConfigured(),
        ], 'app');
    }

    public static function saveSettings(): string
    {
        $app = app();
        $siteName = trim((string) post('site_name', 'Nexa Suite')) ?: 'Nexa Suite';
        $licenseProduct = trim((string) post('license_product', 'Nexa Theme')) ?: 'Nexa Theme';
        $supportEmail = strtolower(trim((string) post('support_email')));
        $suiteUrl = trim((string) post('suite_url'));
        $panelUrl = trim((string) post('pterodactyl_url'));

        if (strlen($siteName) > 60 || strlen($licenseProduct) > 60) {
            self::settingsFail('Site name and license product must be 60 characters or fewer.');
        }
        if ($supportEmail !== '' && !is_email($supportEmail)) {
            self::settingsFail('The support email address is not valid.');
        }
        if ($suiteUrl !== '' && !filter_var($suiteUrl, FILTER_VALIDATE_URL)) {
            self::settingsFail('The suite URL must be a full URL such as https://panel.example.com:4411.');
        }
        if ($panelUrl !== '') {
            $candidate = preg_match('#^https?://#i', $panelUrl) ? $panelUrl : 'https://' . $panelUrl;
            if (!filter_var($candidate, FILTER_VALIDATE_URL)) {
                self::settingsFail('The Pterodactyl panel URL is not valid.');
            }
        }

        $values = [
            'site_name' => $siteName,
            'license_product' => $licenseProduct,
            'support_email' => $supportEmail,
            'suite_url' => rtrim($suiteUrl, '/'),
            'pterodactyl_url' => rtrim($panelUrl, '/'),
            'pterodactyl_owner_id' => self::optionalInt('pterodactyl_owner_id'),
            'pterodactyl_default_node' => self::optionalInt('pterodactyl_default_node'),
            'pterodactyl_default_nest' => self::optionalInt('pterodactyl_default_nest'),
            'pterodactyl_default_egg' => self::optionalInt('pterodactyl_default_egg'),
            'pterodactyl_verify_tls' => isset($_POST['pterodactyl_verify_tls']) ? '1' : '0',
        ];

        $app->settings()->setMany($values);

        if (isset($_POST['clear_application_key'])) {
            $app->settings()->set('pterodactyl_application_key', '');
        } elseif ((string) post('pterodactyl_application_key') !== '') {
            $app->settings()->set('pterodactyl_application_key', trim((string) post('pterodactyl_application_key')));
        }

        if (isset($_POST['clear_client_key'])) {
            $app->settings()->set('pterodactyl_client_key', '');
        } elseif ((string) post('pterodactyl_client_key') !== '') {
            $app->settings()->set('pterodactyl_client_key', trim((string) post('pterodactyl_client_key')));
        }

        flash('success', 'Settings saved.');
        redirect('/admin/settings');

        return '';
    }

    public static function testPanel(): string
    {
        $app = app();
        try {
            $result = $app->pterodactyl()->testApplication();
            flash('success', $result['message']);
        } catch (\Throwable $exception) {
            flash('error', 'Application API: ' . $exception->getMessage());
        }

        if ($app->pterodactyl()->clientConfigured()) {
            try {
                $result = $app->pterodactyl()->testClient();
                flash('success', $result['message']);
            } catch (\Throwable $exception) {
                flash('error', 'Client API: ' . $exception->getMessage());
            }
        }

        redirect('/admin/settings');

        return '';
    }

    public static function account(): string
    {
        return view('admin/account', [
            'title' => 'Account',
            'active' => 'account',
            'user' => app()->auth()->user(),
        ], 'app');
    }

    public static function updateAccount(): string
    {
        $app = app();
        $user = $app->auth()->user();
        if ($user === null) {
            redirect('/login');
        }

        $current = (string) ($_POST['current_password'] ?? '');
        if (!password_verify($current, (string) $user['password_hash'])) {
            flash('error', 'Your current password is incorrect.');
            redirect('/admin/account');
        }

        $name = trim((string) post('name'));
        $email = strtolower(trim((string) post('email')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($name === '' || strlen($name) > 80) {
            flash('error', 'Enter your name (max 80 characters).');
            redirect('/admin/account');
        }
        if (!is_email($email)) {
            flash('error', 'Enter a valid email address.');
            redirect('/admin/account');
        }
        $taken = $app->db()->get('SELECT id FROM users WHERE email = :email AND id != :id', [
            ':email' => $email,
            ':id' => $user['id'],
        ]);
        if ($taken !== null) {
            flash('error', 'Another account already uses that email address.');
            redirect('/admin/account');
        }

        $data = ['name' => $name, 'email' => $email];
        if ($password !== '') {
            if (strlen($password) < 10) {
                flash('error', 'Use a password with at least 10 characters.');
                redirect('/admin/account');
            }
            if ($password !== $confirmation) {
                flash('error', 'The two passwords do not match.');
                redirect('/admin/account');
            }
            $data['password_hash'] = $app->auth()->hashPassword($password);
        }

        $app->db()->update('users', $data, 'id = :id', [':id' => $user['id']]);
        flash('success', 'Account updated.');
        redirect('/admin/account');

        return '';
    }

    public static function cli(): string
    {
        return view('admin/cli', [
            'title' => 'CLI reference',
            'active' => 'cli',
            'baseUrl' => rtrim(app()->url('/'), '/'),
        ], 'app');
    }

    private static function settingsFail(string $message): never
    {
        flash('error', $message);
        flash_old($_POST);
        redirect('/admin/settings');
    }

    private static function optionalInt(string $key): string
    {
        $raw = trim((string) ($_POST[$key] ?? ''));
        if ($raw === '') {
            return '';
        }

        $value = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $value === false ? '' : (string) $value;
    }
}
