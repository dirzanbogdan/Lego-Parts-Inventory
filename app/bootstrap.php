<?php
declare(strict_types=1);
namespace App;
use App\Core\Security;

require_once __DIR__ . '/autoload.php';

$localEnv = __DIR__ . '/Config/local_env.php';
if (file_exists($localEnv)) require $localEnv;
// Config class is autoloaded when needed
Security::initSession();
