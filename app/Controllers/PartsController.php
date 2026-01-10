<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Part;

class PartsController extends Controller {
    public function view($part_num) {
        $part = Part::find($part_num);
        if (!$part) {
            header("HTTP/1.0 404 Not Found");
            echo "Part not found";
            return;
        }

        $colors = $part->getColors();
        $this->view('parts/view', ['part' => $part, 'colors' => $colors]);
    }
}
