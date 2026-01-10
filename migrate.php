<?php
require_once __DIR__ . '/app/autoload.php';

use App\Core\Config;

$pdo = Config::db();

// Ensure migrations table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Check if 'migration' column exists (fix for old table schema)
try {
    $pdo->query("SELECT migration FROM migrations LIMIT 1");
} catch (PDOException $e) {
    // Drop table if column doesn't match (simplest fix for dev env)
    $pdo->exec("DROP TABLE migrations");
    $pdo->exec("CREATE TABLE migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

$files = glob(__DIR__ . '/database/migrations/*.sql');
sort($files);

$executed = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

foreach ($files as $file) {
    $migration = basename($file);
    if (in_array($migration, $executed)) {
        continue;
    }

    echo "Running migration: $migration\n";
    $sql = file_get_contents($file);
    
    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migration]);
        echo "Done.\n";
    } catch (PDOException $e) {
        echo "Error in $migration: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "All migrations are up to date.\n";
