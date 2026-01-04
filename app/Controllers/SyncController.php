<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Security;
use App\Models\Part;
class SyncController extends Controller {
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
        $image = $this->extract($html, '/<img[^>]+src="([^"]+)"[^>]*class="img-item"/i') ?: null;
        $years = null;
        $weight = null;
        $stud = null;
        $package = null;
        $data = [
            'name' => $name,
            'part_code' => $partCode,
            'image_url' => $image,
            'bricklink_url' => $url,
            'years_released' => $years,
            'weight' => $weight,
            'stud_dimensions' => $stud,
            'package_dimensions' => $package,
        ];
        $existing = Part::findByCode($partCode);
        if ($existing) {
            Part::update((int)$existing['id'], $data);
        } else {
            Part::create($data);
        }
        header('Location: /parts?synced=1');
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
}

