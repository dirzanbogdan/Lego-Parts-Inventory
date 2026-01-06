<?php
declare(strict_types=1);
namespace App\Models;
use App\Config\Config;
use PDO;
class SetModel {
    public static function all(int $limit = 50, int $offset = 0): array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT * FROM sets ORDER BY year DESC, set_name LIMIT ? OFFSET ?');
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->bindValue(2, $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
    public static function find(int $id): ?array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT * FROM sets WHERE id=?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }
    public static function parts(int $setId): array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT sp.*, p.name, p.part_code, p.image_url, c.color_name, IFNULL(pc.quantity_in_inventory, 0) as quantity_in_inventory 
                             FROM set_parts sp 
                             JOIN parts p ON p.id=sp.part_id 
                             LEFT JOIN colors c ON c.id=sp.color_id 
                             LEFT JOIN part_colors pc ON pc.part_id=sp.part_id AND (pc.color_id=sp.color_id OR sp.color_id IS NULL)
                             WHERE sp.set_id=? 
                             ORDER BY p.name');
        $st->execute([$setId]);
        return $st->fetchAll();
    }
    public static function create(array $data): bool {
        $pdo = Config::db();
        $st = $pdo->prepare('INSERT INTO sets (set_name, set_code, type, year, image, instructions_url) VALUES (?,?,?,?,?,?)');
        return $st->execute([$data['set_name'], $data['set_code'], $data['type'], $data['year'], $data['image'] ?? null, $data['instructions_url'] ?? null]);
    }
    public static function update(int $id, array $data): bool {
        $pdo = Config::db();
        $st = $pdo->prepare('UPDATE sets SET set_name=?, set_code=?, type=?, year=?, image=?, instructions_url=? WHERE id=?');
        return $st->execute([$data['set_name'], $data['set_code'], $data['type'], $data['year'], $data['image'] ?? null, $data['instructions_url'] ?? null, $id]);
    }
    public static function delete(int $id): bool {
        $pdo = Config::db();
        $st = $pdo->prepare('DELETE FROM sets WHERE id=?');
        return $st->execute([$id]);
    }
    public static function setProgress(int $setId): array {
        $pdo = Config::db();
        $st = $pdo->prepare('SELECT sp.part_id, sp.color_id, sp.quantity, IFNULL(pc.quantity_in_inventory,0) AS have FROM set_parts sp LEFT JOIN part_colors pc ON pc.part_id=sp.part_id AND (pc.color_id=sp.color_id OR sp.color_id IS NULL) WHERE sp.set_id=?');
        $st->execute([$setId]);
        $rows = $st->fetchAll();
        $need = 0;
        $have = 0;
        foreach ($rows as $r) {
            $need += (int)$r['quantity'];
            $have += min((int)$r['quantity'], (int)$r['have']);
        }
        $missing = max(0, $need - $have);
        $pct = $need > 0 ? round(($have / $need) * 100, 2) : 0;
        return ['need' => $need, 'have' => $have, 'missing' => $missing, 'progress' => $pct];
    }
    public static function favorite(int $userId, int $setId): bool {
        $pdo = Config::db();
        $st = $pdo->prepare('INSERT INTO favorites (user_id, set_id) VALUES (?,?) ON DUPLICATE KEY UPDATE set_id=set_id');
        return $st->execute([$userId, $setId]);
    }
}
