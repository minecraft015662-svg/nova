<?php

declare(strict_types=1);

use Nexa\Controllers\AdminController;
use Nexa\Controllers\PublicController;
use Nexa\Router;

/** @var Router $router */

$router->get('/', [PublicController::class, 'landing']);
$router->get('/docs', [PublicController::class, 'docs']);
$router->get('/setup', [PublicController::class, 'setupForm']);
$router->post('/setup', [PublicController::class, 'setup'], ['csrf']);
$router->get('/login', [PublicController::class, 'loginForm'], ['guest', 'installed']);
$router->post('/login', [PublicController::class, 'login'], ['guest', 'installed', 'csrf']);
$router->post('/logout', [PublicController::class, 'logout'], ['auth', 'csrf']);

$router->get('/admin', [AdminController::class, 'overview'], ['auth']);
$router->get('/admin/licenses', [AdminController::class, 'licenses'], ['auth']);
$router->post('/admin/licenses', [AdminController::class, 'createLicense'], ['auth', 'csrf']);
$router->post('/admin/licenses/{id}/revoke', [AdminController::class, 'revokeLicense'], ['auth', 'csrf']);
$router->post('/admin/licenses/{id}/restore', [AdminController::class, 'restoreLicense'], ['auth', 'csrf']);
$router->post('/admin/licenses/{id}/regenerate', [AdminController::class, 'regenerateLicense'], ['auth', 'csrf']);

$router->get('/admin/api-keys', [AdminController::class, 'apiKeys'], ['auth']);
$router->post('/admin/api-keys', [AdminController::class, 'createApiKey'], ['auth', 'csrf']);
$router->post('/admin/api-keys/{id}/revoke', [AdminController::class, 'revokeApiKey'], ['auth', 'csrf']);

$router->get('/admin/extensions', [AdminController::class, 'extensions'], ['auth']);
$router->post('/admin/extensions/toggle', [AdminController::class, 'toggleExtension'], ['auth', 'csrf']);
$router->post('/admin/extensions/add', [AdminController::class, 'addExtension'], ['auth', 'csrf']);

$router->get('/admin/bots', [AdminController::class, 'bots'], ['auth']);
$router->post('/admin/bots', [AdminController::class, 'createBot'], ['auth', 'csrf']);
$router->post('/admin/bots/{id}/power', [AdminController::class, 'powerBot'], ['auth', 'csrf']);
$router->post('/admin/bots/{id}/delete', [AdminController::class, 'deleteBot'], ['auth', 'csrf']);

$router->get('/admin/settings', [AdminController::class, 'settings'], ['auth']);
$router->post('/admin/settings', [AdminController::class, 'saveSettings'], ['auth', 'csrf']);
$router->post('/admin/settings/test', [AdminController::class, 'testPanel'], ['auth', 'csrf']);

$router->get('/admin/account', [AdminController::class, 'account'], ['auth']);
$router->post('/admin/account', [AdminController::class, 'updateAccount'], ['auth', 'csrf']);
$router->get('/admin/cli', [AdminController::class, 'cli'], ['auth']);
