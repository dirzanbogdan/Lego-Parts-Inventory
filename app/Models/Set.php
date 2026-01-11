<?php

namespace App\Models;

use App\Core\Config;
use PDO;

class Set {
    public $set_num;
    public $name;
    public $year;
    public $theme_id;
    public $num_parts;
    public $img_url;
    public $theme_name;

    public static function findAll(int $limit = 50, int $offset = 0): array {
        $pdo = Config::db();
        $stmt = $pdo->prepare("
            SELECT s.*, t.name as theme_name 
            FROM sets s 
            LEFT JOIN themes t ON s.theme_id = t.id 
            ORDER BY s.year DESC, s.name ASC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function find(string $set_num): ?self {
        $pdo = Config::db();
        $stmt = $pdo->prepare("
            SELECT s.*, t.name as theme_name 
            FROM sets s 
            LEFT JOIN themes t ON s.theme_id = t.id 
            WHERE s.set_num = ?
        ");
        $stmt->execute([$set_num]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        return $stmt->fetch() ?: null;
    }

    public static function count(): int {
        $pdo = Config::db();
        return (int)$pdo->query("SELECT COUNT(*) FROM sets")->fetchColumn();
    }

    public static function search(string $query): array {
        $pdo = Config::db();
        $stmt = $pdo->prepare("
            SELECT s.*, t.name as theme_name 
            FROM sets s 
            LEFT JOIN themes t ON s.theme_id = t.id 
            WHERE s.name LIKE ? OR s.set_num LIKE ? OR t.name LIKE ? 
            LIMIT 50
        ");
        $term = "%$query%";
        $stmt->execute([$term, $term, $term]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function getInventory(): array {
        $pdo = Config::db();
        
        $sql = "
            SELECT 
                ip.*, 
                p.name as part_name, 
                c.name as color_name, 
                c.rgb,
                ip.img_url,
                COALESCE(up.quantity, 0) as user_quantity
            FROM inventory_parts ip
            JOIN parts p ON ip.part_num = p.part_num
            JOIN colors c ON ip.color_id = c.id
            LEFT JOIN user_parts up ON (up.part_num = ip.part_num AND up.color_id = ip.color_id AND up.user_id = 1)
            WHERE ip.inventory_id = (
                SELECT id FROM inventories WHERE set_num = ? ORDER BY version DESC LIMIT 1
            )
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->set_num]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
