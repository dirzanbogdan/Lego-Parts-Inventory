<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Config;
use App\Core\Security;
use PDO;

class MyController extends Controller {
    private $userId = 1;

    public function addSet() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /');
            return;
        }
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $set_num = $_POST['set_num'] ?? null;
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        if (!$set_num) {
            header('Location: /sets');
            return;
        }
        $pdo = Config::db();
        $this->ensureUserSetsTable($pdo);
        $stmt = $pdo->prepare("INSERT INTO user_sets (user_id, set_num, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
        $stmt->execute([$this->userId, $set_num, $quantity]);
        header("Location: /my/sets");
        exit;
    }

    public function updateSet() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /my/sets');
            return;
        }
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $set_num = $_POST['set_num'] ?? null;
        $quantity = max(0, (int)($_POST['quantity'] ?? 0));
        if (!$set_num) {
            header('Location: /my/sets');
            return;
        }
        $pdo = Config::db();
        $this->ensureUserSetsTable($pdo);
        if ($quantity === 0) {
            $stmt = $pdo->prepare("DELETE FROM user_sets WHERE user_id = ? AND set_num = ?");
            $stmt->execute([$this->userId, $set_num]);
        } else {
            $stmt = $pdo->prepare("UPDATE user_sets SET quantity = ? WHERE user_id = ? AND set_num = ?");
            $stmt->execute([$quantity, $this->userId, $set_num]);
        }
        header("Location: /my/sets");
        exit;
    }

    public function removeSet() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /my/sets');
            return;
        }
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $set_num = $_POST['set_num'] ?? null;
        if ($set_num) {
            $pdo = Config::db();
            $this->ensureUserSetsTable($pdo);
            $stmt = $pdo->prepare("DELETE FROM user_sets WHERE user_id = ? AND set_num = ?");
            $stmt->execute([$this->userId, $set_num]);
        }
        header("Location: /my/sets");
        exit;
    }

    public function mySets() {
        $pdo = Config::db();
        $this->ensureUserSetsTable($pdo);
        $sets = [];
        try {
            $sql = "
                SELECT us.set_num, us.quantity, s.name, s.year, s.img_url, t.name AS theme_name
                FROM user_sets us
                JOIN sets s ON s.set_num = us.set_num
                LEFT JOIN themes t ON s.theme_id = t.id
                WHERE us.user_id = ?
                ORDER BY s.year DESC, s.name ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->userId]);
            $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $sets = [];
        }
        $error = $_GET['error'] ?? null;
        $success = $_GET['success'] ?? null;
        $this->view('my/sets', ['sets' => $sets, 'error' => $error, 'success' => $success]);
    }

    public function myParts() {
        $pdo = Config::db();
        $this->ensureUserPartsTable($pdo);
        $parts = [];
        try {
            $sql = "
                SELECT 
                    up.part_num, up.color_id, up.quantity,
                    p.name AS part_name, p.img_url AS generic_img_url,
                    c.name AS color_name, c.rgb,
                    MAX(ip.img_url) AS img_url
                FROM user_parts up
                JOIN parts p ON p.part_num = up.part_num
                JOIN colors c ON c.id = up.color_id
                LEFT JOIN inventory_parts ip ON (ip.part_num = up.part_num AND ip.color_id = up.color_id)
                WHERE up.user_id = ?
                GROUP BY up.part_num, up.color_id
                ORDER BY p.name ASC, c.name ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->userId]);
            $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $parts = [];
        }
        $this->view('my/parts', ['parts' => $parts]);
    }

    private function ensureUserSetsTable(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_sets (
                user_id INT NOT NULL,
                set_num VARCHAR(50) NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                PRIMARY KEY (user_id, set_num)
            )
        ");
    }

    private function ensureUserPartsTable(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_parts (
                user_id INT NOT NULL,
                part_num VARCHAR(50) NOT NULL,
                color_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 0,
                PRIMARY KEY (user_id, part_num, color_id)
            )
        ");
    }

    public function buildSet() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /my/sets');
            return;
        }
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $set_num = $_POST['set_num'] ?? null;
        if (!$set_num) {
            header('Location: /my/sets?error=' . urlencode('Set invalid'));
            return;
        }
        $pdo = Config::db();
        $this->ensureUserPartsTable($pdo);

        // Fetch latest inventory for the set, excluding spare parts
        $sql = "
            SELECT 
                ip.part_num, ip.color_id, ip.quantity, ip.is_spare,
                p.part_cat_id, pc.name AS part_cat_name
            FROM inventory_parts ip
            JOIN parts p ON p.part_num = ip.part_num
            LEFT JOIN part_categories pc ON pc.id = p.part_cat_id
            WHERE ip.inventory_id = (
                SELECT id FROM inventories WHERE set_num = ? ORDER BY version DESC LIMIT 1
            )
            AND ip.is_spare = 0
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$set_num]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            header('Location: /my/sets?error=' . urlencode('Inventar indisponibil pentru set'));
            return;
        }

        // Determine missing items (exclude stickers)
        $missing = [];
        foreach ($items as $it) {
            $isSticker = (isset($it['part_cat_name']) && $it['part_cat_name'] === 'Stickers') || (isset($it['part_cat_id']) && (int)$it['part_cat_id'] === 58);
            if ($isSticker) {
                continue;
            }
            $up = $pdo->prepare("SELECT quantity FROM user_parts WHERE user_id = ? AND part_num = ? AND color_id = ?");
            $up->execute([$this->userId, $it['part_num'], $it['color_id']]);
            $owned = (int)($up->fetchColumn() ?: 0);
            if ($owned < (int)$it['quantity']) {
                $missing[] = [
                    'part_num' => $it['part_num'],
                    'color_id' => $it['color_id'],
                    'need' => (int)$it['quantity'],
                    'have' => $owned
                ];
            }
        }

        if (!empty($missing)) {
            $msg = 'Nu poți face build: lipsesc piese';
            header('Location: /my/sets?error=' . urlencode($msg));
            return;
        }

        // Deduct quantities atomically
        try {
            $pdo->beginTransaction();
            foreach ($items as $it) {
                $isSticker = (isset($it['part_cat_name']) && $it['part_cat_name'] === 'Stickers') || (isset($it['part_cat_id']) && (int)$it['part_cat_id'] === 58);
                if ($isSticker) continue;
                $qty = (int)$it['quantity'];
                $upd = $pdo->prepare("
                    UPDATE user_parts 
                    SET quantity = quantity - ? 
                    WHERE user_id = ? AND part_num = ? AND color_id = ? AND quantity >= ?
                ");
                $upd->execute([$qty, $this->userId, $it['part_num'], $it['color_id'], $qty]);
            }
            $pdo->commit();
            header('Location: /my/sets?success=' . urlencode('Set construit. Piesele au fost scăzute.'));
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            header('Location: /my/sets?error=' . urlencode('Eroare la scăderea pieselor'));
        }
    }
}
