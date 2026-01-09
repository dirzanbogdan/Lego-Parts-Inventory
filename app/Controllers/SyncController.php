<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Security;
use App\Models\Part;
use App\Models\Color;
use App\Models\SetModel;
use App\Config\Config;
use PDO;
class SyncController extends Controller {
    private $lastFetchMeta = [];
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

        // LEGO PAB Search
        $results = $this->searchLegoPAB($partCode);
        
        // If we get multiple results, pick the first one that matches the design ID
        $bestMatch = null;
        foreach ($results as $res) {
            if (isset($res['designId']) && $res['designId'] === $partCode) {
                $bestMatch = $res;
                break;
            }
        }
        if (!$bestMatch && !empty($results)) {
            $bestMatch = $results[0]; // Fallback
        }

        $name = $bestMatch['name'] ?? 'Unknown Part';
        $imageUrl = $bestMatch['imageUrl'] ?? null;
        $localImg = $this->saveImage($imageUrl, 'parts', $partCode);
        
        $data = [
            'name' => trim($name),
            'part_code' => $partCode,
            'image_url' => $localImg ?: $imageUrl,
            'bricklink_url' => 'https://www.lego.com/en-us/pick-and-build/pick-a-brick?query=' . urlencode($partCode),
        ];
        
        $existing = Part::findByCode($partCode);
        $partId = null;
        if ($existing) {
            $merged = array_merge($existing, array_filter($data)); 
            Part::update((int)$existing['id'], $merged);
            $partId = (int)$existing['id'];
        } else {
            Part::create($data);
            $pdoTmp = Config::db();
            $partId = (int)$pdoTmp->lastInsertId();
        }

        if ($this->isJsonRequest()) {
             header('Content-Type: application/json');
             echo json_encode([
                 'status' => $partId ? 'ok' : 'err',
                 'type' => 'part',
                 'code' => $partCode,
                 'related_count' => 0, 
                 'inv_count' => 0,
                 'log' => $partId ? ['Synced from LEGO PAB'] : ['Not found on LEGO PAB']
             ]);
             exit;
        }

        if ($partId) {
            header('Location: /parts/view?id=' . $partId . '&synced=1');
        } else {
            header('Location: /parts?synced=1&code=' . urlencode($partCode));
        }
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
        $syncLog[] = 'source=lego_pab';
        
        $elements = $this->searchLegoPAB($setCode);
        $syncLog[] = 'elements_found=' . count($elements);

        $pdo = Config::db();
        $stSet = $pdo->prepare('SELECT * FROM sets WHERE set_code=? LIMIT 1');
        $stSet->execute([$setCode]);
        $set = $stSet->fetch();
        
        $setId = null;
        if (!$set) {
            // Create set if not exists (Basic info since PAB doesn't give full set details easily)
            SetModel::create([
                'set_name' => 'Set ' . $setCode,
                'set_code' => $setCode,
                'type' => 'Standard',
                'year' => (int)date('Y'), // Default to current year
                'image' => null,
                'instructions_url' => null
            ]);
            $setId = (int)$pdo->lastInsertId();
            $syncLog[] = 'created_set=' . $setId;
        } else {
            $setId = (int)$set['id'];
        }

        if ($setId) {
            if (!empty($elements)) {
                foreach ($elements as $el) {
                    $designId = $el['designId'] ?? null;
                    if (!$designId) continue;
                    
                    // Sync Part if missing
                    $part = Part::findByCode($designId);
                    if (!$part) {
                        Part::create([
                            'name' => $el['name'] ?? $designId,
                            'part_code' => $designId,
                            'image_url' => $el['imageUrl'] ?? null,
                            'bricklink_url' => 'https://www.lego.com/en-us/pick-and-build/pick-a-brick?query=' . urlencode($designId)
                        ]);
                        $part = Part::findByCode($designId);
                    }
                    
                    $colorId = null;
                    if (isset($el['colorId'])) {
                         $stC = $pdo->prepare('SELECT id FROM colors WHERE color_code=? LIMIT 1');
                         $stC->execute([$el['colorId']]);
                         $colorId = $stC->fetchColumn();
                         
                         if (!$colorId) {
                             $cName = $el['colorName'] ?? ('Color ' . $el['colorId']);
                             $pdo->prepare('INSERT INTO colors (color_name, color_code) VALUES (?,?)')->execute([$cName, $el['colorId']]);
                             $colorId = $pdo->lastInsertId();
                         }
                    }
                    
                    if ($part && $colorId) {
                         $qty = 1; // PAB usually lists unique elements, not BOM quantity per se in search results, but sometimes it does. 
                         // However, for "search by set", PAB might list the element multiple times or just once. 
                         // If PAB result doesn't have quantity, assume 1.
                         // Actually PAB "search by set" usually shows the parts you can buy.
                         
                         $ins = $pdo->prepare('INSERT INTO set_parts (set_id, part_id, color_id, quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)');
                         $ins->execute([$setId, (int)$part['id'], $colorId, $qty]);
                    }
                }
            }
            
            // Log
             try {
                $uid = $_SESSION['user']['id'] ?? null;
                $stLog = $pdo->prepare("INSERT INTO entity_history (entity_type, entity_id, user_id, changes) VALUES ('set', ?, ?, ?)");
                $stLog->execute([$setId, $uid, json_encode($syncLog)]);
            } catch (\Throwable $e) {}
        }
        
