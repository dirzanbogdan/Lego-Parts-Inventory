<?php

namespace App\Models;

use App\Core\Config;
use PDO;

class Part {
    public $part_num;
    public $name;
    public $part_cat_id;
    public $part_material;

    public static function findAll(int $limit = 50, int $offset = 0): array {
        $pdo = Config::db();
        $stmt = $pdo->prepare("SELECT * FROM parts ORDER BY name ASC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function count(): int {
        $pdo = Config::db();
        return (int)$pdo->query("SELECT COUNT(*) FROM parts")->fetchColumn();
    }

    public static function find(string $part_num): ?self {
        $pdo = Config::db();
        $stmt = $pdo->prepare("SELECT * FROM parts WHERE part_num = ?");
        $stmt->execute([$part_num]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetch() ?: null;
    }

    public static function search(string $query): array {
        $pdo = Config::db();
        $stmt = $pdo->prepare("SELECT * FROM parts WHERE name LIKE ? OR part_num LIKE ? LIMIT 50");
        $term = "%$query%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function getColors(): array {
        // Find which colors this part exists in (via inventory_parts)
        $pdo = Config::db();
        $sql = "
            SELECT c.*, COALESCE(up.quantity, 0) as user_quantity, MAX(ip.img_url) as img_url
            FROM inventory_parts ip
            JOIN colors c ON ip.color_id = c.id
            LEFT JOIN user_parts up ON (up.part_num = ip.part_num AND up.color_id = ip.color_id AND up.user_id = 1)
            WHERE ip.part_num = ?
            GROUP BY c.id
            ORDER BY c.name
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->part_num]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateInventory(string $part_num, int $color_id, int $quantity): void {
        $pdo = Config::db();
        // Upsert
        $sql = "
            INSERT INTO user_parts (user_id, part_num, color_id, quantity)
            VALUES (1, ?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$part_num, $color_id, $quantity, $quantity]);
    }
}
