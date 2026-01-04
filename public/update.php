<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
use App\Core\Migrator;
use App\Core\Security;
header('Content-Type: application/json');
$secret = getenv('UPDATE_SECRET') ?: '';
if ($secret) {
    $provided = $_GET['secret'] ?? '';
    if (!hash_equals($secret, $provided)) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }
} else {
    $user = $_SESSION['user'] ?? null;
    if (!$user || ($user['role'] ?? 'user') !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden']);
        exit;
    }
}
$result = ['git' => null, 'migrations' => null];
if (function_exists('shell_exec')) {
    $out = @shell_exec('git pull 2>&1');
    $result['git'] = $out ? trim($out) : 'git not available';
} else {
    $result['git'] = 'shell_exec disabled';
}
$result['migrations'] = Migrator::applyAll();
echo json_encode($result);
