<?php
declare(strict_types=1);
$secret = getenv('UPDATE_SECRET') ?: '';
if ($secret && (!isset($_GET['s']) || $_GET['s'] !== $secret)) {
    http_response_code(403);
    echo 'forbidden';
    exit;
}
function run(string $cmd): array {
    $out = [];
    $ret = 0;
    exec($cmd . ' 2>&1', $out, $ret);
    return [$ret, implode("\n", $out)];
}
[$code1, $out1] = run('git pull');
require __DIR__ . '/../app/bootstrap.php';
$sql = file_get_contents(__DIR__ . '/../sql/schema.sql');
$pdo = \App\Config\Config::db();
$pdo->exec($sql);
echo json_encode(['git' => ['code' => $code1, 'out' => $out1], 'migrate' => 'ok']);
