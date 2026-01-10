<?php
// Fix for database schema issues
header('Content-Type: text/plain');

echo "Starting database migration...\n";

try {
    // Include the root migrate script
    require_once __DIR__ . '/../migrate.php';
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
