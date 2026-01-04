<?php
declare(strict_types=1);
namespace App\Config;
use PDO;
use PDOException;
class Config {
    public static function db(): PDO {
        static $pdo = null;
        if ($pdo) return $pdo;
        $host = getenv('DB_HOST') ?: 'localhost';
        $db = getenv('DB_NAME') ?: 'lego_inventory';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, $user, $pass, $opts);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'DB error';
            exit;
        }
        return $pdo;
    }
    public static function bricklink(): array {
        return [
            'consumer_key' => getenv('BRICKLINK_CONSUMER_KEY') ?: '',
            'consumer_secret' => getenv('BRICKLINK_CONSUMER_SECRET') ?: '',
            'token' => getenv('BRICKLINK_TOKEN') ?: '',
            'token_secret' => getenv('BRICKLINK_TOKEN_SECRET') ?: '',
        ];
    }
    public static function app(): array {
        $baseUrl = getenv('BASE_URL') ?: '';
        return ['base_url' => $baseUrl];
    }
}
