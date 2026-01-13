<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Config;
use PDO;
class User {
    public static function findByUsername(string $username): ?array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT * FROM users WHERE username=? LIMIT 1');
        $st->execute([$username]);
        $row = $st->fetch();
        return $row ?: null;
    }
    public static function create(string $username, string $password, string $role = 'user'): bool {
        $pdo = Config::db();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $st = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?,?,?)');
        return $st->execute([$username, $hash, $role]);
    }
}
