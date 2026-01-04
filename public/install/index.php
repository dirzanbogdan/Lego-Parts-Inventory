<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';
use App\Core\Migrator;
use App\Models\User;
function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    $items = array_diff(scandir($dir) ?: [], ['.', '..']);
    foreach ($items as $i) {
        $path = $dir . DIRECTORY_SEPARATOR . $i;
        if (is_dir($path)) rrmdir($path);
        else @unlink($path);
    }
    @rmdir($dir);
}
$envExists = file_exists(__DIR__ . '/../../app/Config/local_env.php');
$installed = false;
if ($envExists) {
    try {
        $pdo = \App\Config\Config::db();
        $installed = (bool)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } catch (\Throwable $e) {
        $installed = false;
    }
}
if ($installed) {
    http_response_code(403);
    echo 'Aplicatia este deja instalata.';
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $baseUrl = trim($_POST['base_url'] ?? '');
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $adminUser = trim($_POST['admin_user'] ?? '');
    $adminPass = trim($_POST['admin_pass'] ?? '');
    $updateSecret = trim($_POST['update_secret'] ?? '');
    $blKey = trim($_POST['bl_key'] ?? '');
    $blSecret = trim($_POST['bl_secret'] ?? '');
    $blToken = trim($_POST['bl_token'] ?? '');
    $blTokenSecret = trim($_POST['bl_token_secret'] ?? '');
    $seedColors = isset($_POST['seed_colors']);
    $envFile = __DIR__ . '/../../app/Config/local_env.php';
    $env = [];
    if ($baseUrl) $env[] = "putenv('BASE_URL=$baseUrl');";
    if ($dbHost) $env[] = "putenv('DB_HOST=$dbHost');";
    if ($dbName) $env[] = "putenv('DB_NAME=$dbName');";
    if ($dbUser) $env[] = "putenv('DB_USER=$dbUser');";
    $env[] = "putenv('DB_PASS=" . str_replace("'", "\\'", $dbPass) . "');";
    if ($updateSecret) $env[] = "putenv('UPDATE_SECRET=$updateSecret');";
    if ($blKey) $env[] = "putenv('BRICKLINK_CONSUMER_KEY=$blKey');";
    if ($blSecret) $env[] = "putenv('BRICKLINK_CONSUMER_SECRET=$blSecret');";
    if ($blToken) $env[] = "putenv('BRICKLINK_TOKEN=$blToken');";
    if ($blTokenSecret) $env[] = "putenv('BRICKLINK_TOKEN_SECRET=$blTokenSecret');";
    file_put_contents($envFile, "<?php\n" . implode("\n", $env) . "\n");
    Migrator::applyAll();
    if ($seedColors) {
        $pdo = \App\Config\Config::db();
        $colors = [
            ['Black','0'],['White','15'],['Red','5'],['Blue','7'],['Yellow','3'],
            ['Light Bluish Gray','86'],['Dark Bluish Gray','85'],['Green','2'],
        ];
        foreach ($colors as [$n,$c]) {
            $st = $pdo->prepare('INSERT INTO colors (color_name, color_code) VALUES (?,?) ON DUPLICATE KEY UPDATE color_code=?');
            $st->execute([$n,$c,$c]);
        }
    }
    if ($adminUser && $adminPass) {
        User::create($adminUser, $adminPass, 'admin');
    }
    rrmdir(__DIR__);
    header('Location: /');
    exit;
}
?><!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalare - Lego Parts Inventory</title>
<style>
body{font-family:system-ui,Arial,sans-serif;background:#f7f7f9;color:#222}
.container{max-width:700px;margin:40px auto;background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px}
label{display:block;margin-top:8px}
input,select{width:100%;padding:8px;margin-top:4px}
button{margin-top:12px;background:#2563eb;color:#fff;border:none;padding:10px 12px;border-radius:4px;cursor:pointer;width:100%}
</style>
</head>
<body>
<div class="container">
  <h2>Instalare initiala</h2>
  <form method="post">
    <label>BASE_URL</label>
    <input type="text" name="base_url" value="https://lpi.e-bm.eu" required>
    <label>DB Host</label>
    <input type="text" name="db_host" value="localhost" required>
    <label>DB Name</label>
    <input type="text" name="db_name" value="lego_inventory" required>
    <label>DB User</label>
    <input type="text" name="db_user" value="root" required>
    <label>DB Pass</label>
    <input type="password" name="db_pass" value="">
    <label>Admin Username</label>
    <input type="text" name="admin_user" value="admin" required>
    <label>Admin Parola</label>
    <input type="password" name="admin_pass" required>
    <label>UPDATE_SECRET (optional)</label>
    <input type="text" name="update_secret">
    <label>BrickLink Consumer Key (optional)</label>
    <input type="text" name="bl_key">
    <label>BrickLink Consumer Secret (optional)</label>
    <input type="text" name="bl_secret">
    <label>BrickLink Token (optional)</label>
    <input type="text" name="bl_token">
    <label>BrickLink Token Secret (optional)</label>
    <input type="text" name="bl_token_secret">
    <label><input type="checkbox" name="seed_colors" value="1"> Seed culori de baza</label>
    <button type="submit">Instaleaza</button>
  </form>
</div>
</body>
</html>
