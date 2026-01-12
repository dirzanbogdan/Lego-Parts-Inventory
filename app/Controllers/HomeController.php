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

    public function apiSearch() {
        header('Content-Type: application/json');
        
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'sets';
        
        $results = [];
        if (strlen($query) >= 2) {
            if ($type === 'sets') {
                $results = Set::search($query);
            } else {
                $results = Part::search($query);
            }
        }
        
        echo json_encode($results);
        exit;
    }
}
