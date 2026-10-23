<?php

declare(strict_types=1);

define('NEXA_SUITE', true);
define('NEXA_BASE', dirname(__DIR__));
define('NEXA_VERSION', '1.0.0');

spl_autoload_register(static function (string $class): void {
    $prefix = 'Nexa\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = NEXA_BASE . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require NEXA_BASE . '/app/helpers.php';
require NEXA_BASE . '/app/icons.php';
