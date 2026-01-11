<?php
require 'vendor/autoload.php';
use App\Core\Config;

$pdo = Config::db();

// Check schema
$stmt = $pdo->query("PRAGMA table_info(parts)");
echo "Schema:\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['name'] . "\n";
}

// Check parts
$parts = ['u8004b', 'u8004c', 'x164c01'];
$placeholders = implode(',', array_fill(0, count($parts), '?'));
$stmt = $pdo->prepare("SELECT part_num, part_cat_id FROM parts WHERE part_num IN ($placeholders)");
$stmt->execute($parts);
echo "\nParts Data:\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "{$row['part_num']}: {$row['part_cat_id']}\n";
}
