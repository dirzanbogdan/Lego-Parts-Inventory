<?php

namespace App\Models;

use App\Core\Config;
use PDO;

class Part {
    public $part_num;
    public $name;
    public $part_cat_id;
    public $part_material;

    public static function findAll(int $limit = 50, int $offset = 0, array $filters = []): array {
        $pdo = Config::db();
        $sql = "SELECT * FROM parts WHERE 1=1";
        $params = [];

        if (!empty($filters['has_image'])) {
            if ($filters['has_image'] === 'with') {
                $sql .= " AND img_url IS NOT NULL AND img_url <> '' AND img_url <> '/images/no-image.png'";
            } elseif ($filters['has_image'] === 'without') {
                $sql .= " AND (img_url IS NULL OR img_url = '' OR img_url = '/images/no-image.png')";
            }
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND part_cat_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $sql .= " ORDER BY name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        foreach ($params as $idx => $val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($idx + 1, $val, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function count(array $filters = []): int {
        $pdo = Config::db();
        $sql = "SELECT COUNT(*) FROM parts WHERE 1=1";
        $params = [];

        if (!empty($filters['has_image'])) {
            if ($filters['has_image'] === 'with') {
                $sql .= " AND img_url IS NOT NULL AND img_url <> '' AND img_url <> '/images/no-image.png'";
            } elseif ($filters['has_image'] === 'without') {
                $sql .= " AND (img_url IS NULL OR img_url = '' OR img_url = '/images/no-image.png')";
            }
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND part_cat_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
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
            SELECT 
                c.*, 
                COALESCE(up.quantity, 0) as user_quantity, 
                MAX(ip.img_url) as img_url,
                p.img_url as generic_img_url
            FROM inventory_parts ip
            JOIN colors c ON ip.color_id = c.id
            JOIN parts p ON ip.part_num = p.part_num
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
