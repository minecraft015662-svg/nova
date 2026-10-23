<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use Nexa\App;
use Nexa\HttpException;
use Nexa\Router;

$path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
$isApi = str_starts_with($path, '/api/');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('Cross-Origin-Opener-Policy: same-origin');

try {
    $app = new App(false, !$isApi);

    if ($app->isHttps()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    $router = new Router();
    require NEXA_BASE . '/routes/web.php';
    require NEXA_BASE . '/routes/api.php';

    echo $router->dispatch((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $path);
} catch (HttpException $exception) {
    http_response_code($exception->status);

    if ($isApi) {
        echo json_response([
            'ok' => false,
            'error' => $exception->getMessage(),
            'code' => $exception->errorCode,
        ], $exception->status);
        exit;
    }

    try {
        echo view('errors/error', [
            'title' => $exception->status . ' · Nexa Suite',
            'status' => $exception->status,
            'message' => $exception->getMessage(),
        ], 'site');
    } catch (\Throwable) {
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Nexa Suite</title></head><body style="font-family:system-ui;background:#08080d;color:#f5f5f7;padding:48px"><h1>' . e((string) $exception->status) . '</h1><p>' . e($exception->getMessage()) . '</p><p><a style="color:#fb7185" href="/">Return to Nexa Suite</a></p></body></html>';
    }
} catch (\Throwable $exception) {
    if (isset($app) && $app instanceof App) {
        $app->log('Unhandled exception: ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }

    http_response_code(500);

    if ($isApi) {
        echo json_response([
            'ok' => false,
            'error' => 'Internal server error.',
            'code' => 'server_error',
        ], 500);
        exit;
    }

    $debug = isset($app) && $app instanceof App && $app->isDebug();
    try {
        echo view('errors/error', [
            'title' => '500 · Nexa Suite',
            'status' => 500,
            'message' => $debug ? $exception->getMessage() : 'Something went wrong. Check storage/logs/app.log for details.',
            'trace' => $debug ? $exception->getTraceAsString() : null,
        ], 'site');
    } catch (\Throwable) {
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Nexa Suite</title></head><body style="font-family:system-ui;background:#08080d;color:#f5f5f7;padding:48px"><h1>500</h1><p>Nexa Suite could not start. Make sure storage/ and config/ are writable.</p></body></html>';
    }
}
