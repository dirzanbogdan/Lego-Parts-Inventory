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
    public function seedColors(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $pdo = Config::db();
        $colors = [
            ['Black','0'],['White','15'],['Red','5'],['Blue','7'],['Yellow','3'],
            ['Light Bluish Gray','86'],['Dark Bluish Gray','85'],['Green','2'],
        ];
        foreach ($colors as [$n,$c]) {
            $st = $pdo->prepare('INSERT INTO colors (color_name, color_code) VALUES (?,?) ON DUPLICATE KEY UPDATE color_code=?');
            $st->execute([$n,$c,$c]);
        }
        header('Location: /admin/config?ok=colors');
    }
    public function seedParts(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $pdo = Config::db();
        $cats = ['Basic','Technic'];
        foreach ($cats as $cat) {
            $pdo->prepare('INSERT INTO categories (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name')->execute([$cat]);
        }
        $catId = (int)$pdo->query("SELECT id FROM categories WHERE name='Basic'")->fetchColumn();
        $parts = [
            ['Brick 2x4','3001',null,$catId,'','https://www.bricklink.com/v2/catalog/catalogitem.page?P=3001','',null,'2x4','',null],
            ['Plate 2x4','3020',null,$catId,'','https://www.bricklink.com/v2/catalog/catalogitem.page?P=3020','',null,'2x4','',null],
            ['Tile 2x2','3068b',null,$catId,'','https://www.bricklink.com/v2/catalog/catalogitem.page?P=3068b','',null,'2x2','',null],
        ];
        foreach ($parts as $p) {
            $pdo->prepare('INSERT INTO parts (name, part_code, version, category_id, image_url, bricklink_url, years_released, weight, stud_dimensions, package_dimensions, no_of_parts) VALUES (?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), category_id=VALUES(category_id), image_url=VALUES(image_url), bricklink_url=VALUES(bricklink_url)')->execute($p);
        }
        header('Location: /admin/config?ok=parts');
    }
    public function seedSets(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $pdo = Config::db();
        $pdo->prepare('INSERT INTO sets (set_name, set_code, type, year, image) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE set_name=VALUES(set_name), type=VALUES(type), year=VALUES(year), image=VALUES(image)')
            ->execute(['Starter Pack','SP-001','custom',2025,'']);
        $setId = (int)$pdo->query("SELECT id FROM sets WHERE set_code='SP-001'")->fetchColumn();
        $p1 = $pdo->query("SELECT id FROM parts WHERE part_code='3001'")->fetchColumn();
        $p2 = $pdo->query("SELECT id FROM parts WHERE part_code='3020'")->fetchColumn();
        $p3 = $pdo->query("SELECT id FROM parts WHERE part_code='3068b'")->fetchColumn();
        $black = $pdo->query("SELECT id FROM colors WHERE color_name='Black'")->fetchColumn();
        $white = $pdo->query("SELECT id FROM colors WHERE color_name='White'")->fetchColumn();
        if ($setId && $p1) $pdo->prepare('INSERT INTO set_parts (set_id, part_id, color_id, quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)')->execute([$setId,(int)$p1,(int)$black,10]);
        if ($setId && $p2) $pdo->prepare('INSERT INTO set_parts (set_id, part_id, color_id, quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)')->execute([$setId,(int)$p2,(int)$white,8]);
        if ($setId && $p3) $pdo->prepare('INSERT INTO set_parts (set_id, part_id, color_id, quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)')->execute([$setId,(int)$p3,null,6]);
        header('Location: /admin/config?ok=sets');
    }
}
