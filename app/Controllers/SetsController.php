<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Theme;
use App\Models\Set;

class SetsController extends Controller {
    public function index() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $theme_id = isset($_GET['theme_id']) && $_GET['theme_id'] !== '' ? (int)$_GET['theme_id'] : null;
        $has_image = $_GET['has_image'] ?? '';
        $year_from = isset($_GET['year_from']) && $_GET['year_from'] !== '' ? (int)$_GET['year_from'] : null;
        $year_to = isset($_GET['year_to']) && $_GET['year_to'] !== '' ? (int)$_GET['year_to'] : null;
        $can_build = isset($_GET['can_build']) && $_GET['can_build'] === '1';

        $filters = [
            'theme_id' => $theme_id,
            'has_image' => in_array($has_image, ['with', 'without'], true) ? $has_image : '',
            'year_from' => $year_from,
            'year_to' => $year_to,
            'can_build' => $can_build
        ];

        $limit = 24;
        $offset = ($page - 1) * $limit;
        
        $sets = Set::findAll($limit, $offset, $filters);
        $total = Set::count($filters);
        $totalPages = ceil($total / $limit);

        $themes = Theme::getAll();

        $this->view('sets/index', [
            'sets' => $sets,
            'page' => $page,
            'totalPages' => $totalPages,
            'theme_id' => $theme_id,
            'has_image' => $filters['has_image'],
            'year_from' => $filters['year_from'],
            'year_to' => $filters['year_to'],
            'can_build' => $can_build,
            'themes' => $themes
        ]);
    }

    public function show($set_num) {
        $set = Set::find($set_num);
        if (!$set) {
            header("HTTP/1.0 404 Not Found");
            echo "Set not found";
            return;
        }

        $inventory = $set->getInventory();
        $this->view('sets/view', ['set' => $set, 'inventory' => $inventory]);
    }
}
