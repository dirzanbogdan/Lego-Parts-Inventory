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
        $code = trim($_POST['code'] ?? '');
        if ($code === '') {http_response_code(422);echo 'invalid';return;}
        $url = 'https://www.bricklink.com/v2/catalog/catalogitem.page?P=' . urlencode($code);
        $html = $this->fetch($url);
        $name = $this->extract($html, '/<title>(.*?)<\/title>/i') ?: $code;
        $img = $this->extract($html, '/<img[^>]+src="([^"]+)"[^>]*class="img-item"/i');
        $localImg = $this->saveImage($img, 'parts', $code);
        $data = [
            'name' => $name,
            'part_code' => $code,
            'image_url' => $localImg ?: $img,
            'bricklink_url' => $url,
        ];
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT id FROM parts WHERE part_code=?');
        $st->execute([$code]);
        $id = (int)($st->fetchColumn() ?: 0);
        if ($id) {
            $s2 = $pdo->prepare('UPDATE parts SET name=?, image_url=?, bricklink_url=? WHERE id=?');
            $s2->execute([$name, $data['image_url'], $url, $id]);
        } else {
            $s3 = $pdo->prepare('INSERT INTO parts (name, part_code, image_url, bricklink_url) VALUES (?,?,?,?)');
            $s3->execute([$name, $code, $data['image_url'], $url]);
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
