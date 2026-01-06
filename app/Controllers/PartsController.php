<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Part;
use App\Models\Color;
use App\Models\Inventory;
use App\Config\Config;
use PDO;

class PartsController extends Controller {

    public function home(): void {
        $this->render('dashboard/home', []);
    }

    public function index(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $q = trim($_GET['q'] ?? '');
        $parts = [];
        
        if ($q !== '') {
            $pdo = Config::db();
            // Fulltext search
            $sql = "SELECT p.*, c.name as category_name, MATCH(p.name, p.part_code) AGAINST(? IN BOOLEAN MODE) as score 
                    FROM parts p 
                    LEFT JOIN categories c ON c.id=p.category_id 
                    WHERE MATCH(p.name, p.part_code) AGAINST(? IN BOOLEAN MODE) 
                    ORDER BY score DESC LIMIT ? OFFSET ?";
            $st = $pdo->prepare($sql);
            $st->bindValue(1, $q);
            $st->bindValue(2, $q);
            $st->bindValue(3, $limit, PDO::PARAM_INT);
            $st->bindValue(4, $offset, PDO::PARAM_INT);
            $st->execute();
            $parts = $st->fetchAll();
        } else {
            $parts = Part::all($limit, $offset);
        }

        $colors = Color::all();
        $this->render('parts/index', ['parts' => $parts, 'colors' => $colors, 'query' => $q]);
    }

    public function view(): void {
        $id = (int)($_GET['id'] ?? 0);
        $part = Part::find($id);

        if (!$part) {
            http_response_code(404);
            echo 'not found';
            return;
        }

        $pdo = Config::db();
        $inv = Inventory::getByPart($id);

        // Related Items
        $relatedItemsRaw = json_decode($part['related_items'] ?? '[]', true);
        $relatedItems = [];
        if (is_array($relatedItemsRaw)) {
            if (array_keys($relatedItemsRaw) !== range(0, count($relatedItemsRaw) - 1)) {
                $sections = ['counterparts', 'alternate_molds', 'alt_molds', 'related'];
                foreach ($sections as $sec) {
                    if (!empty($relatedItemsRaw[$sec]) && is_array($relatedItemsRaw[$sec])) {
                        foreach ($relatedItemsRaw[$sec] as $it) {
                            if (is_string($it)) $it = ['code' => $it];
                            if (!empty($it['code']) && is_string($it['code'])) {
                                $relatedItems[] = ['code' => $it['code'], 'name' => $it['name'] ?? ''];
                            }
                        }
                    }
                }
            } else {
                foreach ($relatedItemsRaw as $it) {
                    if (is_string($it)) $it = ['code' => $it];
                    if (!empty($it['code']) && is_string($it['code'])) {
                        $relatedItems[] = ['code' => $it['code'], 'name' => $it['name'] ?? ''];
                    }
                }
            }
            foreach ($relatedItems as &$item) {
                $p = Part::findByCode($item['code']);
                $item['id'] = $p['id'] ?? null;
                if ($item['id']) {
                    $item['inventory'] = Inventory::getByPart($item['id']);
                }
            }
            unset($item);
        }

        // Constituent parts (Item Consists Of)
        $consistOf = $pdo->prepare("SELECT p.*, pp.quantity FROM part_parts pp JOIN parts p ON p.id=pp.child_part_id WHERE pp.parent_part_id=?");
        $consistOf->execute([$id]);
        $consistOfParts = $consistOf->fetchAll();

        // Appears in
        $appearsIn = $pdo->prepare("SELECT COUNT(DISTINCT set_id) FROM set_parts WHERE part_id=?");
        $appearsIn->execute([$id]);
        $appearsInCount = $appearsIn->fetchColumn();
        
        $colors = Color::all();

        $this->render('parts/view', [
            'part' => $part,
            'inventory' => $inv,
            'relatedItems' => $relatedItems,
            'consistOfParts' => $consistOfParts,
            'appearsInCount' => $appearsInCount,
            'colors' => $colors
        ]);
    }

    public function create(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'part_code' => trim($_POST['part_code'] ?? ''),
            'version' => $_POST['version'] ?? null,
            'category_id' => $_POST['category_id'] ?? null,
            'image_url' => $_POST['image_url'] ?? null,
            'bricklink_url' => $_POST['bricklink_url'] ?? null,
            'years_released' => $_POST['years_released'] ?? null,
            'weight' => $_POST['weight'] ?? null,
            'stud_dimensions' => $_POST['stud_dimensions'] ?? null,
            'package_dimensions' => $_POST['package_dimensions'] ?? null,
            'no_of_parts' => $_POST['no_of_parts'] ?? null,
        ];

        if (!$data['name'] || !$data['part_code']) {
            http_response_code(422);
            echo 'invalid';
            return;
        }

        Part::create($data);
        $this->logHistory('part', (int)Config::db()->lastInsertId(), 'Created part');
        header('Location: /parts');
    }

    public function update(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $existing = $id ? Part::find($id) : null;
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'part_code' => trim($_POST['part_code'] ?? ''),
            'version' => $_POST['version'] ?? null,
            'category_id' => $_POST['category_id'] ?? null,
            'bricklink_url' => $_POST['bricklink_url'] ?? null,
            'years_released' => $_POST['years_released'] ?? null,
            'weight' => $_POST['weight'] ?? null,
            'stud_dimensions' => $_POST['stud_dimensions'] ?? null,
            'package_dimensions' => $_POST['package_dimensions'] ?? null,
            'no_of_parts' => $_POST['no_of_parts'] ?? null,
            'related_items' => $_POST['related_items'] ?? ($existing['related_items'] ?? null),
        ];

        $imageLocal = null;
        if (!empty($_FILES['image_file']['tmp_name'])) {
            $dir = __DIR__ . '/../../public/uploads/parts';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $code = $data['part_code'] ?: ('part-' . $id);
            $fname = $code . '.' . strtolower($ext);
            $dest = $dir . '/' . $fname;
            @move_uploaded_file($_FILES['image_file']['tmp_name'], $dest);
            if (file_exists($dest)) {
                $imageLocal = '/uploads/parts/' . $fname;
            }
        }

        if ($imageLocal) {
            $data['image_url'] = $imageLocal;
        }

        Part::update($id, $data);
        $this->logHistory('part', $id, 'Updated part details');
        header('Location: /parts/view?id=' . $id);
    }

    public function delete(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        Part::delete($id);
        header('Location: /parts');
    }

    public function search(): void {
        // Alias to index with q
        $_GET['page'] = 1;
        $this->index();
    }
    
    public function history(): void {
        $id = (int)($_GET['id'] ?? 0);
        $type = $_GET['type'] ?? 'part';
        
        $pdo = Config::db();
        $st = $pdo->prepare("SELECT h.*, u.username FROM entity_history h LEFT JOIN users u ON u.id=h.user_id WHERE entity_type=? AND entity_id=? ORDER BY created_at DESC");
        $st->execute([$type, $id]);
        $history = $st->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode($history);
    }
    
    private function logHistory(string $type, int $id, string $changes): void {
        $pdo = Config::db();
        $uid = $_SESSION['user']['id'] ?? null;
        $st = $pdo->prepare("INSERT INTO entity_history (entity_type, entity_id, user_id, changes) VALUES (?,?,?,?)");
        $st->execute([$type, $id, $uid, $changes]);
    }
}
