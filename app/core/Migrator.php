<?php
declare(strict_types=1);
namespace App\Core;
use App\Config\Config;
use PDO;
class Migrator {
    public static function applyAll(string $dir = __DIR__ . '/../../database/migrations'): array {
        $pdo = Config::db();
        if (!is_dir($dir)) return ['applied' => [], 'skipped' => []];
        $files = array_values(array_filter(scandir($dir), function($f){return preg_match('/\.sql$/', $f);}));
        sort($files);
        self::ensureTable($pdo);
        $applied = [];
        $skipped = [];
        foreach ($files as $file) {
            if (self::isApplied($pdo, $file)) {
                $skipped[] = $file;
                continue;
            }
            $sql = file_get_contents($dir . '/' . $file) ?: '';
            $sql = self::expandSource($sql);
            self::executeSqlBatch($pdo, $sql);
            self::markApplied($pdo, $file);
            $applied[] = $file;
        }
        return ['applied' => $applied, 'skipped' => $skipped];
    }
    private static function ensureTable(PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(200) UNIQUE NOT NULL, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    }
    private static function isApplied(PDO $pdo, string $file): bool {
        $st = $pdo->prepare('SELECT 1 FROM migrations WHERE filename=?');
        $st->execute([$file]);
        return (bool)$st->fetchColumn();
    }
    private static function markApplied(PDO $pdo, string $file): void {
        $st = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
        $st->execute([$file]);
    }
    private static function executeSqlBatch(PDO $pdo, string $sql): void {
        $statements = [];
        $buf = '';
        $len = strlen($sql);
        for ($i=0; $i<$len; $i++) {
            $ch = $sql[$i];
            $buf .= $ch;
            if ($ch === ';') {
                $statements[] = $buf;
                $buf = '';
            }
        }
        if (trim($buf) !== '') $statements[] = $buf;
        foreach ($statements as $s) {
            $trim = trim(self::stripComments($s));
            if ($trim === '') continue;
            $pdo->exec($trim);
        }
    }
    private static function stripComments(string $s): string {
        $s = preg_replace('/--.*?(\r?\n)/', '$1', $s);
        $s = preg_replace('/\/\*[\s\S]*?\*\//', '', $s);
        return $s;
    }
    private static function expandSource(string $sql): string {
        return preg_replace_callback('/SOURCE\s+([^\s;]+);?/i', function($m){
            $path = __DIR__ . '/../../' . $m[1];
            return file_exists($path) ? (file_get_contents($path) ?: '') : '';
        }, $sql);
    }
}
