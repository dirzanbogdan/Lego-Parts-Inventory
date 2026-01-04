<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Security;
use App\Models\Part;
use App\Models\Color;
use App\Models\Inventory;
class PartsController extends Controller {
    public function home(): void {
        $this->render('dashboard/home', []);
    }
    public function index(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        $parts = Part::all($limit, $offset);
        $colors = Color::all();
        $this->render('parts/index', ['parts' => $parts, 'colors' => $colors]);
    }
    public function view(): void {
        $id = (int)($_GET['id'] ?? 0);
        $part = Part::find($id);
        if (!$part) {
            http_response_code(404);
            echo 'not found';
            return;
        }
        $inv = Inventory::getByPart($id);
        $this->render('parts/view', ['part' => $part, 'inventory' => $inv]);
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
        Part::update($id, $data);
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
        $q = trim($_GET['q'] ?? '');
        $pdo = \App\Config\Config::db();
        $sql = 'SELECT p.*, c.name as category_name FROM parts p LEFT JOIN categories c ON c.id=p.category_id WHERE 1';
        $args = [];
        if ($q !== '') {
            $sql .= ' AND (p.part_code LIKE ? OR p.name LIKE ?)';
            $args[] = "%$q%";
            $args[] = "%$q%";
        }
        if (isset($_GET['color'])) {
            $sql .= ' AND EXISTS(SELECT 1 FROM part_colors pc JOIN colors co ON co.id=pc.color_id WHERE pc.part_id=p.id AND co.color_name=?)';
            $args[] = $_GET['color'];
        }
        if (isset($_GET['category'])) {
            $sql .= ' AND c.name=?';
            $args[] = $_GET['category'];
        }
        if (isset($_GET['year'])) {
            $sql .= ' AND p.years_released LIKE ?';
            $args[] = "%{$_GET['year']}%";
        }
        if (isset($_GET['available'])) {
            $sql .= ' AND EXISTS(SELECT 1 FROM part_colors pc WHERE pc.part_id=p.id AND pc.quantity_in_inventory>0)';
        }
        if (isset($_GET['min_weight'])) {
            $sql .= ' AND p.weight>=?';
            $args[] = (float)$_GET['min_weight'];
        }
        if (isset($_GET['max_weight'])) {
            $sql .= ' AND p.weight<=?';
            $args[] = (float)$_GET['max_weight'];
        }
        $sql .= ' ORDER BY p.name LIMIT 200';
        $st = $pdo->prepare($sql);
        $st->execute($args);
        $parts = $st->fetchAll();
        $this->render('search/index', ['parts' => $parts, 'query' => $q]);
    }
}