        if ($this->isJsonRequest()) {
             header('Content-Type: application/json');
             echo json_encode([
                 'status' => $setId ? 'ok' : 'err',
                 'type' => 'set',
                 'code' => $setCode,
                 'instructions_url' => null,
                 'inv_count' => count($elements),
                 'log' => $syncLog
             ]);
             exit;
        }

        if (!empty($setId)) {
            header('Location: /sets/view?id=' . $setId . '&synced=1');
        } else {
            header('Location: /sets?synced=1&code=' . urlencode($setCode));
        }
    }

    private function isJsonRequest(): bool {
        return isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }

    private function searchLegoPAB(string $query): array {
        $url = 'https://www.lego.com/en-us/pick-and-build/pick-a-brick?query=' . urlencode($query);
        $html = $this->fetch($url);
        
        // Attempt to parse __NEXT_DATA__
        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/', $html, $m)) {
            $data = json_decode($m[1], true);
            // Navigate: props -> pageProps -> initialData -> elements
            // Or similar structure. This is highly volatile.
            // I'll search recursively for "elements" or "results" key in the JSON.
            return $this->findKeyInArray($data, 'elements') ?? [];
        }
        return [];
    }
    
    private function findKeyInArray(array $array, string $key) {
        if (isset($array[$key])) return $array[$key];
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $res = $this->findKeyInArray($v, $key);
                if ($res) return $res;
            }
        }
        return null;
    }

    private function fetch(string $url): string {
        $this->lastFetchMeta = [];
        $ch = curl_init($url);
        
        $headers = [
            'Authority: www.lego.com',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: max-age=0',
            'Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'Sec-Ch-Ua-Mobile: ?0',
            'Sec-Ch-Ua-Platform: "Windows"',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Upgrade-Insecure-Requests: 1',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer: https://www.lego.com/',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => 'gzip,deflate,br',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_COOKIEJAR => sys_get_temp_dir() . '/lego_cookies.txt',
            CURLOPT_COOKIEFILE => sys_get_temp_dir() . '/lego_cookies.txt',
        ]);
        
        $html = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $eff = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        if ($html === false || $code === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $html = curl_exec($ch);
            $err = curl_error($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $eff = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        }
        
        curl_close($ch);
        $this->lastFetchMeta = ['http_code' => $code, 'error' => $err, 'effective_url' => $eff, 'length' => is_string($html) ? strlen($html) : 0];
        return is_string($html) ? $html : '';
    }
    private function saveImage(?string $url, string $type, string $code): ?string {
        if (!$url) return null;
        if (strpos($url, '//') === 0) $url = 'https:' . $url;
        $ch = curl_init($url);
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => $ua,
            CURLOPT_ENCODING => 'gzip,deflate',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $data = curl_exec($ch);
        if ($data === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $data = curl_exec($ch);
        }
        curl_close($ch);
        if (!$data) return null;
        $dir = __DIR__ . '/../../public/uploads/' . $type;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $ext = 'jpg';
        $path = $dir . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $code) . '.' . $ext;
        @file_put_contents($path, $data);
        if (file_exists($path)) {
            return '/uploads/' . $type . '/' . basename($path);
        }
        return null;
    }
    private function extract(string $html, string $pattern): ?string {
        if (!$html) return null;
        if (preg_match($pattern, $html, $m)) return trim(html_entity_decode($m[1]));
        return null;
    }
    private function extractSectionItems(string $html, string $sectionTitle): array {
        if (!$html) return [];
        $items = [];
        $pos = stripos($html, $sectionTitle);
        if ($pos === false) return [];
        $snippet = substr($html, $pos, 20000);
        if (preg_match_all('/catalogitem\\.page\\?P=([^"&\\s]+)/i', $snippet, $m)) {
            foreach (array_unique($m[1]) as $code) {
                $items[] = ['code' => html_entity_decode($code)];
            }
        }
        return $items;
    }
    private function getPartComposition(string $partCode): array {
        $invUrl = 'https://www.bricklink.com/catalogItemInv.asp?P=' . urlencode($partCode);
        $html = $this->fetch($invUrl);
        if (!$html) return [];
        $items = [];
        // Match rows containing part links and quantities
        if (preg_match_all('/catalogitem\\.page\\?P=([^"\\s]+)[\\s\\S]*?Qty[^\\d]*(\\d+)/i', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $items[] = ['code' => html_entity_decode($row[1]), 'quantity' => (int)$row[2]];
            }
        }
        return $items;
    }
    private function upsertPartComposition(int $parentPartId, array $items): void {
        $pdo = Config::db();
        foreach ($items as $it) {
            $child = Part::findByCode($it['code']);
            if (!$child) {
                Part::create(['name' => $it['code'], 'part_code' => $it['code']]);
                $child = Part::findByCode($it['code']);
            }
            if ($child && isset($child['id'])) {
                try {
                    $st = $pdo->prepare('INSERT INTO part_parts (parent_part_id, child_part_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)');
                    $st->execute([$parentPartId, (int)$child['id'], (int)($it['quantity'] ?? 1)]);
                } catch (\Throwable $e) {
                    // ignore if table not migrated yet
                }
            }
        }
    }
    private function getSetInventory(string $setCode, array &$log = []): array {
        $invUrl = 'https://www.bricklink.com/catalogItemInv.asp?S=' . urlencode($setCode);
        $html = $this->fetch($invUrl);
        if (!$html) {
            $log[] = 'inv_html_empty';
            return [];
        }
        
        $items = [];
        // Parse HTML table rows
        // Structure typically: Image | Qty | Item No | Description
        
        $log[] = 'parsing_method=table_tr_td';
        if (preg_match_all('/<TR[^>]*>(.*?)<\/TR>/is', $html, $rows)) {
            foreach ($rows[1] as $row) {
                // Must contain catalogitem.page?P=
                if (stripos($row, 'catalogitem.page?P=') === false) continue;
                
                // Extract cells
                if (preg_match_all('/<TD[^>]*>(.*?)<\/TD>/is', $row, $cells)) {
                    $cols = $cells[1];
                    $partCode = null;
                    $qty = 0;
                    $colorName = null;
                    
                    // Find Part Code Column
                    $pIdx = -1;
                    foreach ($cols as $idx => $cell) {
                        if (preg_match('/catalogitem\.page\?P=([^"&]+)/i', $cell, $pm)) {
                            $partCode = $pm[1];
                            $pIdx = $idx;
                            break;
                        }
                    }
                    
                    if ($partCode && $pIdx > 0) {
                        // Qty is usually in pIdx - 1
                        $qtyRaw = strip_tags($cols[$pIdx - 1]);
                        $qty = (int)trim($qtyRaw);
                        
                        // Color is usually in Description (pIdx + 1)
                        if (isset($cols[$pIdx + 1])) {
                            $desc = trim(strip_tags($cols[$pIdx + 1]));
                            $colorName = $desc; 
                        }
                    }
                    
                    if ($partCode && $qty > 0) {
                        $items[] = [
                            'code' => html_entity_decode($partCode),
                            'color' => $colorName, 
                            'quantity' => $qty
                        ];
                    }
                }
            }
        }
        
        // Fallback to regex if table parse fails
        if (empty($items)) {
             $log[] = 'parsing_method=fallback_regex';
             if (preg_match_all('/catalogitem\\.page\\?P=([^"\\s]+)[\\s\\S]*?Qty[^\\d]*(\\d+)/i', $html, $m2, PREG_SET_ORDER)) {
                foreach ($m2 as $row) {
                    $items[] = ['code' => html_entity_decode($row[1]), 'quantity' => (int)$row[2], 'color' => null];
                }
            }
        }
        
        // Second fallback: Look for "x Qty" pattern (e.g. "4x") often found in simple lists
        if (empty($items)) {
            $log[] = 'parsing_method=fallback_simple_qty';
            if (preg_match_all('/(\d+)\s*x\s*<a[^>]+catalogitem\.page\?P=([^"&]+)/i', $html, $m3, PREG_SET_ORDER)) {
                foreach ($m3 as $row) {
                    $items[] = ['code' => html_entity_decode($row[2]), 'quantity' => (int)$row[1], 'color' => null];
                }
            }
        }
        
        if (empty($items)) {
            $log[] = 'parse_failed_preview=' . substr(strip_tags($html), 0, 300);
        }

        return $items;
    }
    private function extractInstructionsUrl(string $html): ?string {
        if (!$html) return null;
        // Try BrickLink instruction download links
        if (preg_match('/href="([^"]*catalogDownloadInstructions[^"]*)"/i', $html, $m)) {
            $url = html_entity_decode($m[1]);
            if (strpos($url, '//') === 0) $url = 'https:' . $url;
            return $url;
        }
        // Generic "Instructions" anchor
        if (preg_match('/<a[^>]+href="([^"]+)"[^>]*>\\s*Instructions\\s*<\\/a>/i', $html, $m2)) {
            $url = html_entity_decode($m2[1]);
            if (strpos($url, '//') === 0) $url = 'https:' . $url;
            return $url;
        }
        return null;
    }
}
