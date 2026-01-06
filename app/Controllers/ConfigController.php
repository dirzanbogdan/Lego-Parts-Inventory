<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Config\Config;

class ConfigController extends Controller {
    private array $lastFetchMeta = [];

    public function page(): void {
        Security::requireRole('admin');
        $this->render('admin/config', []);
    }

    public function scrapePartsOne(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        $delay = function() {
            $d = random_int(3, 10);
            sleep($d);
        };

        $raw = trim($_POST['code'] ?? '');
        if ($raw === '') {
            http_response_code(422);
            echo 'invalid';
            return;
        }

        // Normalize code: remove (Inv)
        $code = preg_replace('/\s*\(Inv\)\s*$/i', '', $raw);

        // Check for color param in input (e.g. Code C=11)
        $colorParam = null;
        if (preg_match('/\bC=(\d+)/i', $raw, $cm)) {
            $colorParam = $cm[1];
        }

        $anchor = $colorParam ? ('#T=C&C=' . $colorParam) : '';
        $url = 'https://www.bricklink.com/v2/catalog/catalogitem.page?P=' . urlencode($code) . $anchor;

        $delay();
        $html = $this->fetch($url);
        $log = [];
        $log[] = 'fetch_parts_url=' . $url;
        $log[] = 'fetch_parts_len=' . strlen($html);
        $log[] = 'http_code=' . ($this->lastFetchMeta['http_code'] ?? 0);
        if (!empty($this->lastFetchMeta['error'])) $log[] = 'curl_error=' . $this->lastFetchMeta['error'];

        // If no color param in input, try to find it in the fetched HTML (if redirected or default)
        if (!$colorParam) {
            if (preg_match('/#T=C&C=(\d+)/i', $html, $am)) {
                $colorParam = $am[1];
            }
        }

        $name = $this->extract($html, '/<title>(.*?)<\/title>/i') ?: $code;
        $img = $this->extract($html, '/<img[^>]+src="([^"]+)"[^>]*class="img-item"/i');
        $localImg = $this->saveImage($img, 'parts', $code);
        $log[] = 'image_local=' . ($localImg ? '1' : '0');

        $version = null;
        if (preg_match('/^[^\\s]+?([A-Za-z].*)$/', $code, $vm)) {
            $version = $vm[1];
        }

        $weight = null;
        if (preg_match('/Item\s*Weight:\s*([\d\.]+)/i', $html, $wm)) {
            $weight = (float)$wm[1];
        }

        $stud = null;
        if (preg_match('/Stud\s*Dimensions:\s*([^<\n]+)/i', $html, $sm)) {
            $stud = trim($sm[1]);
        }

        $pkg = null;
        if (preg_match('/Item\s*Dim\w*:\s*([^<\n]+)/i', $html, $pm)) {
            $pkg = trim($pm[1]);
        }

        $composition = null;
        if (preg_match('/\(\s*(\d+[A-Za-z0-9]*\s*\/\s*\d+[A-Za-z0-9]*\s*)\)/', $name, $cmps)) {
            $composition = trim($cmps[1]);
        }
        $log[] = 'composition_tag=' . ($composition ? '1' : '0');

        // Extract Related Items
        // Look for "This Item fits with" block
        $relatedItems = [];
        if (preg_match('/This Item fits with and is usually used with the following Item\(s\):(.*?)(?:<\/table>|<div)/is', $html, $relMatch)) {
             preg_match_all('/catalogitem\.page\?P=([^"&]+).*?>([^<]+)</i', $relMatch[1], $relLinks);
             if (isset($relLinks[1])) {
                 foreach ($relLinks[1] as $idx => $rCode) {
                     $rDesc = trim($relLinks[2][$idx] ?? '');
                     $relatedItems[] = ['code' => $rCode, 'name' => $rDesc];
                 }
             }
        }
        // Also include Counterparts / Alternate Molds sections
        $counterparts = $this->extractSectionItems($html, 'Counterparts');
        $altMolds = $this->extractSectionItems($html, 'Alternate Molds');
        foreach ($counterparts as $cp) $relatedItems[] = $cp;
        foreach ($altMolds as $am) $relatedItems[] = $am;
        $log[] = 'related_fits=' . (isset($relLinks[1]) ? count($relLinks[1]) : 0);
        $log[] = 'related_counterparts=' . count($counterparts);
        $log[] = 'related_alt_molds=' . count($altMolds);
        $relatedJson = !empty($relatedItems) ? json_encode($relatedItems) : null;

        $pdo = Config::db();
        $st = $pdo->prepare('SELECT id FROM parts WHERE part_code=?');
        $st->execute([$code]);
        $id = (int)($st->fetchColumn() ?: 0);

        if ($id) {
            $s2 = $pdo->prepare('UPDATE parts SET name=?, image_url=?, bricklink_url=?, version=?, weight=?, stud_dimensions=?, package_dimensions=?, composition=?, related_items=? WHERE id=?');
            $s2->execute([$name, ($localImg ?: $img), $url, $version, $weight, $stud, $pkg, $composition, $relatedJson, $id]);
        } else {
            $s3 = $pdo->prepare('INSERT INTO parts (name, part_code, image_url, bricklink_url, version, weight, stud_dimensions, package_dimensions, composition, related_items) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $s3->execute([$name, $code, ($localImg ?: $img), $url, $version, $weight, $stud, $pkg, $composition, $relatedJson]);
            $id = (int)$pdo->lastInsertId();
        }
        $log[] = 'upsert_part_id=' . $id;

        // Scrape inventory if (Inv) or composition detected
        $invCount = 0;
        if (stripos($raw, '(Inv)') !== false || $composition) {
             $invCount = $this->scrapeInventory('P', $code, $id);
        }
        $log[] = 'inv_parts_count=' . $invCount;

        if ($colorParam) {
            $cid = $this->ensureColorByCode($colorParam);
            if ($cid) {
                $pdo->prepare('INSERT INTO part_colors (part_id, color_id, quantity_in_inventory) VALUES (?,?,0) ON DUPLICATE KEY UPDATE quantity_in_inventory=quantity_in_inventory')
                    ->execute([$id, $cid]);
            }
        }
        $log[] = 'color_param=' . ($colorParam ?: '');

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'type' => 'part',
            'code' => $code,
            'id' => $id,
            'name' => $name,
            'related_count' => count($relatedItems),
            'inv_count' => $invCount,
            'color_code' => $colorParam,
            'log' => $log
        ]);
    }

    public function scrapeSetsOne(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        $delay = function() {
            $d = random_int(3, 10);
            sleep($d);
        };

        $code = trim($_POST['code'] ?? '');
        if ($code === '') {
            http_response_code(422);
            echo 'invalid';
            return;
        }

        $url = 'https://www.bricklink.com/v2/catalog/catalogitem.page?S=' . urlencode($code);
        $delay();
        $html = $this->fetch($url);
        $log = [];
        $log[] = 'fetch_set_url=' . $url;
        $log[] = 'fetch_set_len=' . strlen($html);
        $log[] = 'http_code=' . ($this->lastFetchMeta['http_code'] ?? 0);
        if (!empty($this->lastFetchMeta['error'])) $log[] = 'curl_error=' . $this->lastFetchMeta['error'];

        $name = $this->extract($html, '/<title>(.*?)<\/title>/i') ?: $code;
        $img = $this->extract($html, '/<img[^>]+src="([^"]+)"[^>]*class="img-item"/i');
        $localImg = $this->saveImage($img, 'sets', $code);
        $log[] = 'image_local=' . ($localImg ? '1' : '0');

        // Instructions URL
        $instructionsUrl = null;
        if (preg_match('/<a[^>]+href="([^"]+)"[^>]*>View Instructions<\/a>/i', $html, $instM)) {
            $instructionsUrl = 'https://www.bricklink.com' . $instM[1];
        } else {
             if (preg_match('/<a[^>]+href="([^"]+)"[^>]*>Instructions<\/a>/i', $html, $instM2)) {
                 $instructionsUrl = $instM2[1];
                 if (strpos($instructionsUrl, 'http') === false) {
                     $instructionsUrl = 'https://www.bricklink.com' . $instructionsUrl;
                 }
             }
        }
        $log[] = 'instructions=' . ($instructionsUrl ? '1' : '0');

        $pdo = Config::db();
        $st = $pdo->prepare('SELECT id FROM sets WHERE set_code=?');
        $st->execute([$code]);
        $sid = (int)($st->fetchColumn() ?: 0);

        if ($sid) {
            $s2 = $pdo->prepare('UPDATE sets SET set_name=?, image=?, instructions_url=? WHERE id=?');
            $s2->execute([$name, $localImg ?: $img, $instructionsUrl, $sid]);
        } else {
            $s3 = $pdo->prepare('INSERT INTO sets (set_name, set_code, type, year, image, instructions_url) VALUES (?,?,?,?,?,?)');
            $s3->execute([$name, $code, 'official', null, $localImg ?: $img, $instructionsUrl]);
            $sid = (int)$pdo->lastInsertId();
        }
        $log[] = 'upsert_set_id=' . $sid;

        $invCount = $this->scrapeInventory('S', $code, $sid);
        $log[] = 'inv_set_count=' . $invCount;
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'type' => 'set',
            'code' => $code,
            'id' => $sid,
            'name' => $name,
            'instructions_url' => $instructionsUrl,
            'inv_count' => $invCount,
            'log' => $log
        ]);
    }

    private function scrapeInventory(string $type, string $code, int $parentId): int {
        $invUrl = 'https://www.bricklink.com/catalogItemInv.asp?' . $type . '=' . urlencode($code);
        // Delay before inventory scrape to avoid rate limit
        sleep(random_int(3, 5));
        $invHtml = $this->fetch($invUrl);
        $pdo = Config::db();
        $count = 0;
        if ($type === 'S') {
            preg_match_all('/catalogitem.page\?P=([^"&]+).*?color=([^"&]+).*?Qty:\s*(\d+)/is', $invHtml, $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $pcode = trim($m[1]);
                $colorCode = trim($m[2]);
                $qty = (int)trim($m[3]);
                if ($pcode === '' || $qty <= 0) continue;
                $childId = $this->ensurePart($pcode);
                $cId = $this->ensureColorByCode($colorCode);
                if ($childId) {
                    $stp = $pdo->prepare('INSERT INTO set_parts (set_id, part_id, color_id, quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)');
                    $stp->execute([$parentId, $childId, $cId ?: null, $qty]);
                    $count++;
                }
            }
        } else {
            preg_match_all('/catalogitem.page\?P=([^"&]+)[\s\S]*?Qty:\s*(\d+)/is', $invHtml, $matches, PREG_SET_ORDER);
            if (empty($matches)) {
                preg_match_all('/catalogitem.page\?P=([^"&]+)[\s\S]*?Quantity[^0-9]*(\d+)/is', $invHtml, $matches, PREG_SET_ORDER);
            }
            foreach ($matches as $m) {
                $pcode = trim($m[1]);
                $qty = (int)trim($m[2]);
                if ($pcode === '' || $qty <= 0) continue;
                $childId = $this->ensurePart($pcode);
                if ($childId) {
                    $pp = $pdo->prepare('INSERT INTO part_parts (parent_part_id, child_part_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)');
                    $pp->execute([$parentId, $childId, $qty]);
                    $count++;
                }
            }
        }
        return $count;
    }
    private function extractSectionItems(string $html, string $sectionTitle): array {
        if (!$html) return [];
        $items = [];
        $pos = stripos($html, $sectionTitle);
        if ($pos === false) return [];
        $snippet = substr($html, $pos, 4000);
        if (preg_match_all('/catalogitem\\.page\\?P=([^"&\\s]+)/i', $snippet, $m)) {
            foreach (array_unique($m[1]) as $code) {
                $items[] = ['code' => html_entity_decode($code)];
            }
        }
        return $items;
    }

    public function scrapeColors(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        $html = $this->fetch('https://www.bricklink.com/catalogColors.asp?itemType=P');
        preg_match_all('/color=(\d+)[^>]*>\s*([^<]+)/i', $html, $ms, PREG_SET_ORDER);
        
        $pdo = Config::db();
        foreach ($ms as $m) {
            $code = trim($m[1]);
            $name = trim($m[2]);
            $st = $pdo->prepare('INSERT INTO colors (color_name, color_code) VALUES (?,?) ON DUPLICATE KEY UPDATE color_code=?');
            $st->execute([$name, $code, $code]);
        }
        header('Location: /admin/config');
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

    private function extract(string $html, string $pattern): ?string {
        if (!$html) return null;
        if (preg_match($pattern, $html, $m)) return trim(html_entity_decode($m[1]));
        return null;
    }

    private function saveImage(?string $url, string $type, string $code): ?string {
        if (!$url) return null;
        $data = @file_get_contents($url);
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

    private function ensurePart(string $code): ?int {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT id FROM parts WHERE part_code=?');
        $st->execute([$code]);
        $id = (int)($st->fetchColumn() ?: 0);
        if ($id) return $id;

        $url = 'https://www.bricklink.com/v2/catalog/catalogitem.page?P=' . urlencode($code);
        $html = $this->fetch($url);
        
        $name = $this->extract($html, '/<title>(.*?)<\/title>/i') ?: $code;
        $img = $this->extract($html, '/<img[^>]+src="([^"]+)"[^>]*class="img-item"/i');
        $localImg = $this->saveImage($img, 'parts', $code);

        $s = $pdo->prepare('INSERT INTO parts (name, part_code, image_url, bricklink_url) VALUES (?,?,?,?)');
        $s->execute([$name, $code, $localImg ?: $img, $url]);
        
        return (int)$pdo->lastInsertId();
    }

    private function ensureColorByCode(string $code): ?int {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT id FROM colors WHERE color_code=?');
        $st->execute([$code]);
        $id = (int)($st->fetchColumn() ?: 0);
        if ($id) return $id;
        return null;
    }

    public function seedColors(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $this->scrapeColors();
    }

    public function seedParts(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        header('Location: /admin/config');
    }

    public function seedSets(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        header('Location: /admin/config');
    }
}
