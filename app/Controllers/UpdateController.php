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
        // Security::requireRole('admin');
        $local = $this->cmd('git rev-parse HEAD');
        $remote = $this->cmd('git ls-remote origin HEAD');
        $status = $this->cmd('git status -sb');
        $local = trim($local);
        $remoteHash = trim(explode("\t", trim($remote))[0] ?? '');
        $localShort = $local ? substr($local, -7) : '';
        $remoteShort = ($remoteHash && $remoteHash !== $local) ? substr($remoteHash, -7) : '';
        $this->view('admin/update', [
            'local' => $local,
            'remote' => $remoteHash,
            'local_short' => $localShort,
            'remote_short' => $remoteShort,
            'status' => $status,
            'last_backup' => $this->lastBackupPath(),
            'clear_report' => null,
            'schema_report' => null,
            'csrf' => Security::csrfToken(),
        ]);
    }
    public function backup(): void {
        $this->requirePost();
        // Security::requireRole('admin');
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
        // Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $before = trim($this->cmd('git rev-parse HEAD'));
        $pull = $this->cmd('git pull 2>&1');
        try {
            $migrations = Migrator::applyAll();
        } catch (\Throwable $e) {
            $migrations = ['applied' => [], 'skipped' => [], 'error' => $e->getMessage()];
        }
        $after = trim($this->cmd('git rev-parse HEAD'));
        $remoteHash = trim(explode("\t", trim($this->cmd('git ls-remote origin HEAD')))[0] ?? '');
        $localShort = $after ? substr($after, -7) : '';
        $remoteShort = ($remoteHash && $remoteHash !== $after) ? substr($remoteHash, -7) : '';
        $this->view('admin/update', [
            'local' => $after,
            'remote' => $remoteHash,
            'local_short' => $localShort,
            'remote_short' => $remoteShort,
            'status' => $this->cmd('git status -sb'),
            'last_backup' => $this->lastBackupPath(),
            'pull_log' => $pull,
            'before' => $before,
            'after' => $after,
            'migrations' => $migrations,
            'clear_report' => null,
            'schema_report' => null,
        ]);
    }
    public function clearDb(): void {
        $this->requirePost();
        // Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $pdo = Config::db();
        // Updated table list to match actual schema
        $tables = ['inventory_parts','inventories','minifigs','sets','elements','part_relationships','parts','part_categories','colors','themes'];
        $existing = [];
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $t) {
            try {
                $pdo->exec('TRUNCATE TABLE ' . $t);
                $existing[] = $t;
            } catch (\Throwable $e) {
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $local = trim($this->cmd('git rev-parse HEAD'));
        $remoteHash = trim(explode("\t", trim($this->cmd('git ls-remote origin HEAD')))[0] ?? '');
        $localShort = $local ? substr($local, -7) : '';
        $remoteShort = ($remoteHash && $remoteHash !== $local) ? substr($remoteHash, -7) : '';
        $this->view('admin/update', [
            'local' => $local,
            'remote' => $remoteHash,
            'local_short' => $localShort,
            'remote_short' => $remoteShort,
            'status' => $this->cmd('git status -sb'),
            'last_backup' => $this->lastBackupPath(),
            'clear_report' => ['cleared' => $existing],
            'schema_report' => null,
        ]);
    }
    public function verifySchema(): void {
        $this->requirePost();
        // Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $pdo = Config::db();
        $expected = [
            'users' => ['username','password_hash','role','created_at'],
            'categories' => ['name'],
            'parts' => ['name','part_code','version','category_id','image_url','bricklink_url','years_released','weight','stud_dimensions','package_dimensions','no_of_parts','related_items'],
            'colors' => ['color_name','color_code'],
            'part_colors' => ['part_id','color_id','quantity_in_inventory','condition_state','purchase_price'],
            'inventory_history' => ['part_id','color_id','delta','reason','user_id','created_at'],
            'sets' => ['set_name','set_code','type','year','image','instructions_url'],
            'set_parts' => ['set_id','part_id','color_id','quantity'],
            'entity_history' => ['entity_type','entity_id','user_id','changes','created_at'],
            'part_parts' => ['parent_part_id','child_part_id','quantity'],
            'migrations' => ['filename','applied_at'],
        ];
        $report = ['missing_tables' => [], 'missing_columns' => [], 'ok_tables' => []];
        foreach ($expected as $table => $cols) {
            try {
                $st = $pdo->query('SHOW COLUMNS FROM ' . $table);
                $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
                if (!$rows) {
                    $report['missing_tables'][] = $table;
                    continue;
                }
                $present = array_map(function($r){return $r['Field'] ?? '';}, $rows);
                $missing = array_values(array_diff($cols, $present));
                if (!empty($missing)) {
                    $report['missing_columns'][] = ['table' => $table, 'columns' => $missing];
                } else {
                    $report['ok_tables'][] = $table;
                }
            } catch (\Throwable $e) {
                $report['missing_tables'][] = $table;
            }
        }
        $local = trim($this->cmd('git rev-parse HEAD'));
        $remoteHash = trim(explode("\t", trim($this->cmd('git ls-remote origin HEAD')))[0] ?? '');
        $localShort = $local ? substr($local, -7) : '';
        $remoteShort = ($remoteHash && $remoteHash !== $local) ? substr($remoteHash, -7) : '';
        $this->view('admin/update', [
            'local' => $local,
            'remote' => $remoteHash,
            'local_short' => $localShort,
            'remote_short' => $remoteShort,
            'status' => $this->cmd('git status -sb'),
            'last_backup' => $this->lastBackupPath(),
            'clear_report' => null,
            'schema_report' => $report,
            'csrf' => Security::csrfToken(),
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
                // Skip if table doesn't exist
                try {
                    $rs = $pdo->query('SELECT * FROM `' . str_replace('`','',$t) . '`');
                } catch (\Throwable $e) {
                    continue;
                }
                
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
            // Log error if needed
            error_log("Backup error: " . $e->getMessage());
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
