<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Theme;

class ThemesController extends Controller {
    public function index() {
        $allThemes = Theme::getAll();
        
        $themesById = [];
        foreach ($allThemes as $t) {
            $t->children = [];
            $themesById[$t->id] = $t;
        }

        $tree = [];
        foreach ($themesById as $id => $theme) {
            if ($theme->parent_id && isset($themesById[$theme->parent_id])) {
                $themesById[$theme->parent_id]->children[] = $theme;
            } else {
                $tree[] = $theme;
            }
        }

        // Sort children recursively (optional, if DB sort isn't enough)
        // Since we iterated in order, they should be roughly ordered, but grouping might shift things.
        // Let's rely on DB sort for now.

        $this->view('themes/index', [
            'themesTree' => $tree
        ]);
    }
}
