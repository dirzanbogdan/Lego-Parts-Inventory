<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Security;
use App\Models\SetModel;
use App\Models\Part;
use App\Models\Color;
class SetsController extends Controller {
    public function index(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        $sets = SetModel::all($limit, $offset);
        $this->render('sets/index', ['sets' => $sets]);
    }
    public function view(): void {
        $id = (int)($_GET['id'] ?? 0);
        $set = SetModel::find($id);
        if (!$set) {
            http_response_code(404);
            echo 'not found';
            return;
        }
        $parts = SetModel::parts($id);
        $progress = SetModel::setProgress($id);
        $debug = null;
        if ((int)($_GET['debug'] ?? 0) === 1) {
            $pdo = \App\Config\Config::db();
            $cntParts = (int)($pdo->prepare('SELECT COUNT(*) FROM set_parts WHERE set_id=?')->execute([$id]) ? $pdo->prepare('SELECT COUNT(*) FROM set_parts WHERE set_id=?')->execute([$id]) : 0);
            // fetch sample set_parts rows
            $st = $pdo->prepare('SELECT sp.*, p.name, p.part_code, c.color_name FROM set_parts sp LEFT JOIN parts p ON p.id=sp.part_id LEFT JOIN colors c ON c.id=sp.color_id WHERE sp.set_id=? ORDER BY sp.id DESC LIMIT 20');
            $st->execute([$id]);
            $rows = $st->fetchAll();
            $debug = [
                'set_record' => $set,
                'set_parts_count' => $cntParts,
                'set_parts_sample' => $rows,
            ];
        }
        $this->render('sets/view', ['set' => $set, 'parts' => $parts, 'progress' => $progress, 'debug' => $debug]);
    }
    public function create(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $data = [
            'set_name' => trim($_POST['set_name'] ?? ''),
            'set_code' => trim($_POST['set_code'] ?? ''),
            'type' => $_POST['type'] ?? 'official',
            'year' => (int)($_POST['year'] ?? 0),
            'image' => $_POST['image'] ?? null,
        ];
        SetModel::create($data);
        header('Location: /sets');
    }
    public function update(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'set_name' => trim($_POST['set_name'] ?? ''),
            'set_code' => trim($_POST['set_code'] ?? ''),
            'type' => $_POST['type'] ?? 'official',
            'year' => (int)($_POST['year'] ?? 0),
            'image' => $_POST['image'] ?? null,
        ];
        SetModel::update($id, $data);
        header('Location: /sets/view?id=' . $id);
    }
    public function delete(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        SetModel::delete($id);
        header('Location: /sets');
    }
    public function favorite(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $setId = (int)($_POST['set_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        SetModel::favorite($userId, $setId);
        header('Location: /sets/view?id=' . $setId);
    }
    public function missing(): void {
        $id = (int)($_GET['id'] ?? 0);
        $progress = SetModel::setProgress($id);
        header('Content-Type: text/plain');
        echo 'Missing: ' . $progress['missing'];
    }
}
