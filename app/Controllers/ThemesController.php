<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Theme;

class ThemesController extends Controller {
    public function index() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $themes = Theme::findAll($limit, $offset);
        $total = Theme::count();
        $totalPages = ceil($total / $limit);

        $this->view('themes/index', [
            'themes' => $themes,
            'page' => $page,
            'totalPages' => $totalPages
        ]);
    }
}
