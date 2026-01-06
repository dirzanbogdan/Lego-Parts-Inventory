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
    public function syncBrickLink(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $partCode = trim($_POST['part_code'] ?? '');
        if (!$partCode) {
            http_response_code(422);
            echo 'invalid';
            return;
        }
        $url = 'https://www.bricklink.com/v2/catalog/catalogitem.page?P=' . urlencode($partCode);
        $html = $this->fetch($url);
        
        $name = $this->extract($html, '/<title>(.*?)<\/title>/i') ?: 'unknown';
        $name = preg_replace('/^BrickLink : Part \w+ : /', '', $name);
        $name = preg_replace('/ - BrickLink Reference Catalog$/', '', $name);
        
        $image = $this->extract($html, '/<img[^>]+src="([^"]+)"[^>]*class="img-item"/i') ?: null;
        if ($image && strpos($image, '//') === 0) $image = 'https:' . $image;
        $localImg = $this->saveImage($image, 'parts', $partCode);
        
        $weight = $this->extract($html, '/Weight:.*?([\d\.]+)\s*g/is');
        $stud = $this->extract($html, '/Stud Dim\.:.*?([\d\s\.]+)\s*in/is');
        $counterparts = $this->extractSectionItems($html, 'Counterparts');
        $altMolds = $this->extractSectionItems($html, 'Alternate Molds');
        $relatedItems = [
            'counterparts' => $counterparts,
            'alternate_molds' => $altMolds,
        ];
        
        $data = [
            'name' => trim($name),
            'part_code' => $partCode,
            'image_url' => $localImg ?: $image,
            'bricklink_url' => $url,
            'weight' => $weight ? (float)$weight : null,
            'stud_dimensions' => $stud,
            'related_items' => json_encode($relatedItems),
        ];
        
        $existing = Part::findByCode($partCode);
        $partId = null;
        if ($existing) {
            $merged = [
                'name' => ($data['name'] ?: $existing['name']),
                'part_code' => $existing['part_code'],
                'version' => $existing['version'] ?? null,
                'category_id' => $existing['category_id'] ?? null,
                'image_url' => ($data['image_url'] ?: ($existing['image_url'] ?? null)),
                'bricklink_url' => ($data['bricklink_url'] ?: ($existing['bricklink_url'] ?? null)),
                'years_released' => $existing['years_released'] ?? null,
                'weight' => ($data['weight'] ?? ($existing['weight'] ?? null)),
                'stud_dimensions' => ($data['stud_dimensions'] ?: ($existing['stud_dimensions'] ?? null)),
                'package_dimensions' => $existing['package_dimensions'] ?? null,
                'no_of_parts' => $existing['no_of_parts'] ?? null,
                'related_items' => ($data['related_items'] ?: ($existing['related_items'] ?? null)),
            ];
            Part::update((int)$existing['id'], $merged);
            $partId = (int)$existing['id'];
            try {
                $pdoLog = Config::db();
                $uid = $_SESSION['user']['id'] ?? null;
                $stLog = $pdoLog->prepare("INSERT INTO entity_history (entity_type, entity_id, user_id, changes) VALUES ('part', ?, ?, ?)");
                $stLog->execute([$partId, $uid, 'Sync BrickLink']);
            } catch (\Throwable $e) {
            }
        } else {
            $defaults = [
                'version' => null,
                'category_id' => null,
                'years_released' => null,
                'package_dimensions' => null,
                'no_of_parts' => null,
            ];
            Part::create($data + $defaults);
            $pdoTmp = Config::db();
            $partId = (int)$pdoTmp->lastInsertId();
            try {
                $pdoLog = Config::db();
                $uid = $_SESSION['user']['id'] ?? null;
                $stLog = $pdoLog->prepare("INSERT INTO entity_history (entity_type, entity_id, user_id, changes) VALUES ('part', ?, ?, ?)");
                $stLog->execute([$partId, $uid, 'Create via BrickLink sync']);
            } catch (\Throwable $e) {
            }
        }
        $consists = $this->getPartComposition($partCode);
        if (!empty($consists) && $partId) {
            $this->upsertPartComposition($partId, $consists);
        }
        if ($partId) {
            header('Location: /parts/view?id=' . $partId . '&synced=1');
        } else {
            header('Location: /parts?synced=1&code=' . urlencode($partCode));
        }
    }
    public function syncBrickLinkSet(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $setCode = trim($_POST['set_code'] ?? '');
        if (!$setCode) {
            http_response_code(422);
            echo 'invalid';
            return;
        }
        $url = 'https://www.bricklink.com/v2/catalog/catalogitem.page?S=' . urlencode($setCode);
        $html = $this->fetch($url);
        $syncLog = [];
        $syncLog[] = 'fetch_set_url=' . $url;
        $syncLog[] = 'fetch_set_len=' . strlen($html);
        $instructionsUrl = $this->extractInstructionsUrl($html);
        if ($instructionsUrl) {
            $chk = $this->fetch($instructionsUrl);
            $codeChk = (int)($this->lastFetchMeta['http_code'] ?? 0);
            if ($codeChk !== 200 || !$chk) {
                $instructionsUrl = null;
            }
        }
        $syncLog[] = 'instructions_valid=' . ($instructionsUrl ? '1' : '0');
        $parts = $this->getSetInventory($setCode);
        $pdo = Config::db();
        $stSet = $pdo->prepare('SELECT * FROM sets WHERE set_code=? LIMIT 1');
        $stSet->execute([$setCode]);
        $set = $stSet->fetch();
        if ($set) {
            $setId = (int)$set['id'];
            if ($instructionsUrl) {
                $stUpd = $pdo->prepare('UPDATE sets SET instructions_url=? WHERE id=?');
                $stUpd->execute([$instructionsUrl, $setId]);
            }
            if (!empty($parts)) {
                foreach ($parts as $p) {
                    $part = Part::findByCode($p['code']);
                    if (!$part) continue;
                    $colorId = null;
                    if (!empty($p['color'])) {
                        $c = Color::findByName($p['color']);
                        $colorId = $c['id'] ?? null;
                    }
                    $ins = $pdo->prepare('INSERT INTO set_parts (set_id, part_id, color_id, quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)');
                    $ins->execute([$setId, (int)$part['id'], $colorId, (int)$p['quantity']]);
                }
                $syncLog[] = 'inv_set_count=' . count($parts);
            } else {
                $syncLog[] = 'inv_set_count=0';
            }
            try {
                $uid = $_SESSION['user']['id'] ?? null;
                $stLog = $pdo->prepare("INSERT INTO entity_history (entity_type, entity_id, user_id, changes) VALUES ('set', ?, ?, ?)");
                $stLog->execute([$setId, $uid, json_encode($syncLog)]);
            } catch (\Throwable $e) {
            }
        }
        if (!empty($setId)) {
            header('Location: /sets/view?id=' . $setId . '&synced=1');
        } else {
            header('Location: /sets?synced=1&code=' . urlencode($setCode));
        }
    }
    private function fetch(string $url): string {
        $this->lastFetchMeta = [];
        $ch = curl_init($url);
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $hdrs = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9,ro;q=0.8',
            'Referer: https://www.bricklink.com/',
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => $ua,
            CURLOPT_HTTPHEADER => $hdrs,
            CURLOPT_ENCODING => 'gzip,deflate',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
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
        $this->lastFetchMeta = ['http_code' => $code, 'error' => $err, 'effective_url' => $eff];
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
    private function getSetInventory(string $setCode): array {
        $invUrl = 'https://www.bricklink.com/catalogItemInv.asp?S=' . urlencode($setCode);
        $html = $this->fetch($invUrl);
        if (!$html) return [];
        $items = [];
        if (preg_match_all('/catalogitem\\.page\\?P=([^"\\s]+)[\\s\\S]*?Color:\\s*<[^>]*>([^<]*)[\\s\\S]*?Qty[^\\d]*(\\d+)/i', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $items[] = ['code' => html_entity_decode($row[1]), 'color' => trim(html_entity_decode($row[2])), 'quantity' => (int)$row[3]];
            }
        } else {
            // Fallback without color
            if (preg_match_all('/catalogitem\\.page\\?P=([^"\\s]+)[\\s\\S]*?Qty[^\\d]*(\\d+)/i', $html, $m2, PREG_SET_ORDER)) {
                foreach ($m2 as $row) {
                    $items[] = ['code' => html_entity_decode($row[1]), 'quantity' => (int)$row[2]];
                }
            }
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
