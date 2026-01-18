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

    public static function findAll(int $limit = 50, int $offset = 0, $filtersOrThemeId = null): array {
        $pdo = Config::db();
        $sql = "
            SELECT s.*, t.name as theme_name 
            FROM sets s 
            LEFT JOIN themes t ON s.theme_id = t.id 
            WHERE 1=1 
        ";
        
        $params = [];
        $filters = [];
        if (is_array($filtersOrThemeId)) {
            $filters = $filtersOrThemeId;
        } elseif ($filtersOrThemeId !== null) {
            $filters['theme_id'] = (int)$filtersOrThemeId;
        }

        if (!empty($filters['theme_id'])) {
            $sql .= " AND s.theme_id = ? ";
            $params[] = (int)$filters['theme_id'];
        }

        if (!empty($filters['has_image'])) {
            if ($filters['has_image'] === 'with') {
                $sql .= " AND s.img_url IS NOT NULL AND s.img_url <> '' AND s.img_url <> '/images/no-image.png'";
            } elseif ($filters['has_image'] === 'without') {
                $sql .= " AND (s.img_url IS NULL OR s.img_url = '' OR s.img_url = '/images/no-image.png')";
            }
        }

        if (!empty($filters['year_from'])) {
            $sql .= " AND s.year >= ? ";
            $params[] = (int)$filters['year_from'];
        }

        if (!empty($filters['year_to'])) {
            $sql .= " AND s.year <= ? ";
            $params[] = (int)$filters['year_to'];
        }

        if (!empty($filters['can_build'])) {
            $userId = 1;
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM inventory_parts ip1
                    JOIN parts p1 ON p1.part_num = ip1.part_num
                    LEFT JOIN part_categories pc1 ON pc1.id = p1.part_cat_id
                    WHERE ip1.inventory_id = (
                        SELECT id FROM inventories WHERE set_num = s.set_num ORDER BY version DESC LIMIT 1
                    )
                    AND ip1.is_spare = 0
                    AND (pc1.name IS NULL OR pc1.name <> 'Stickers')
                    AND (pc1.id IS NULL OR pc1.id <> 58)
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM inventory_parts ip2
                    JOIN parts p2 ON p2.part_num = ip2.part_num
                    LEFT JOIN part_categories pc2 ON pc2.id = p2.part_cat_id
                    LEFT JOIN user_parts up2 ON (
                        up2.part_num = ip2.part_num 
                        AND up2.color_id = ip2.color_id 
                        AND up2.user_id = ?
                    )
                    WHERE ip2.inventory_id = (
                        SELECT id FROM inventories WHERE set_num = s.set_num ORDER BY version DESC LIMIT 1
                    )
                    AND ip2.is_spare = 0
                    AND (pc2.name IS NULL OR pc2.name <> 'Stickers')
                    AND (pc2.id IS NULL OR pc2.id <> 58)
                    AND COALESCE(up2.quantity, 0) < ip2.quantity
                )
            ";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY s.year DESC, s.name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function count($filtersOrThemeId = null): int {
        $pdo = Config::db();
        $sql = "SELECT COUNT(*) FROM sets s WHERE 1=1";
        $params = [];

        $filters = [];
        if (is_array($filtersOrThemeId)) {
            $filters = $filtersOrThemeId;
        } elseif ($filtersOrThemeId !== null) {
            $filters['theme_id'] = (int)$filtersOrThemeId;
        }

        if (!empty($filters['theme_id'])) {
            $sql .= " AND s.theme_id = ?";
            $params[] = (int)$filters['theme_id'];
        }

        if (!empty($filters['has_image'])) {
            if ($filters['has_image'] === 'with') {
                $sql .= " AND s.img_url IS NOT NULL AND s.img_url <> '' AND s.img_url <> '/images/no-image.png'";
            } elseif ($filters['has_image'] === 'without') {
                $sql .= " AND (s.img_url IS NULL OR s.img_url = '' OR s.img_url = '/images/no-image.png')";
            }
        }

        if (!empty($filters['year_from'])) {
            $sql .= " AND s.year >= ? ";
            $params[] = (int)$filters['year_from'];
        }

        if (!empty($filters['year_to'])) {
            $sql .= " AND s.year <= ? ";
            $params[] = (int)$filters['year_to'];
        }

        if (!empty($filters['can_build'])) {
            $userId = 1;
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM inventory_parts ip1
                    JOIN parts p1 ON p1.part_num = ip1.part_num
                    LEFT JOIN part_categories pc1 ON pc1.id = p1.part_cat_id
                    WHERE ip1.inventory_id = (
                        SELECT id FROM inventories WHERE set_num = s.set_num ORDER BY version DESC LIMIT 1
                    )
                    AND ip1.is_spare = 0
                    AND (pc1.name IS NULL OR pc1.name <> 'Stickers')
                    AND (pc1.id IS NULL OR pc1.id <> 58)
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM inventory_parts ip2
                    JOIN parts p2 ON p2.part_num = ip2.part_num
                    LEFT JOIN part_categories pc2 ON pc2.id = p2.part_cat_id
                    LEFT JOIN user_parts up2 ON (
                        up2.part_num = ip2.part_num 
                        AND up2.color_id = ip2.color_id 
                        AND up2.user_id = ?
                    )
                    WHERE ip2.inventory_id = (
                        SELECT id FROM inventories WHERE set_num = s.set_num ORDER BY version DESC LIMIT 1
                    )
                    AND ip2.is_spare = 0
                    AND (pc2.name IS NULL OR pc2.name <> 'Stickers')
                    AND (pc2.id IS NULL OR pc2.id <> 58)
                    AND COALESCE(up2.quantity, 0) < ip2.quantity
                )
            ";
            $params[] = $userId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
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
        $result = $stmt->fetch();
        return $result ?: null;
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
                p.img_url as generic_img_url,
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
