<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Set;
use App\Models\Part;

class HomeController extends Controller {
    public function index() {
        $recentSets = Set::findAll(12);
        $this->view('home', ['sets' => $recentSets]);
    }

    public function search() {
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'sets';
        
        $results = [];
        if ($query) {
            if ($type === 'sets') {
                $results = Set::search($query);
            } else {
                $results = Part::search($query);
            }
        }

        $this->view('search', [
            'results' => $results, 
            'query' => $query, 
            'type' => $type
        ]);
    }
}
