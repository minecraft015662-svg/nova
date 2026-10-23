<?php

declare(strict_types=1);

use Nexa\Controllers\ApiController;
use Nexa\Router;

/** @var Router $router */

$router->get('/api/v1/health', [ApiController::class, 'health']);

$router->post('/api/v1/licenses/validate', [ApiController::class, 'validateLicense']);
$router->post('/api/v1/licenses/activate', [ApiController::class, 'activateLicense']);
$router->post('/api/v1/licenses/deactivate', [ApiController::class, 'deactivateLicense']);

$router->get('/api/v1/licenses', [ApiController::class, 'listLicenses'], ['api:licenses:read']);
$router->post('/api/v1/licenses', [ApiController::class, 'createLicense'], ['api:licenses:write']);
$router->post('/api/v1/licenses/{id}/revoke', [ApiController::class, 'revokeLicense'], ['api:licenses:write']);

$router->get('/api/v1/extensions', [ApiController::class, 'listExtensions'], ['api:extensions:read']);

$router->get('/api/v1/bots', [ApiController::class, 'listBots'], ['api:bots:read']);
$router->post('/api/v1/bots', [ApiController::class, 'createBot'], ['api:bots:write']);
$router->get('/api/v1/bots/{id}', [ApiController::class, 'showBot'], ['api:bots:read']);
$router->post('/api/v1/bots/{id}/power', [ApiController::class, 'powerBot'], ['api:bots:write']);
$router->delete('/api/v1/bots/{id}', [ApiController::class, 'deleteBot'], ['api:bots:write']);
