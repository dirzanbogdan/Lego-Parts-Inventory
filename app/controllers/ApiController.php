<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
class ApiController extends Controller {
    public function parts(): void {
        $pdo = \App\Config\Config::db();
        $st = $pdo->query('SELECT id, name, part_code FROM parts ORDER BY name LIMIT 500');
        $this->json($st->fetchAll());
    }
    public function sets(): void {
        $pdo = \App\Config\Config::db();
        $st = $pdo->query('SELECT id, set_name, set_code, year FROM sets ORDER BY year DESC LIMIT 500');
        $this->json($st->fetchAll());
    }
    public function inventory(): void {
        $pdo = \App\Config\Config::db();
        $st = $pdo->query('SELECT p.part_code, c.color_name, pc.quantity_in_inventory FROM part_colors pc JOIN parts p ON p.id=pc.part_id JOIN colors c ON c.id=pc.color_id');
        $this->json($st->fetchAll());
    }
    public function suggest(): void {
        $q = trim($_GET['q'] ?? '');
        $pdo = \App\Config\Config::db();
        $st = $pdo->prepare('SELECT id, name, part_code FROM parts WHERE name LIKE ? OR part_code LIKE ? ORDER BY name LIMIT 10');
        $st->execute(["%$q%", "%$q%"]);
        $this->json($st->fetchAll());
    }
}

