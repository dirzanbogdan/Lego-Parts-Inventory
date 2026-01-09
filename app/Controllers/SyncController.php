<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Part;
use App\Models\Color;
use App\Models\SetModel;
use App\Config\Config;
use App\Services\RebrickableService;
use PDO;

class SyncController extends Controller {
    private RebrickableService $rebrickable;

    public function __construct() {
        parent::__construct();
        $this->rebrickable = new RebrickableService();
    }

    public function syncLegoPart(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $partCode = trim($_POST['part_code'] ?? $_POST['code'] ?? '');
        if (!$partCode) {
            http_response_code(422);
            echo 'invalid';
            return;
        }

        // Ensure parts.csv is available
        if (!$this->rebrickable->ensureFile('parts.csv')) {
             $this->jsonResponse('err', $partCode, ['error' => 'Failed to download parts.csv']);
             return;
        }

        // Find part
        $row = $this->rebrickable->findInCsv('parts.csv', 'part_num', $partCode);
        if (!$row) {
             $this->jsonResponse('err', $partCode, ['error' => 'Part not found in Rebrickable database']);
             return;
        }

        $name = $row['name'] ?? 'Unknown Part';
        // Rebrickable image URL logic: https://cdn.rebrickable.com/media/parts/elements/{part_num}.jpg 
        // OR https://cdn.rebrickable.com/media/parts/ldraw/{color_id}/{part_num}.png
        // Since we don't have color here, we might use a default or fetch from API.
        // For now, let's use a generic placeholder or try to construct one.
        // Actually, we can check parts.csv headers? No, it just has name, class, material.
        
        $imageUrl = "https://cdn.rebrickable.com/media/parts/ldraw/0/{$partCode}.png"; // 0 is usually black or default in ldraw, but let's try.
        
        $data = [
            'name' => trim($name),
            'part_code' => $partCode,
            'image_url' => $imageUrl,
            'bricklink_url' => 'https://rebrickable.com/parts/' . urlencode($partCode),
        ];
        
        $existing = Part::findByCode($partCode);
        $partId = null;
        if ($existing) {
            Part::update((int)$existing['id'], $data);
            $partId = (int)$existing['id'];
        } else {
            Part::create($data);
            $partId = (int)Config::db()->lastInsertId();
        }

        $this->jsonResponse('ok', $partCode, ['Synced from Rebrickable CSV'], $partId);
    }

