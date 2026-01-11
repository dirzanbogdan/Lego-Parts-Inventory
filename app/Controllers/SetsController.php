<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Set;

class SetsController extends Controller {
    public function index() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $theme_id = isset($_GET['theme_id']) ? (int)$_GET['theme_id'] : null;
        $limit = 24;
        $offset = ($page - 1) * $limit;
        
        $sets = Set::findAll($limit, $offset, $theme_id);
        $total = Set::count($theme_id);
        $totalPages = ceil($total / $limit);

        $this->view('sets/index', [
            'sets' => $sets,
            'page' => $page,
            'totalPages' => $totalPages,
            'theme_id' => $theme_id
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
