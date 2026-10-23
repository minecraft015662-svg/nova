<?php

declare(strict_types=1);

namespace Nexa\Controllers;

use Nexa\App;

final class PublicController
{
    public static function landing(): string
    {
        $app = app();
        $extensions = $app->extensions()->all();
        $enabled = array_values(array_filter($extensions, static fn (array $entry): bool => ($entry['status'] ?? '') === 'enabled'));

        return view('landing', [
            'title' => 'Nexa Suite — licenses, API keys and bot hosting',
            'extensions' => $enabled,
            'installed' => $app->installed(),
            'plans' => $app->plans(),
            'active' => 'home',
        ], 'site');
    }

    public static function docs(): string
    {
        $app = app();

        return view('docs', [
            'title' => 'Documentation',
            'active' => 'docs',
            'baseUrl' => rtrim($app->url('/'), '/'),
            'plans' => $app->plans(),
        ], 'site');
    }

    public static function loginForm(): string
    {
        if (!app()->installed()) {
            redirect('/setup');
        }

        return view('login', [
            'title' => 'Sign in',
            'active' => 'login',
        ], 'auth');
    }

    public static function login(): string
    {
        $app = app();
        $app->rateLimiter()->enforce('login:' . ip_hash(), 8, 600, 'Too many sign-in attempts. Wait 10 minutes and try again.');

        $email = strtolower((string) post('email'));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            flash('error', 'Enter your email address and password.');
            flash_old(['email' => $email]);
            redirect('/login');
        }

        if (!$app->auth()->attempt($email, $password)) {
            flash('error', 'The email address or password is incorrect.');
            flash_old(['email' => $email]);
            redirect('/login');
        }

        flash('success', 'Welcome back to Nexa Suite.');
        redirect('/admin');
    }

    public static function logout(): string
    {
        app()->auth()->logout();
        flash('success', 'You have been signed out.');
        redirect('/login');
    }

    public static function setupForm(): string
    {
        $app = app();
        if ($app->installed()) {
            redirect($app->auth()->check() ? '/admin' : '/login');
        }

        $writable = is_writable($app->storagePath()) && is_writable(base_path('config'));

        return view('setup', [
            'title' => 'Set up Nexa Suite',
            'writable' => $writable,
            'requirements' => self::requirements(),
        ], 'auth');
    }

    public static function setup(): string
    {
        $app = app();
        if ($app->installed()) {
            abort(403, 'Nexa Suite is already installed.', 'already_installed');
        }

        $app->rateLimiter()->enforce('setup:' . ip_hash(), 6, 900, 'Too many setup attempts. Try again later.');

        $siteName = trim((string) post('site_name', 'Nexa Suite')) ?: 'Nexa Suite';
        $name = trim((string) post('name'));
        $email = strtolower(trim((string) post('email')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $suiteUrl = trim((string) post('suite_url'));

        if ($name === '' || strlen($name) > 80) {
            self::setupFail('Enter your name (max 80 characters).', ['site_name' => $siteName, 'name' => $name, 'email' => $email, 'suite_url' => $suiteUrl]);
        }
        if (!is_email($email)) {
            self::setupFail('Enter a valid email address.', ['site_name' => $siteName, 'name' => $name, 'email' => $email, 'suite_url' => $suiteUrl]);
        }
        if (strlen($password) < 10) {
            self::setupFail('Use a password with at least 10 characters.', ['site_name' => $siteName, 'name' => $name, 'email' => $email, 'suite_url' => $suiteUrl]);
        }
        if ($password !== $confirmation) {
            self::setupFail('The two passwords do not match.', ['site_name' => $siteName, 'name' => $name, 'email' => $email, 'suite_url' => $suiteUrl]);
        }
        if ($suiteUrl !== '' && !filter_var($suiteUrl, FILTER_VALIDATE_URL)) {
            self::setupFail('The suite URL must look like https://panel.example.com:4411.', ['site_name' => $siteName, 'name' => $name, 'email' => $email, 'suite_url' => $suiteUrl]);
        }

        $userId = $app->db()->transaction(function () use ($app, $name, $email, $password, $siteName, $suiteUrl): int {
            if ((int) $app->db()->value('SELECT COUNT(*) FROM users') > 0) {
                abort(403, 'Nexa Suite is already installed.', 'already_installed');
            }

            $id = $app->db()->insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => $app->auth()->hashPassword($password),
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $app->settings()->setMany([
                'site_name' => $siteName,
                'suite_url' => $suiteUrl,
            ]);
            $app->extensions()->all();

            return $id;
        });

        $app->auth()->login($userId);
        flash('success', 'Nexa Suite is ready. Connect your Pterodactyl panel next.');
        redirect('/admin');

        return '';
    }

    private static function setupFail(string $message, array $old): never
    {
        flash('error', $message);
        flash_old($old);
        redirect('/setup');
    }

    public static function requirements(): array
    {
        return [
            ['label' => 'PHP 8.1 or newer', 'ok' => PHP_VERSION_ID >= 80100, 'value' => PHP_VERSION],
            ['label' => 'PDO SQLite extension', 'ok' => extension_loaded('pdo_sqlite'), 'value' => extension_loaded('pdo_sqlite') ? 'Loaded' : 'Missing'],
            ['label' => 'OpenSSL extension', 'ok' => extension_loaded('openssl'), 'value' => extension_loaded('openssl') ? 'Loaded' : 'Missing'],
            ['label' => 'cURL or HTTPS streams', 'ok' => extension_loaded('curl') || ini_get('allow_url_fopen'), 'value' => extension_loaded('curl') ? 'cURL' : 'Streams'],
            ['label' => 'Storage writable', 'ok' => is_writable(storage_path()), 'value' => is_writable(storage_path()) ? 'Writable' : 'Not writable'],
            ['label' => 'Config writable', 'ok' => is_writable(base_path('config')), 'value' => is_writable(base_path('config')) ? 'Writable' : 'Not writable'],
        ];
    }
}
