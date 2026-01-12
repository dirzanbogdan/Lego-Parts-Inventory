<?php
// CLI Download Script
// Usage: php scripts/cli_download.php [type]
// type: sets, parts, themes (default: sets)

// Determine project root
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/app/autoload.php';

// Load environment config if exists
$localEnv = $projectRoot . '/app/Config/local_env.php';
if (file_exists($localEnv)) require $localEnv;

use App\Core\Config;

// CLI check
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

$type = $argv[1] ?? 'sets';
if (!in_array($type, ['sets', 'parts', 'themes'])) {
    die("Invalid type. Usage: php cli_download.php [sets|parts|themes]\n");
}

echo "Starting download for: $type\n";
echo "Press Ctrl+C to stop.\n\n";

// Reuse logic from UpdateController but simplified for CLI
try {
    $pdo = Config::db();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage() . "\n");
}

$targetDir = $projectRoot . '/public/images/' . $type;
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Select items
$sql = "";
$csvMode = false;
$csvPath = '';

if ($type === 'sets') {
    $sql = "SELECT set_num as id, img_url FROM sets WHERE img_url LIKE 'http%'";
} elseif ($type === 'parts') {
    $csvPath = $projectRoot . '/parts and sets/csv/inventory_parts.csv';
    if (file_exists($csvPath)) {
        $csvMode = true;
        echo "INFO: Found local CSV at " . basename($csvPath) . ". Processing...\n";
    } else {
        echo "INFO: Local CSV not found. Using database records.\n";
        $sql = "SELECT part_num as id, img_url FROM parts WHERE img_url LIKE 'http%'";
    }
} elseif ($type === 'themes') {
    $sql = "SELECT id, img_url FROM themes WHERE img_url LIKE 'http%'";
}

$stmt = null;
$total = 0;
$csvHandle = null;
$idx_part = -1;
$idx_color = -1;
$idx_img = -1;

if ($csvMode) {
    // Count lines
    $lineCount = 0;
    $handle = fopen($csvPath, "r");
    if ($handle) {
        while(!feof($handle)){
            if (fgets($handle) !== false) $lineCount++;
        }
        fclose($handle);
    }
    $total = max(0, $lineCount - 1);
    
    $csvHandle = fopen($csvPath, 'r');
    if ($csvHandle) {
        $headers = fgetcsv($csvHandle);
        if ($headers) {
            $idx_part = array_search('part_num', $headers);
            $idx_color = array_search('color_id', $headers);
            $idx_img = array_search('img_url', $headers);
        }
        if ($idx_part === false || $idx_color === false || $idx_img === false) {
            die("ERROR: CSV missing required columns.\n");
        }
    }
} else {
    $stmt = $pdo->query($sql);
    $total = $stmt->rowCount();
}

echo "Found $total items to process.\n";

$downloaded = 0;
$failed = 0;
$skipped = 0;
$counter = 0;

$updateStmt = null;
$updatePartsGenericStmt = null;

if ($type === 'sets') {
    $updateStmt = $pdo->prepare("UPDATE sets SET img_url = ? WHERE set_num = ?");
} elseif ($type === 'parts') {
    if ($csvMode) {
        $updateStmt = $pdo->prepare("UPDATE inventory_parts SET img_url = ? WHERE part_num = ? AND color_id = ?");
        $updatePartsGenericStmt = $pdo->prepare("UPDATE parts SET img_url = ? WHERE part_num = ? AND (img_url IS NULL OR img_url = '')");
    } else {
        $updateStmt = $pdo->prepare("UPDATE parts SET img_url = ? WHERE part_num = ?");
    }
} elseif ($type === 'themes') {
    $updateStmt = $pdo->prepare("UPDATE themes SET img_url = ? WHERE id = ?");
}

while (true) {
    $row = null;
    if ($csvMode) {
        if (($data = fgetcsv($csvHandle)) !== false) {
            if (count($data) <= max($idx_part, $idx_color, $idx_img)) continue;
            $row = [
                'id' => $data[$idx_part],
                'color_id' => $data[$idx_color],
                'img_url' => $data[$idx_img]
            ];
        } else {
            break;
        }
    } else {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) break;
    }

    $counter++;
    $id = $row['id'];
    $url = $row['img_url'] ?? '';
    $color_id = $row['color_id'] ?? null;
    
    if (empty($url) || strpos($url, 'http') !== 0) continue;
    
    $ext = 'jpg';
    if (preg_match('/\.(\w{3,4})$/', $url, $m)) $ext = $m[1];
    
    $safeId = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $id);
    
    if ($csvMode && $type === 'parts') {
        $filename = $safeId . '_' . $color_id . '.' . $ext;
    } else {
        $filename = $safeId . '.' . $ext;
    }
    
    $localPath = $targetDir . '/' . $filename;
    $webPath = '/images/' . $type . '/' . $filename;

    // Progress bar
    if ($counter % 50 === 0 || $counter === $total) {
        $percent = $total > 0 ? round(($counter / $total) * 100, 1) : 0;
        echo "\rProgress: $counter / $total ($percent%) [DL: $downloaded | Skip: $skipped | Fail: $failed]";
    }

    if (file_exists($localPath) && filesize($localPath) > 0) {
        if ($csvMode && $type === 'parts') {
            $updateStmt->execute([$webPath, $id, $color_id]);
            if ($updatePartsGenericStmt) $updatePartsGenericStmt->execute([$webPath, $id]);
        } else {
            $updateStmt->execute([$webPath, $id]);
        }
        $skipped++;
        continue;
    }

    // Rate limiting
    if ($downloaded > 0 && $downloaded % 50 === 0) {
        $pause = rand(1, 10);
        echo "\nRate limiting pause for {$pause}s...\n";
        sleep($pause);
    }
    
    usleep(100000); // 0.1s

    $content = false;
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $content = curl_exec($ch);
        curl_close($ch);
    } else {
        $content = @file_get_contents($url);
    }
    
    if ($content !== false && strlen($content) > 0) {
        file_put_contents($localPath, $content);
        if ($csvMode && $type === 'parts') {
            $updateStmt->execute([$webPath, $id, $color_id]);
            if ($updatePartsGenericStmt) $updatePartsGenericStmt->execute([$webPath, $id]);
        } else {
            $updateStmt->execute([$webPath, $id]);
        }
        $downloaded++;
    } else {
        $failed++;
        echo "\nFailed to download: $id ($url)\n";
    }
}

if ($csvMode && $csvHandle) fclose($csvHandle);

echo "\n\nDone!\n";
echo "Downloaded: $downloaded\n";
echo "Skipped: $skipped\n";
echo "Failed: $failed\n";
