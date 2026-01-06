<?php
declare(strict_types=1);
namespace App\Models;
use App\Config\Config;
use PDO;
class Part {
    public static function all(int $limit = 50, int $offset = 0): array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT p.*, c.name as category_name FROM parts p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.name LIMIT ? OFFSET ?');
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->bindValue(2, $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
    public static function find(int $id): ?array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT * FROM parts WHERE id=?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }
    public static function findByCode(string $code): ?array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT * FROM parts WHERE part_code=? LIMIT 1');
        $st->execute([$code]);
        $row = $st->fetch();
        return $row ?: null;
    }
    public static function create(array $data): bool {
        $pdo = Config::db();
        $st = $pdo->prepare('INSERT INTO parts (name, part_code, version, category_id, image_url, bricklink_url, years_released, weight, stud_dimensions, package_dimensions, no_of_parts, related_items) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        return $st->execute([
            $data['name'],
            $data['part_code'],
            $data['version'] ?? null,
            $data['category_id'] ?? null,
            $data['image_url'] ?? null,
            $data['bricklink_url'] ?? null,
            $data['years_released'] ?? null,
            $data['weight'] ?? null,
            $data['stud_dimensions'] ?? null,
            $data['package_dimensions'] ?? null,
            $data['no_of_parts'] ?? null,
            $data['related_items'] ?? null,
        ]);
    }
    public static function update(int $id, array $data): bool {
        $pdo = Config::db();
        $st = $pdo->prepare('UPDATE parts SET name=?, part_code=?, version=?, category_id=?, image_url=?, bricklink_url=?, years_released=?, weight=?, stud_dimensions=?, package_dimensions=?, no_of_parts=?, related_items=? WHERE id=?');
        return $st->execute([
            $data['name'],
            $data['part_code'],
            $data['version'] ?? null,
            $data['category_id'] ?? null,
            $data['image_url'] ?? null,
            $data['bricklink_url'] ?? null,
            $data['years_released'] ?? null,
            $data['weight'] ?? null,
            $data['stud_dimensions'] ?? null,
            $data['package_dimensions'] ?? null,
            $data['no_of_parts'] ?? null,
            $data['related_items'] ?? null,
            $id,
        ]);
    }
    public static function delete(int $id): bool {
        $pdo = Config::db();
        $st = $pdo->prepare('DELETE FROM parts WHERE id=?');
        return $st->execute([$id]);
    }
}
