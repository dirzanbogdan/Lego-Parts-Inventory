<?php
// Import data from CSV files
header('Content-Type: text/plain');

// Increase time limit for large imports
set_time_limit(600);
ini_set('memory_limit', '512M');

echo "Starting data import process...\n";

require_once __DIR__ . '/../app/Core/Config.php';
require_once __DIR__ . '/../app/autoload.php';

use App\Services\ImportService;

try {
    // Check if CSV directory exists
    $csvPath = __DIR__ . '/../parts and sets/csv/';
    echo "Checking CSV directory: $csvPath\n";
    
    if (!is_dir($csvPath)) {
        throw new Exception("CSV directory not found at: $csvPath");
    }
    
    $files = glob($csvPath . '*.csv');
    echo "Found " . count($files) . " CSV files.\n";
    
    if (count($files) === 0) {
        echo "Available files in directory:\n";
        $allFiles = scandir($csvPath);
        print_r($allFiles);
        throw new Exception("No CSV files found to import.");
    }
    
    $importer = new ImportService();
    $importer->importAll();
    
    echo "Import completed successfully.\n";
    
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
