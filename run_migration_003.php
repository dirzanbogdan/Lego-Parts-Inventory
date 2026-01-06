<?php
require_once __DIR__ . '/app/Config/Config.php';
use App\Config\Config;

$pdo = Config::db();
$sql = file_get_contents(__DIR__ . '/database/migrations/003_v2_features.sql');

try {
    $pdo->exec($sql);
    echo "Migration 003 executed successfully.\n";
} catch (PDOException $e) {
    echo "Error executing migration: " . $e->getMessage() . "\n";
}
