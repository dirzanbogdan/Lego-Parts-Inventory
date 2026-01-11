<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\SetsController;
use App\Controllers\PartsController;
use App\Controllers\UpdateController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/search', [HomeController::class, 'search']);
$router->get('/sets', [SetsController::class, 'index']);
$router->get('/sets/{id}', [SetsController::class, 'show']);
$router->get('/parts/{id}', [PartsController::class, 'show']);
$router->post('/parts/update', [PartsController::class, 'update']);

$router->get('/admin/update', [UpdateController::class, 'page']);
$router->post('/admin/update/backup', [UpdateController::class, 'backup']);
$router->post('/admin/update/apply', [UpdateController::class, 'apply']);
$router->post('/admin/update/cleardb', [UpdateController::class, 'clearDb']);
$router->post('/admin/update/schema', [UpdateController::class, 'verifySchema']);

$router->resolve();
