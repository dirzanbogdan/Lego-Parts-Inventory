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

    public static function findAll(int $limit = 50, int $offset = 0): array {
        $pdo = Config::db();
        $stmt = $pdo->prepare("SELECT * FROM sets ORDER BY year DESC, name ASC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function find(string $set_num): ?self {
        $pdo = Config::db();
        $stmt = $pdo->prepare("SELECT * FROM sets WHERE set_num = ?");
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
        $stmt = $pdo->prepare("SELECT * FROM sets WHERE name LIKE ? OR set_num LIKE ? LIMIT 50");
        $term = "%$query%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public function getInventory(): array {
        $pdo = Config::db();
        // Get the latest inventory version for this set
        $sql = "
            SELECT ip.*, p.name as part_name, c.name as color_name, c.rgb
            FROM inventories i
            JOIN inventory_parts ip ON i.id = ip.inventory_id
            JOIN parts p ON ip.part_num = p.part_num
            JOIN colors c ON ip.color_id = c.id
            WHERE i.set_num = ?
            ORDER BY i.version DESC
            LIMIT 1000
        "; 
        // Note: Logic for 'latest version' might need refinement if multiple inventories exist.
        // Usually, we pick the one with highest version.
        // But strict SQL requires subquery.
        
        $sql = "
            SELECT ip.*, p.name as part_name, c.name as color_name, c.rgb
            FROM inventory_parts ip
            JOIN parts p ON ip.part_num = p.part_num
            JOIN colors c ON ip.color_id = c.id
            WHERE ip.inventory_id = (
                SELECT id FROM inventories WHERE set_num = ? ORDER BY version DESC LIMIT 1
            )
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->set_num]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
