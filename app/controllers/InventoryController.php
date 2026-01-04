<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Security;
use App\Models\Inventory;
use App\Models\Part;
use App\Models\Color;
class InventoryController extends Controller {
    public function index(): void {
        $partId = (int)($_GET['part_id'] ?? 0);
        $parts = Part::all(200, 0);
        $colors = Color::all();
        $inv = $partId ? Inventory::getByPart($partId) : [];
        $history = $partId ? Inventory::historyByPart($partId) : [];
        $this->render('inventory/index', ['parts' => $parts, 'colors' => $colors, 'inventory' => $inv, 'history' => $history, 'partId' => $partId]);
    }
    public function update(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $partId = (int)($_POST['part_id'] ?? 0);
        $colorId = (int)($_POST['color_id'] ?? 0);
        $delta = (int)($_POST['delta'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $userId = (int)(($_SESSION['user']['id'] ?? 0));
        Inventory::updateQuantity($partId, $colorId, $delta, $userId, $reason);
        header('Location: /inventory?part_id=' . $partId);
    }
    public function export(): void {
        $rows = Inventory::exportAll();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=inventory.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['part_code', 'name', 'color_name', 'quantity']);
        foreach ($rows as $r) fputcsv($out, [$r['part_code'], $r['name'], $r['color_name'], $r['quantity_in_inventory']]);
        fclose($out);
    }
    public function import(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        if (!isset($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            http_response_code(400);
            echo 'no file';
            return;
        }
        $pdo = \App\Config\Config::db();
        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        $header = fgetcsv($fh);
        while (($row = fgetcsv($fh)) !== false) {
            $partCode = $row[0] ?? '';
            $colorName = $row[2] ?? '';
            $qty = (int)($row[3] ?? 0);
            $p = \App\Models\Part::findByCode($partCode);
            $c = \App\Models\Color::findByName($colorName);
            if ($p && $c) {
                $st = $pdo->prepare('INSERT INTO part_colors (part_id, color_id, quantity_in_inventory) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity_in_inventory=?');
                $st->execute([$p['id'], $c['id'], $qty, $qty]);
            }
        }
        fclose($fh);
        header('Location: /inventory');
    }
}
