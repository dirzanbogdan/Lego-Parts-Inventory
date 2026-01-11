<?php

namespace App\Models;

use App\Core\Config;
use PDO;

class Theme {
    public $id;
    public $name;
    public $parent_id;

    public static function findAll(int $limit = 50, int $offset = 0): array {
        $pdo = Config::db();
        $stmt = $pdo->prepare("
            SELECT t.*, p.name as parent_name 
            FROM themes t 
            LEFT JOIN themes p ON t.parent_id = p.id 
            ORDER BY t.name ASC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function count(): int {
        $pdo = Config::db();
        return (int)$pdo->query("SELECT COUNT(*) FROM themes")->fetchColumn();
    }
}
