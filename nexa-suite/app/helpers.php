<?php

declare(strict_types=1);

use Nexa\App;
use Nexa\HttpException;

function app(): App
{
    return App::instance();
}

function base_path(string $path = ''): string
{
    return NEXA_BASE . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function storage_path(string $path = ''): string
{
    return app()->storagePath($path);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return app()->url($path);
}

function asset(string $path): string
{
    return app()->asset($path);
}

function config(string $key, mixed $default = null): mixed
{
    return app()->config[$key] ?? $default;
}

function csrf_token(): string
{
    return app()->csrf()->token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $items;
}

function flash_old(array $input): void
{
    unset($input['_token'], $input['password'], $input['password_confirmation']);
    $_SESSION['_old'] = $input;
}

function old(string $key, mixed $default = ''): mixed
{
    $old = $GLOBALS['nexa_old'] ?? [];

    return array_key_exists($key, $old) ? $old[$key] : $default;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function back(string $fallback = '/admin'): never
{
    redirect($fallback);
}

function view(string $template, array $data = [], string $layout = 'site'): string
{
    $GLOBALS['nexa_old'] = $_SESSION['_old'] ?? [];
    unset($_SESSION['_old']);

    $app = app();
    $data += ['title' => 'Nexa Suite', 'active' => '', 'meta_description' => 'Nexa Suite — licenses, API keys, extension catalog and Pterodactyl bot hosting in one control deck.'];
    extract($data, EXTR_SKIP);

    ob_start();
    require base_path('app/Views/' . $template . '.php');
    $content = (string) ob_get_clean();

    ob_start();
    require base_path('app/Views/layouts/' . $layout . '.php');

    return (string) ob_get_clean();
}

function json_response(array $payload, int $status = 200): string
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');

    return (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function abort(int $status, string $message, string $code = 'error'): never
{
    throw new HttpException($status, $message, $code);
}

function post(string $key, mixed $default = ''): mixed
{
    $value = $_POST[$key] ?? $default;

    return is_string($value) ? trim($value) : $value;
}

function query(string $key, mixed $default = ''): mixed
{
    $value = $_GET[$key] ?? $default;

    return is_string($value) ? trim($value) : $value;
}

function input_int(string $key, int $default = 0, int $min = 0, int $max = PHP_INT_MAX): int
{
    $value = filter_var($_POST[$key] ?? $default, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max],
    ]);

    return $value === false ? $default : (int) $value;
}

function is_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function client_ip(): string
{
    $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    if ($forwarded !== '') {
        $first = trim(explode(',', $forwarded)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }

    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function ip_hash(): string
{
    return substr(hash('sha256', client_ip() . '|' . app()->key()), 0, 32);
}

function nice_date(?string $date): string
{
    if ($date === null || $date === '') {
        return 'Never';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? '—' : date('d M Y', $timestamp);
}

function nice_datetime(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? '—' : date('d M Y, H:i', $timestamp);
}

function time_ago(?string $date): string
{
    if ($date === null || $date === '') {
        return 'never';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 'never';
    }

    $seconds = time() - $timestamp;
    if ($seconds < 60) {
        return 'just now';
    }
    if ($seconds < 3600) {
        $minutes = (int) floor($seconds / 60);
        return $minutes . ' min ago';
    }
    if ($seconds < 86400) {
        $hours = (int) floor($seconds / 3600);
        return $hours . ' hr ago';
    }
    if ($seconds < 2592000) {
        $days = (int) floor($seconds / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }

    return date('d M Y', $timestamp);
}

function days_left(?string $expiresAt): ?int
{
    if ($expiresAt === null || $expiresAt === '') {
        return null;
    }

    $timestamp = strtotime($expiresAt);
    if ($timestamp === false) {
        return null;
    }

    return (int) ceil(($timestamp - time()) / 86400);
}

function license_plan(string $plan): array
{
    $plans = app()->plans();
    $key = strtolower($plan);

    return $plans[$key] ?? ['name' => ucfirst($plan === '' ? 'Custom' : $plan), 'activations' => 1, 'label' => 'Custom plan'];
}

function mask_secret(string $value): string
{
    if ($value === '') {
        return 'Not set';
    }

    if (strlen($value) <= 8) {
        return '••••••••';
    }

    return substr($value, 0, 7) . '••••••••' . substr($value, -4);
}

function slugify(string $value): string
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? '', '-'));
    return $slug === '' ? 'extension' : substr($slug, 0, 48);
}

function random_token(int $bytes = 24): string
{
    return bin2hex(random_bytes($bytes));
}

function state_class(string $state): string
{
    return match (strtolower($state)) {
        'running', 'online', 'active', 'enabled' => 'is-good',
        'starting', 'installing', 'restarting', 'stopping' => 'is-warn',
        'suspended', 'revoked', 'expired', 'offline', 'disabled' => 'is-bad',
        default => 'is-neutral',
    };
}

function nav_class(string $active, string $key): string
{
    return $active === $key ? 'is-active' : '';
}

function bearer_token(): ?string
{
    $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if ($header !== '' && preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return trim($matches[1]);
    }

    $alt = (string) ($_SERVER['HTTP_X_NEXA_KEY'] ?? '');
    if ($alt !== '') {
        return trim($alt);
    }

    return null;
}

function selected(mixed $value, mixed $expected): string
{
    return (string) $value === (string) $expected ? ' selected' : '';
}

function checked(bool $condition): string
{
    return $condition ? ' checked' : '';
}
