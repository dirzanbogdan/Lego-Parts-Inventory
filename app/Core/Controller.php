<?php

namespace App\Core;

class Controller {
    protected function view(string $view, array $data = []) {
        extract($data);
        
        // Define path to the specific view file
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            $viewPath = $viewFile;
            // Include layout, which will include $viewPath
            require_once __DIR__ . '/../views/layout.php';
        } else {
            die("View does not exist: $view");
        }
    }
}
