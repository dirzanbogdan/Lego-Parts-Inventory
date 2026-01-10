<?php

namespace App\Services;

use App\Core\Config;
use PDO;

class ImportService {
    private PDO $pdo;
    private string $basePath;

    public function __construct() {
        $this->pdo = Config::db();
        $this->basePath = __DIR__ . '/../../parts and sets/csv/';
    }

    public function importAll() {
        // Order matters for Foreign Keys
        $this->importFile('themes.csv', 'themes', ['id', 'name', 'parent_id']);
        $this->importFile('colors.csv', 'colors', ['id', 'name', 'rgb', 'is_trans']);
        $this->importFile('part_categories.csv', 'part_categories', ['id', 'name']);
        $this->importFile('parts.csv', 'parts', ['part_num', 'name', 'part_cat_id', 'part_material']);
        $this->importFile('part_relationships.csv', 'part_relationships', ['rel_type', 'child_part_num', 'parent_part_num']);
        $this->importFile('sets.csv', 'sets', ['set_num', 'name', 'year', 'theme_id', 'num_parts', 'img_url']);
        $this->importFile('minifigs.csv', 'minifigs', ['fig_num', 'name', 'num_parts']);
        $this->importFile('inventories.csv', 'inventories', ['id', 'version', 'set_num']);
        $this->importFile('inventory_parts.csv', 'inventory_parts', ['inventory_id', 'part_num', 'color_id', 'quantity', 'is_spare', 'img_url']);
        $this->importFile('inventory_minifigs.csv', 'inventory_minifigs', ['inventory_id', 'fig_num', 'quantity']);
        $this->importFile('inventory_sets.csv', 'inventory_sets', ['inventory_id', 'set_num', 'quantity']);
        $this->importFile('elements.csv', 'elements', ['element_id', 'part_num', 'color_id']);
    }

    private function importFile(string $filename, string $table, array $columns) {
        $path = $this->basePath . $filename;
        if (!file_exists($path)) {
            echo "Skipping $filename (not found)\n";
            return;
        }

        echo "Importing $filename into $table...\n";
        
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle); // Skip header
        
        // Disable FK checks for speed and circular dependencies (if any)
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $this->pdo->beginTransaction();

        $batchSize = 1000;
        $batch = [];
        $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
        $sql = "INSERT IGNORE INTO $table (" . implode(',', $columns) . ") VALUES ";

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            // Map row to columns based on CSV header (assuming standard order or matching count)
            // Rebrickable CSVs usually match the order, but we should be careful.
            // For now, assuming strict column order mapping.
            
            // Handle booleans (t/f to 1/0)
            foreach ($row as &$val) {
                if ($val === 't' || $val === 'True') $val = 1;
                if ($val === 'f' || $val === 'False') $val = 0;
                if ($val === '') $val = null;
            }

            // Ensure row has correct number of columns
            if (count($row) != count($columns)) {
                // Try to pad or slice? Better to skip or log.
                // Rebrickable CSVs are usually well-formed.
                continue;
            }

            $batch = array_merge($batch, $row);
            $count++;

            if ($count % $batchSize === 0) {
                $stmt = $this->pdo->prepare($sql . implode(',', array_fill(0, $batchSize, $placeholders)));
                $stmt->execute($batch);
                $batch = [];
                echo ".";
            }
        }

        // Insert remaining
        if (!empty($batch)) {
            $remaining = count($batch) / count($columns);
            $stmt = $this->pdo->prepare($sql . implode(',', array_fill(0, $remaining, $placeholders)));
            $stmt->execute($batch);
        }

        $this->pdo->commit();
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        fclose($handle);
        echo "\nDone $table.\n";
    }
}
