<?php
declare(strict_types=1);
namespace App\Models;
use App\Config\Config;
use PDO;
class Inventory {
    public static function getByPart(int $partId): array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT pc.*, c.color_name, c.color_code FROM part_colors pc JOIN colors c ON c.id=pc.color_id WHERE pc.part_id=? ORDER BY c.color_name');
        $st->execute([$partId]);
        return $st->fetchAll();
    }
    public static function updateQuantity(int $partId, int $colorId, int $delta, int $userId, string $reason = ''): bool {
        $pdo = Config::db();
        $pdo->beginTransaction();
        $st = $pdo->prepare('INSERT INTO part_colors (part_id, color_id, quantity_in_inventory) VALUES (?,?,0) ON DUPLICATE KEY UPDATE quantity_in_inventory=quantity_in_inventory');
        $st->execute([$partId, $colorId]);
        $st2 = $pdo->prepare('UPDATE part_colors SET quantity_in_inventory=quantity_in_inventory+? WHERE part_id=? AND color_id=?');
        $st2->execute([$delta, $partId, $colorId]);
        $st3 = $pdo->prepare('INSERT INTO inventory_history (part_id, color_id, delta, reason, user_id) VALUES (?,?,?,?,?)');
        $st3->execute([$partId, $colorId, $delta, $reason, $userId]);
        $pdo->commit();
        return true;
    }
    public static function historyByPart(int $partId, int $limit = 20): array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT ih.*, c.color_name, u.username FROM inventory_history ih LEFT JOIN colors c ON c.id=ih.color_id LEFT JOIN users u ON u.id=ih.user_id WHERE ih.part_id=? ORDER BY ih.created_at DESC LIMIT ?');
        $st->bindValue(1, $partId, PDO::PARAM_INT);
        $st->bindValue(2, $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
    public static function exportAll(): array {
        $pdo = Config::db();
        $st = $pdo->query('SELECT p.part_code, p.name, c.color_name, pc.quantity_in_inventory FROM part_colors pc JOIN parts p ON p.id=pc.part_id JOIN colors c ON c.id=pc.color_id ORDER BY p.part_code, c.color_name');
        return $st->fetchAll();
    }
}
