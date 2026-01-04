<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Security;
use App\Config\Config;
class ConfigController extends Controller {
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
        $raw = trim($_POST['code'] ?? '');
        if ($raw === '') {http_response_code(422);echo 'invalid';return;}
        $code = preg_replace('/\\s*\\(Inv\\)\\s*$/i', '', $raw);
        $colorParam = null;
        if (preg_match('/\\bC=(\\d+)/i', $raw, $cm)) $colorParam = $cm[1];
        $anchor = $colorParam ? ('#T=C&C=' . $colorParam) : '';
        $url = 'https://www.bricklink.com/v2/catalog/catalogitem.page?P=' . urlencode($code) . $anchor;
        $html = $this->fetch($url);
        if (!$colorParam) {
            if (preg_match('/#T=C&C=(\\d+)/i', $html, $am)) $colorParam = $am[1];
        }
        $name = $this->extract($html, '/<title>(.*?)<\/title>/i') ?: $code;
        $img = $this->extract($html, '/<img[^>]+src="([^"]+)"[^>]*class="img-item"/i');
        $localImg = $this->saveImage($img, 'parts', $code);
        $version = null;
        if (preg_match('/^[^\\s]+?([A-Za-z].*)$/', $code, $vm)) $version = $vm[1];
        $weight = null;
        if (preg_match('/Item\\s*Weight:\\s*([\\d\\.]+)/i', $html, $wm)) $weight = (float)$wm[1];
        $stud = null;
        if (preg_match('/Stud\\s*Dimensions:\\s*([^<\\n]+)/i', $html, $sm)) $stud = trim($sm[1]);
        $pkg = null;
        if (preg_match('/Item\\s*Dim\\w*:\\s*([^<\\n]+)/i', $html, $pm)) $pkg = trim($pm[1]);
        $composition = null;
        if (preg_match('/\\((\\s*\\d+[A-Za-z0-9]*\\s*\\/\\s*\\d+[A-Za-z0-9]*\\s*)\\)/', $name, $cmps)) {
            $composition = trim($cmps[1]);
        }
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT id FROM parts WHERE part_code=?');
        $st->execute([$code]);
        $id = (int)($st->fetchColumn() ?: 0);
        if ($id) {
            $s2 = $pdo->prepare('UPDATE parts SET name=?, image_url=?, bricklink_url=?, version=?, weight=?, stud_dimensions=?, package_dimensions=?, composition=? WHERE id=?');
            $s2->execute([$name, ($localImg ?: $img), $url, $version, $weight, $stud, $pkg, $composition, $id]);
        } else {
            $s3 = $pdo->prepare('INSERT INTO parts (name, part_code, image_url, bricklink_url, version, weight, stud_dimensions, package_dimensions, composition) VALUES (?,?,?,?,?,?,?,?,?)');
            $s3->execute([$name, $code, ($localImg ?: $img), $url, $version, $weight, $stud, $pkg, $composition]);
            $id = (int)$pdo->lastInsertId();
        }
        if ($colorParam) {
            $cid = $this->ensureColorByCode($colorParam);
            if ($cid) {
                $pdo->prepare('INSERT INTO part_colors (part_id, color_id, quantity_in_inventory) VALUES (?,?,0) ON DUPLICATE KEY UPDATE quantity_in_inventory=quantity_in_inventory')
                    ->execute([$id, $cid]);
            }
        }
        echo 'ok';
    }
    public function scrapeSetsOne(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $code = trim($_POST['code'] ?? '');
        if ($code === '') {http_response_code(422);echo 'invalid';return;}
        $url = 'https://www.bricklink.com/v2/catalog/catalogitem.page?S=' . urlencode($code);
        $html = $this->fetch($url);
        $name = $this->extract($html, '/<title>(.*?)<\/title>/i') ?: $code;
        $img = $this->extract($html, '/<img[^>]+src="([^"]+)"[^>]*class="img-item"/i');
        $localImg = $this->saveImage($img, 'sets', $code);
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT id FROM sets WHERE set_code=?');
        $st->execute([$code]);
        $sid = (int)($st->fetchColumn() ?: 0);
        if ($sid) {
            $s2 = $pdo->prepare('UPDATE sets SET set_name=?, image=? WHERE id=?');
            $s2->execute([$name, $localImg ?: $img, $sid]);
        } else {
            $s3 = $pdo->prepare('INSERT INTO sets (set_name, set_code, type, year, image) VALUES (?,?,?,?,?)');
            $s3->execute([$name, $code, 'official', null, $localImg ?: $img]);
            $sid = (int)$pdo->lastInsertId();
        }
        $invUrl = 'https://www.bricklink.com/catalogItemInv.asp?S=' . urlencode($code);
        $invHtml = $this->fetch($invUrl);
        preg_match_all('/catalogitem.page\\?P=([^"&]+).*?color=([^"&]+).*?Qty:\\s*(\\d+)/is', $invHtml, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $pcode = trim($m[1]);
            $colorCode = trim($m[2]);
            $qty = (int)trim($m[3]);
            if ($pcode === '' || $qty<=0) continue;
            $pId = $this->ensurePart($pcode);
            $cId = $this->ensureColorByCode($colorCode);
            if ($pId) {
                $stp = $pdo->prepare('INSERT INTO set_parts (set_id, part_id, color_id, quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)');
                $stp->execute([$sid, $pId, $cId ?: null, $qty]);
            }
        }
        echo 'ok';
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
        preg_match_all('/color=(\\d+)[^>]*>\\s*([^<]+)/i', $html, $ms, PREG_SET_ORDER);
        $pdo = Config::db();
        foreach ($ms as $m) {
            $code = trim($m[1]);
            $name = trim($m[2]);
            $st = $pdo->prepare('INSERT INTO colors (color_name, color_code) VALUES (?,?) ON DUPLICATE KEY UPDATE color_code=?');
            $st->execute([$name, $code, $code]);
        }
        header('Location: /admin/config?ok=colors');
    }
    private function fetch(string $url): string {
        $opts = ['http' => ['method' => 'GET', 'header' => "User-Agent: LegoInventory\r\n"]];
        return @file_get_contents($url, false, stream_context_create($opts)) ?: '';
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
        $path = $dir . '/' . preg_replace('/[^a-zA-Z0-9_\\-]/','_', $code) . '.' . $ext;
        @file_put_contents($path, $data);
        if (file_exists($path)) return '/uploads/' . $type . '/' . basename($path);
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
