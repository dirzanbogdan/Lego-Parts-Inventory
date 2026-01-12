<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\SetsController;
use App\Controllers\PartsController;
use App\Controllers\ThemesController;
use App\Controllers\UpdateController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/search', [HomeController::class, 'search']);
$router->get('/sets', [SetsController::class, 'index']);
$router->get('/sets/{id}', [SetsController::class, 'show']);
$router->get('/parts', [PartsController::class, 'index']);
$router->get('/parts/{id}', [PartsController::class, 'show']);
$router->post('/parts/update', [PartsController::class, 'update']);
$router->get('/themes', [ThemesController::class, 'index']);

$router->get('/admin/update', [UpdateController::class, 'page']);
$router->post('/admin/update/backup', [UpdateController::class, 'backup']);
$router->post('/admin/update/pull', [UpdateController::class, 'gitPull']);
$router->post('/admin/update/scan-images', [UpdateController::class, 'scanImages']);
$router->post('/admin/update/image-stats', [UpdateController::class, 'imageStats']);
$router->get('/admin/update/image-stats', [UpdateController::class, 'redirectBack']);
$router->post('/admin/update/export-debug', [UpdateController::class, 'exportDebug']);
$router->post('/admin/update/download-images', [UpdateController::class, 'downloadMissingImages']);
$router->post('/admin/update/populate-theme-urls', [UpdateController::class, 'populateThemeUrls']);
$router->get('/admin/update/download-images', [UpdateController::class, 'redirectBack']);

$router->resolve();
