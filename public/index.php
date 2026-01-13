<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\SetsController;
use App\Controllers\PartsController;
use App\Controllers\ThemesController;
use App\Controllers\UpdateController;
use App\Controllers\MyController;
use App\Controllers\AuthController;

$router = new Router();

// Access control: require login for all non-auth routes; restrict admin update
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!in_array($path, ['/login', '/register', '/logout'])) {
    \App\Core\Security::requireLogin();
}
if (strpos($path, '/admin/update') === 0) {
    \App\Core\Security::requireRole('admin');
}

$router->get('/', [HomeController::class, 'index']);
$router->get('/search', [HomeController::class, 'search']);
$router->get('/api/search', [HomeController::class, 'apiSearch']);
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

// My collections
$router->post('/my/sets/add', [MyController::class, 'addSet']);
$router->post('/my/sets/update', [MyController::class, 'updateSet']);
$router->post('/my/sets/remove', [MyController::class, 'removeSet']);
$router->get('/my/sets', [MyController::class, 'mySets']);
$router->get('/my/parts', [MyController::class, 'myParts']);

// Auth
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->resolve();