    public function syncLegoSet(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $setCode = trim($_POST['set_code'] ?? $_POST['code'] ?? '');
        if (!$setCode) {
            http_response_code(422);
            echo 'invalid';
            return;
        }
        
        $syncLog = [];
        $syncLog[] = 'source=rebrickable_csv';
        
        // 1. Ensure Files
        $files = ['sets.csv', 'inventories.csv', 'inventory_parts.csv', 'parts.csv', 'colors.csv'];
        foreach ($files as $f) {
            if (!$this->rebrickable->ensureFile($f)) {
                $syncLog[] = "error_downloading=$f";
                $this->jsonResponse('err', $setCode, $syncLog);
                return;
            }
        }

        // 2. Find Set info
        $setRow = $this->rebrickable->findInCsv('sets.csv', 'set_num', $setCode);
        if (!$setRow) {
             // Try appending -1 if missing
             if (strpos($setCode, '-') === false) {
                 $setCode .= '-1';
                 $setRow = $this->rebrickable->findInCsv('sets.csv', 'set_num', $setCode);
             }
        }

        $setId = null;
        if ($setRow) {
            $pdo = Config::db();
            $stSet = $pdo->prepare('SELECT * FROM sets WHERE set_code=? LIMIT 1');
            $stSet->execute([$setCode]);
            $set = $stSet->fetch();
            
            $imgUrl = "https://cdn.rebrickable.com/media/sets/{$setCode}.jpg";
            
            if (!$set) {
                SetModel::create([
                    'set_name' => $setRow['name'],
                    'set_code' => $setCode,
                    'type' => 'Standard',
                    'year' => (int)$setRow['year'],
                    'image' => $imgUrl,
                    'instructions_url' => "https://rebrickable.com/sets/{$setCode}"
                ]);
                $setId = (int)$pdo->lastInsertId();
                $syncLog[] = 'created_set=' . $setId;
            } else {
                $setId = (int)$set['id'];
                // Update info?
            }
        } else {
            $syncLog[] = 'set_not_found_in_csv';
            $this->jsonResponse('err', $setCode, $syncLog);
            return;
        }

        // 3. Find Inventory ID
        // inventories.csv: id, version, set_num
        // There might be multiple versions. We usually want version 1.
        $invRows = $this->rebrickable->findAllInCsv('inventories.csv', 'set_num', $setCode);
        $inventoryId = null;
        foreach ($invRows as $ir) {
            if ($ir['version'] == 1) {
                $inventoryId = $ir['id'];
                break;
            }
        }
        if (!$inventoryId && !empty($invRows)) {
            $inventoryId = $invRows[0]['id'];
        }

        if (!$inventoryId) {
            $syncLog[] = 'inventory_id_not_found';
            $this->jsonResponse('err', $setCode, $syncLog);
            return;
        }

        // 4. Find Inventory Parts
        // This is the heavy part. We need to scan inventory_parts.csv for inventory_id.
        // It's sorted by inventory_id? Usually yes, but not guaranteed.
        // Streaming scan...
        $partsFound = 0;
        
        $pdo = Config::db();
        
        // Cache colors to memory for speed
        $colorsMap = []; // rebrickable_id => local_id
        $stC = $pdo->query("SELECT id, color_code FROM colors"); // assume color_code stores rebrickable ID or we add a column
        // Ideally we should have a map. For now let's assume color_code is used for Rebrickable ID (which are integers 0, 1, 2...)
        while ($r = $stC->fetch()) {
            $colorsMap[$r['color_code']] = $r['id'];
        }

        // Cache parts map? Too big.
        
        foreach ($this->rebrickable->readCsv('inventory_parts.csv') as $row) {
            if ($row['inventory_id'] == $inventoryId) {
                $partNum = $row['part_num'];
                $colorIdRb = $row['color_id'];
                $qty = (int)$row['quantity'];
                
                // Ensure Part Exists
                // We might need to look up part name in parts.csv if not local.
                // Doing this for every part is slow.
                // Optimization: Collect all missing part_nums first?
                // Let's just check DB.
                $stP = $pdo->prepare('SELECT id FROM parts WHERE part_code=? LIMIT 1');
                $stP->execute([$partNum]);
                $partId = $stP->fetchColumn();
                
                if (!$partId) {
                    // Look up in parts.csv (inefficient inside loop, but maybe okay for one set)
                    // Better: use rebrickable service to find one.
                    // But we are already scanning a file. We can't scan another file simultaneously with the same service instance unless we open new handle.
                    // The service uses generator, so it's fine to call findInCsv which opens a new handle.
                    $pData = $this->rebrickable->findInCsv('parts.csv', 'part_num', $partNum);
                    if ($pData) {
                         Part::create([
                            'name' => $pData['name'],
                            'part_code' => $partNum,
                            'image_url' => "https://cdn.rebrickable.com/media/parts/ldraw/{$colorIdRb}/{$partNum}.png",
                            'bricklink_url' => "https://rebrickable.com/parts/$partNum"
                        ]);
                        $partId = $pdo->lastInsertId();
                    } else {
                        // Create stub
                        Part::create(['name' => $partNum, 'part_code' => $partNum]);
                        $partId = $pdo->lastInsertId();
                    }
                }
                
                // Ensure Color Exists
                if (!isset($colorsMap[$colorIdRb])) {
                    // Look up color name in colors.csv
                    $cData = $this->rebrickable->findInCsv('colors.csv', 'id', $colorIdRb);
                    $cName = $cData ? $cData['name'] : "Color $colorIdRb";
                    $pdo->prepare("INSERT INTO colors (color_name, color_code) VALUES (?, ?)")->execute([$cName, $colorIdRb]);
                    $colorsMap[$colorIdRb] = $pdo->lastInsertId();
                }
                $localColorId = $colorsMap[$colorIdRb];
                
                // Insert Set Part
                $pdo->prepare("INSERT INTO set_parts (set_id, part_id, color_id, quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)")
                    ->execute([$setId, $partId, $localColorId, $qty]);
                    
                $partsFound++;
            }
        }
        
        $syncLog[] = "parts_synced=$partsFound";
        
        // Log history
        try {
            $uid = $_SESSION['user']['id'] ?? null;
            $pdo->prepare("INSERT INTO entity_history (entity_type, entity_id, user_id, changes) VALUES ('set', ?, ?, ?)")
                ->execute([$setId, $uid, json_encode($syncLog)]);
        } catch (\Throwable $e) {}

        $this->jsonResponse('ok', $setCode, $syncLog, $setId, $partsFound);
    }

    private function jsonResponse(string $status, string $code, array $log, ?int $id = null, int $invCount = 0): void {
        if ($this->isJsonRequest()) {
             header('Content-Type: application/json');
             echo json_encode([
                 'status' => $status,
                 'type' => 'set', // or part, generic enough
                 'code' => $code,
                 'instructions_url' => null,
                 'inv_count' => $invCount,
                 'log' => $log
             ]);
             exit;
        }
        
        if ($id) {
            header('Location: /sets/view?id=' . $id . '&synced=1');
        } else {
            header('Location: /sets?synced=1&code=' . urlencode($code));
        }
    }

    private function isJsonRequest(): bool {
        return isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }
}
