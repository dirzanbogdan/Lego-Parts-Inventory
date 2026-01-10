<?php
// Fix for database schema issues - FULL RESET
header('Content-Type: text/plain');

echo "Starting database RESET and migration...\n";

// Adjust path to reach the root from public/
require_once __DIR__ . '/../app/Core/Config.php';
require_once __DIR__ . '/../app/autoload.php';

use App\Core\Config;

try {
    $pdo = Config::db();
    
    echo "Disabling Foreign Key Checks...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    echo "Fetching all tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found to drop.\n";
    } else {
        foreach ($tables as $table) {
            echo "Dropping table: $table\n";
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
    }
    
    echo "Enabling Foreign Key Checks...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Database cleared. Starting migration process...\n";
    
    // Include the root migrate script
    // We need to chdir to root so that migrate.php finds relative paths (like database/migrations) correctly if it uses relative paths
    chdir(__DIR__ . '/..');
    require_once 'migrate.php';
    
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
