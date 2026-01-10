<?php

namespace App\Models;

use App\Core\Config;
use PDO;

class Part {
    public $part_num;
    public $name;
    public $part_cat_id;
    public $part_material;

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
            SELECT DISTINCT c.*
            FROM inventory_parts ip
            JOIN colors c ON ip.color_id = c.id
            WHERE ip.part_num = ?
            ORDER BY c.name
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->part_num]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
