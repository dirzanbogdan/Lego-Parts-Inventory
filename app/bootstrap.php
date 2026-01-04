<?php
declare(strict_types=1);
namespace App;
use App\Core\Security;
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});
$localEnv = __DIR__ . '/config/local_env.php';
if (file_exists($localEnv)) require $localEnv;
require __DIR__ . '/config/config.php';
Security::initSession();
