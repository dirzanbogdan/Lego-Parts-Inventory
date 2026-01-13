<?php

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Config;

// Options
$options = getopt("t:", ["type:"]);
$type = $options['type'] ?? $options['t'] ?? 'parts'; // Default to parts

if (!in_array($type, ['parts', 'sets', 'themes'])) {
    die("Invalid type. Use --type=parts|sets|themes\n");
}

echo "Starting download for: $type\n";

$pdo = Config::db();
$targetDir = __DIR__ . '/../public/images/' . $type;

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Ensure columns exist (just in case)
ensureColumnExists($pdo, 'parts', 'img_url');
ensureColumnExists($pdo, 'sets', 'img_url');
ensureColumnExists($pdo, 'themes', 'img_url');
ensureColumnExists($pdo, 'inventory_parts', 'img_url');

// Determine source
$csvFile = __DIR__ . '/../storage/updates/' . $type . '.csv';
$csvMode = file_exists($csvFile);
$csvHandle = null;

if ($csvMode) {
    echo "Source: CSV file ($csvFile)\n";
    $csvHandle = fopen($csvFile, 'r');
    // Read header
    $header = fgetcsv($csvHandle);
    
    // Detect columns
    $idx_part = array_search('part_num', $header);
    $idx_color = array_search('color_id', $header);
    $idx_img = array_search('img_url', $header);
    
    // For sets/themes, columns might be different
    if ($type === 'sets') {
        $idx_part = array_search('set_num', $header);
        $idx_color = -1; // Not used
        $idx_img = array_search('img_url', $header);
    } elseif ($type === 'themes') {
        $idx_part = array_search('id', $header);
        $idx_color = -1;
        $idx_img = -1; // Themes usually don't have img_url in CSV? Check UpdateController logic
        // If themes CSV doesn't have img_url, we might skip. But let's assume standard logic.
    }
    
    if ($idx_part === false || ($type === 'parts' && ($idx_color === false || $idx_img === false))) {
        die("ERROR: CSV missing required columns.\n");
    }
    
    // Count lines for progress (rough estimate)
    $lineCount = 0;
    while(!feof($csvHandle)){
        $line = fgets($csvHandle);
        $lineCount++;
    }
    fseek($csvHandle, 0); // Reset
    fgetcsv($csvHandle); // Skip header again
    $total = $lineCount - 1;
    
} else {
    echo "Source: Database\n";
    // DB logic similar to UpdateController if needed, but usually we use CSV for updates.
    // If no CSV, we might want to scan DB for missing images?
    // For now, let's stick to CSV as per UpdateController logic which prefers CSV if available.
    // If no CSV, fallback to DB query logic from UpdateController?
    // UpdateController: if ($csvMode) ... else { $stmt = $pdo->query($sql); }
    
    // Let's implement DB fallback for parts/sets if they have URLs in DB but not downloaded.
    // But usually URLs come from CSV.
    // If DB has URLs, we can use them.
    
    $sql = "";
    if ($type === 'parts') {
        // Warning: This table might be huge and not have img_url for specific colors unless updated from CSV previously.
        // We probably want to process inventory_parts if we want color specific images.
        // But the CSV source is usually "inventory_parts.csv" or similar?
        // UpdateController looks for "$type.csv".
        die("Database mode not fully implemented for CLI yet. Please ensure storage/updates/$type.csv exists.\n");
    }
}

echo "Total items to process: $total\n";

$downloaded = 0;
$skipped = 0;
$failed = 0;
$counter = 0;

$updateStmt = null;
$updatePartsGenericStmt = null;

if ($type === 'sets') {
    $updateStmt = $pdo->prepare("UPDATE sets SET img_url = ? WHERE set_num = ?");
} elseif ($type === 'parts') {
    if ($csvMode) {
        $updateStmt = $pdo->prepare("UPDATE inventory_parts SET img_url = ? WHERE part_num = ? AND color_id = ?");
        $updatePartsGenericStmt = $pdo->prepare("UPDATE parts SET img_url = ? WHERE part_num = ? AND (img_url IS NULL OR img_url = '')");
    }
} elseif ($type === 'themes') {
    $updateStmt = $pdo->prepare("UPDATE themes SET img_url = ? WHERE id = ?");
}

while (true) {
    $row = null;
    if ($csvMode) {
        if (($data = fgetcsv($csvHandle)) !== false) {
            // Check columns
            if (count($data) <= max($idx_part, $idx_color, $idx_img)) {
                 continue; 
            }
            $row = [
                'id' => $data[$idx_part],
                'color_id' => ($idx_color !== false && $idx_color !== -1) ? $data[$idx_color] : null,
                'img_url' => ($idx_img !== false && $idx_img !== -1) ? $data[$idx_img] : null
            ];
        } else {
            break;
        }
    }
    
    $counter++;
    $id = $row['id'];
    $url = $row['img_url'] ?? '';
    $color_id = $row['color_id'];
    
    if (empty($url) || strpos($url, 'http') !== 0) {
         continue; 
    }
    
    // Determine extension
    $ext = 'jpg';
    if (preg_match('/\.(\w{3,4})$/', $url, $m)) {
        $ext = $m[1];
    }
    
    $safeId = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $id);
    
    if ($csvMode && $type === 'parts') {
        $filename = $safeId . '_' . $color_id . '.' . $ext;
    } else {
        $filename = $safeId . '.' . $ext;
    }
    
    $localPath = $targetDir . '/' . $filename;
    $webPath = '/images/' . $type . '/' . $filename;

    if (file_exists($localPath) && filesize($localPath) > 0) {
        // Update DB
        if ($csvMode && $type === 'parts') {
            $updateStmt->execute([$webPath, $id, $color_id]);
            if ($updatePartsGenericStmt) {
                $updatePartsGenericStmt->execute([$webPath, $id]);
            }
        } else {
            $updateStmt->execute([$webPath, $id]);
        }
        $skipped++;
    } else {
        // Rate limiting
        if ($downloaded > 0 && $downloaded % 50 === 0) {
            $pause = rand(1, 5);
            echo "[$downloaded downloaded] Pausing for {$pause}s...\n";
            sleep($pause);
        }
        
        usleep(100000); // 0.1s
        
        // Download
        $content = downloadUrl($url);
        
        if ($content !== false && strlen($content) > 0) {
            file_put_contents($localPath, $content);
            if ($csvMode && $type === 'parts') {
                $updateStmt->execute([$webPath, $id, $color_id]);
                if ($updatePartsGenericStmt) {
                    $updatePartsGenericStmt->execute([$webPath, $id]);
                }
            } else {
                $updateStmt->execute([$webPath, $id]);
            }
            $downloaded++;
        } else {
            $failed++;
            echo "Failed: $url\n";
        }
    }
    
    // Progress
    if ($counter % 100 === 0) {
        echo "Processed: $counter / $total (Skipped: $skipped, Downloaded: $downloaded, Failed: $failed)\r";
    }
}

echo "\nDone!\n";
echo "Total Processed: $counter\n";
echo "Downloaded: $downloaded\n";
echo "Skipped: $skipped\n";
echo "Failed: $failed\n";


function downloadUrl($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    } else {
        return @file_get_contents($url);
    }
}

function ensureColumnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $column VARCHAR(255)");
        }
    } catch (\Exception $e) {
        // Ignore
    }
}
