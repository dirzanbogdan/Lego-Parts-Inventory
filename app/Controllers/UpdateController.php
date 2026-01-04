<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Security;
use App\Core\Migrator;
use App\Config\Config;
use PDO;
class UpdateController extends Controller {
    public function page(): void {
        Security::requireRole('admin');
        $local = $this->cmd('git rev-parse HEAD');
        $remote = $this->cmd('git ls-remote origin HEAD');
        $status = $this->cmd('git status -sb');
        $this->render('admin/update', [
            'local' => trim($local),
            'remote' => trim(explode("\t", trim($remote))[0] ?? ''),
            'status' => $status,
            'last_backup' => $this->lastBackupPath(),
        ]);
    }
    public function backup(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $dir = __DIR__ . '/../../backups';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $fname = 'db-' . date('Ymd-His') . '.sql';
        $path = $dir . '/' . $fname;
        $ok = $this->dumpWithMysqldump($path);
        if (!$ok) $ok = $this->dumpDataSql($path);
        if ($ok) {
            header('Location: /admin/update?backup=' . urlencode($fname));
        } else {
            http_response_code(500);
            echo 'backup failed';
        }
    }
    public function apply(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $before = trim($this->cmd('git rev-parse HEAD'));
        $pull = $this->cmd('git pull 2>&1');
        $migrations = Migrator::applyAll();
        $after = trim($this->cmd('git rev-parse HEAD'));
        $this->render('admin/update', [
            'local' => $after,
            'remote' => trim(explode("\t", trim($this->cmd('git ls-remote origin HEAD')))[0] ?? ''),
            'status' => $this->cmd('git status -sb'),
            'last_backup' => $this->lastBackupPath(),
            'pull_log' => $pull,
            'before' => $before,
            'after' => $after,
            'migrations' => $migrations,
        ]);
    }
    private function cmd(string $command): string {
        if (!function_exists('shell_exec')) return '';
        $out = @shell_exec($command);
        return $out ? (string)$out : '';
    }
    private function dumpWithMysqldump(string $path): bool {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $db = getenv('DB_NAME') ?: '';
        $user = getenv('DB_USER') ?: '';
        $pass = getenv('DB_PASS') ?: '';
        if (!$db || !$user) return false;
        if (!function_exists('shell_exec')) return false;
        $cmd = 'mysqldump -h ' . escapeshellarg($host) . ' -P ' . escapeshellarg($port) . ' -u ' . escapeshellarg($user) . ' --password=' . escapeshellarg($pass) . ' ' . escapeshellarg($db) . ' > ' . escapeshellarg($path);
        $res = @shell_exec($cmd);
        return file_exists($path) && filesize($path) > 0;
    }
    private function dumpDataSql(string $path): bool {
        try {
            $pdo = Config::db();
            $tables = [];
            $st = $pdo->query('SHOW TABLES');
            while ($row = $st->fetch(PDO::FETCH_NUM)) $tables[] = $row[0];
            $fh = fopen($path, 'w');
            if (!$fh) return false;
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
            foreach ($tables as $t) {
                $rs = $pdo->query('SELECT * FROM `' . str_replace('`','',$t) . '`');
                $cols = [];
                for ($i=0; $i<$rs->columnCount(); $i++) {
                    $meta = $rs->getColumnMeta($i);
                    $cols[] = '`' . $meta['name'] . '`';
                }
                while ($row = $rs->fetch(PDO::FETCH_NUM)) {
                    $vals = array_map(function($v) use ($pdo){
                        if ($v === null) return 'NULL';
                        return $pdo->quote((string)$v);
                    }, $row);
                    $sql = 'INSERT INTO `' . $t . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ');' . "\n";
                    fwrite($fh, $sql);
                }
            }
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($fh);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    private function lastBackupPath(): ?string {
        $dir = __DIR__ . '/../../backups';
        if (!is_dir($dir)) return null;
        $files = array_values(array_filter(scandir($dir) ?: [], function($f){return preg_match('/\\.sql$/', $f);} ));
        if (!$files) return null;
        rsort($files);
        return '/backups/' . $files[0];
    }
}
