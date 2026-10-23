<?php

declare(strict_types=1);

namespace Nexa;

final class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function delete(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, array|callable $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $path): string
    {
        $method = strtoupper($method);
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '/';
        }

        $pathMatched = false;

        foreach ($this->routes as $route) {
            $pattern = $this->pattern($route['path']);
            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            $pathMatched = true;
            if ($route['method'] !== $method) {
                continue;
            }

            array_shift($matches);
            $params = array_map(static fn ($value): string => rawurldecode((string) $value), $matches);

            foreach ($route['middleware'] as $middleware) {
                $this->runMiddleware($middleware, $method);
            }

            $handler = $route['handler'];
            if (!is_callable($handler)) {
                throw new \RuntimeException('Route handler is not callable.');
            }

            $result = $handler(...$params);

            return is_string($result) ? $result : '';
        }

        if ($pathMatched) {
            abort(405, 'That request method is not allowed.', 'method_not_allowed');
        }

        abort(404, 'The page you are looking for does not exist.', 'not_found');
    }

    private function pattern(string $path): string
    {
        $parts = preg_split('/\{[A-Za-z_][A-Za-z0-9_]*\}/', $path);
        $quoted = implode('([^/]+)', array_map(
            static fn (string $part): string => preg_quote($part, '#'),
            $parts === false ? [$path] : $parts
        ));

        return '#^' . $quoted . '$#';
    }

    private function runMiddleware(string $middleware, string $method): void
    {
        if ($middleware === 'auth') {
            if (!app()->auth()->check()) {
                if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                    abort(401, 'Authentication required.', 'unauthorized');
                }
                flash('error', 'Sign in to open the Nexa console.');
                redirect('/login');
            }
            return;
        }

        if ($middleware === 'guest') {
            if (app()->auth()->check()) {
                redirect('/admin');
            }
            return;
        }

        if ($middleware === 'installed') {
            if (!app()->installed()) {
                redirect('/setup');
            }
            return;
        }

        if ($middleware === 'csrf') {
            if ($method === 'POST' && !app()->csrf()->verify($_POST['_token'] ?? null)) {
                abort(419, 'Your session token expired. Go back, refresh the page and try again.', 'csrf_mismatch');
            }
            return;
        }

        if (str_starts_with($middleware, 'api:')) {
            $scope = substr($middleware, 4);
            $key = app()->apiKeys()->authenticate(bearer_token());
            if ($key === null) {
                abort(401, 'A valid Nexa API key is required. Send it as a Bearer token.', 'unauthorized');
            }
            if ($scope !== '' && !app()->apiKeys()->allows($key, $scope)) {
                abort(403, 'This API key does not have the "' . $scope . '" scope.', 'forbidden');
            }
            app()->setApiKey($key);
            return;
        }

        throw new \RuntimeException('Unknown middleware "' . $middleware . '".');
    }
}
