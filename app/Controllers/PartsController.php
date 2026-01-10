<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Part;

class PartsController extends Controller {
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
