<?php

declare(strict_types=1);

namespace Nexa;

final class App
{
    private static ?self $instance = null;

    public array $config;
    public string $basePath;
    public bool $cli;
    public bool $api = false;
    public ?array $apiKey = null;

    private ?Database $db = null;
    private ?Crypto $crypto = null;
    private ?Auth $auth = null;
    private ?Csrf $csrf = null;
    private ?Settings $settings = null;
    private ?RateLimiter $rateLimiter = null;
    private ?LicenseService $licenseService = null;
    private ?ApiKeyService $apiKeyService = null;
    private ?ExtensionService $extensionService = null;
    private ?Pterodactyl $pterodactyl = null;
    private ?BotService $botService = null;

    public function __construct(bool $cli = false, bool $withSession = true)
    {
        $this->basePath = NEXA_BASE;
        $this->cli = $cli;
        $this->api = !$cli && !$withSession;

        $defaults = [
            'name' => 'Nexa Suite',
            'url' => '',
            'port' => 4411,
            'timezone' => 'UTC',
            'debug' => false,
            'version' => NEXA_VERSION,
            'database' => 'nexa.sqlite',
            'plans' => [
                'starter' => ['name' => 'Starter', 'activations' => 1, 'label' => 'Single install'],
                'pro' => ['name' => 'Pro', 'activations' => 3, 'label' => 'Up to 3 installs'],
                'business' => ['name' => 'Business', 'activations' => 10, 'label' => 'Up to 10 installs'],
                'lifetime' => ['name' => 'Lifetime', 'activations' => 25, 'label' => 'Lifetime access'],
            ],
        ];

        $configFile = $this->basePath . '/config/config.php';
        $fileConfig = is_file($configFile) ? (array) require $configFile : [];
        $this->config = array_replace($defaults, $fileConfig);

        if (getenv('NEXA_DEBUG') === '1') {
            $this->config['debug'] = true;
        }

        date_default_timezone_set((string) ($this->config['timezone'] ?? 'UTC'));
        $this->ensureStorage();

        if (!$cli && $withSession) {
            $this->startSession();
        }

        self::$instance = $this;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Nexa Suite has not been booted.');
        }

        return self::$instance;
    }

    private function ensureStorage(): void
    {
        foreach ([
            $this->basePath . '/storage',
            $this->basePath . '/storage/logs',
            $this->basePath . '/storage/cache',
        ] as $directory) {
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
        }
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = $this->isHttps();
        session_name('nexa_suite');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $https,
        ]);
        session_start();
    }

    public function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    public function db(): Database
    {
        if ($this->db === null) {
            $database = (string) $this->config['database'];
            $path = str_starts_with($database, '/') ? $database : $this->storagePath($database);
            $this->db = new Database($path);
        }

        return $this->db;
    }

    public function key(): string
    {
        if (!empty($this->config['app_key'])) {
            return (string) $this->config['app_key'];
        }

        $file = $this->storagePath('.app_key');
        if (is_file($file)) {
            $key = trim((string) file_get_contents($file));
            if ($key !== '') {
                return $key;
            }
        }

        $key = bin2hex(random_bytes(32));
        if (file_put_contents($file, $key, LOCK_EX) === false) {
            throw new \RuntimeException('Could not write storage/.app_key. Make the storage directory writable.');
        }
        @chmod($file, 0600);

        return $key;
    }

    public function crypto(): Crypto
    {
        return $this->crypto ??= new Crypto($this->key());
    }

    public function csrf(): Csrf
    {
        return $this->csrf ??= new Csrf();
    }

    public function auth(): Auth
    {
        return $this->auth ??= new Auth($this->db());
    }

    public function settings(): Settings
    {
        return $this->settings ??= new Settings($this->db(), $this->crypto());
    }

    public function rateLimiter(): RateLimiter
    {
        return $this->rateLimiter ??= new RateLimiter($this->db());
    }

    public function licenses(): LicenseService
    {
        return $this->licenseService ??= new LicenseService($this->db());
    }

    public function apiKeys(): ApiKeyService
    {
        return $this->apiKeyService ??= new ApiKeyService($this->db());
    }

    public function extensions(): ExtensionService
    {
        return $this->extensionService ??= new ExtensionService($this->storagePath('extensions.json'));
    }

    public function pterodactyl(): Pterodactyl
    {
        return $this->pterodactyl ??= new Pterodactyl($this->settings());
    }

    public function bots(): BotService
    {
        return $this->botService ??= new BotService($this->db(), $this->pterodactyl(), $this->settings());
    }

    public function installed(): bool
    {
        try {
            return (int) $this->db()->value('SELECT COUNT(*) FROM users') > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function url(string $path = ''): string
    {
        $base = trim((string) ($this->config['url'] ?? ''));

        if ($base === '') {
            if ($this->cli) {
                $base = 'http://127.0.0.1:' . (int) ($this->config['port'] ?? 4411);
            } else {
                $host = (string) ($_SERVER['HTTP_HOST'] ?? ('127.0.0.1:' . (int) ($this->config['port'] ?? 4411)));
                if (!preg_match('/^[A-Za-z0-9.\-\[\]:]+$/', $host)) {
                    $host = '127.0.0.1:' . (int) ($this->config['port'] ?? 4411);
                }
                $base = ($this->isHttps() ? 'https' : 'http') . '://' . $host;
            }
        }

        $base = rtrim($base, '/');
        if ($path === '' || $path === '/') {
            return $base . '/';
        }

        return $base . '/' . ltrim($path, '/');
    }

    public function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/') . '?v=' . rawurlencode((string) $this->config['version']);
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath . '/storage/' . ltrim($path, '/');
    }

    public function isDebug(): bool
    {
        return (bool) ($this->config['debug'] ?? false);
    }

    public function log(string $message, array $context = []): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if ($context !== []) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        @file_put_contents($this->storagePath('logs/app.log'), $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function setApiKey(?array $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function currentApiKey(): ?array
    {
        return $this->apiKey;
    }

    public function plans(): array
    {
        return (array) ($this->config['plans'] ?? []);
    }
}
