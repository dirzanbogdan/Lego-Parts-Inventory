<?php
require_once __DIR__ . '/app/autoload.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\SetsController;
use App\Controllers\PartsController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/search', [HomeController::class, 'search']);
$router->get('/sets', [SetsController::class, 'index']);
$router->get('/sets/{id}', [SetsController::class, 'view']);
$router->get('/parts/{id}', [PartsController::class, 'view']);

$router->resolve();
