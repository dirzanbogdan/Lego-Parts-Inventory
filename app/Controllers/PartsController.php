<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Config;
use App\Models\Part;

class PartsController extends Controller {
    public function index() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;

        $has_image = $_GET['has_image'] ?? '';
        $category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;

        $filters = [
            'has_image' => in_array($has_image, ['with', 'without'], true) ? $has_image : '',
            'category_id' => $category_id
        ];
        
        $parts = Part::findAll($limit, $offset, $filters);
        $total = Part::count($filters);
        $totalPages = ceil($total / $limit);

        $pdo = Config::db();
        $categories = $pdo->query("SELECT id, name FROM part_categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('parts/index', [
            'parts' => $parts,
            'page' => $page,
            'totalPages' => $totalPages,
            'has_image' => $filters['has_image'],
            'category_id' => $filters['category_id'],
            'categories' => $categories
        ]);
    }

    public function show($part_num) {
        $part = Part::find($part_num);
        if (!$part) {
            header("HTTP/1.0 404 Not Found");
            echo "Part not found";
            return;
        }

        $colors = $part->getColors();
        $this->view('parts/view', ['part' => $part, 'colors' => $colors]);
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $part_num = $_POST['part_num'];
            $color_id = (int)$_POST['color_id'];
            $quantity = (int)$_POST['quantity'];

            Part::updateInventory($part_num, $color_id, $quantity);
            
            // Redirect back
            header("Location: /parts/$part_num");
            exit;
        }
    }
}
