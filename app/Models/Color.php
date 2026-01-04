<?php
declare(strict_types=1);
namespace App\Models;
use App\Config\Config;
use PDO;
class Color {
    public static function all(): array {
        $pdo = Config::db();
        $st = $pdo->query('SELECT * FROM colors ORDER BY color_name');
        return $st->fetchAll();
    }
    public static function findByName(string $name): ?array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT * FROM colors WHERE color_name=? LIMIT 1');
        $st->execute([$name]);
        $row = $st->fetch();
        return $row ?: null;
    }
}
