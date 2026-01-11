<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Security;
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
            'csrf' => Security::csrfToken(),
        ]);
    }
    public function gitPull(): void {
        $this->requirePost();
        // Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $before = trim($this->cmd('git rev-parse HEAD'));
        $pull = $this->cmd('git pull 2>&1');
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
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function scanImages(): void {
        $this->requirePost();
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        set_time_limit(0);
        $pdo = Config::db();
        $log = [];

        // 1. Ensure columns exist
        $this->ensureColumnExists($pdo, 'parts', 'img_url');
        $this->ensureColumnExists($pdo, 'themes', 'img_url');
        $this->ensureColumnExists($pdo, 'sets', 'img_url');
        
        $log[] = "Schema verified (img_url columns checked).";

        // 2. Scan Parts
        $baseImagesDir = $this->findImagesDir();
        if ($baseImagesDir) {
            $log[] = "Found images directory: $baseImagesDir";
            
            // Scan Parts
            $partsDir = $this->findSubDir($baseImagesDir, ['parts', 'Parts']);
            if ($partsDir) {
                $count = $this->scanAndLink($pdo, $partsDir, 'parts', 'part_num');
                $log[] = "Parts: Linked $count local images from $partsDir.";
            } else {
                $log[] = "Parts: Sub-directory 'parts' or 'Parts' not found in $baseImagesDir.";
            }

            // Scan Themes
            $themesDir = $this->findSubDir($baseImagesDir, ['themes', 'Themes']);
            if ($themesDir) {
                $count = $this->scanAndLink($pdo, $themesDir, 'themes', 'id');
                $log[] = "Themes: Linked $count local images from $themesDir.";
            } else {
                $log[] = "Themes: Sub-directory 'themes' or 'Themes' not found in $baseImagesDir.";
            }

            // Scan Sets
            $setsDir = $this->findSubDir($baseImagesDir, ['sets', 'Sets']);
            if ($setsDir) {
                $count = $this->scanAndLink($pdo, $setsDir, 'sets', 'set_num', 'img_url');
                $log[] = "Sets: Linked $count local images from $setsDir.";
            } else {
                $log[] = "Sets: Sub-directory 'sets' or 'Sets' not found in $baseImagesDir.";
            }
        } else {
            $log[] = "Error: Could not find 'images' directory. Checked: ../../public/images, ../../images, ../../public_html/images";
        }

        // Return view with log
        $this->view('admin/update', [
            'scan_log' => implode("\n", $log),
            'csrf' => Security::csrfToken(),
            // Pass minimal other vars to avoid undefined variable errors in view
            'local' => '', 'remote' => '', 'local_short' => '', 'remote_short' => '', 'status' => '', 'last_backup' => '' 
        ]);
    }

    private function findImagesDir(): ?string {
        $candidates = [
            __DIR__ . '/../../public/images',
            __DIR__ . '/../../images',
            __DIR__ . '/../public/images', // if app/Controllers/../public
            $_SERVER['DOCUMENT_ROOT'] . '/images',
            $_SERVER['DOCUMENT_ROOT'] . '/public/images'
        ];

        foreach ($candidates as $path) {
            if (is_dir($path)) {
                return realpath($path);
            }
        }
        return null;
    }

    private function findSubDir(string $base, array $names): ?string {
        foreach ($names as $name) {
            $path = $base . DIRECTORY_SEPARATOR . $name;
            if (is_dir($path)) {
                return $path;
            }
        }
        return null;
    }

    private function ensureColumnExists(PDO $pdo, string $table, string $column): void {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("ALTER TABLE $table ADD COLUMN $column VARCHAR(255)");
            }
        } catch (\Exception $e) {
            // SQLite syntax might differ, attempt standard SQL or ignore if generic
            // For SQLite: PRAGMA table_info(table)
            // But usually ALTER TABLE ADD COLUMN is supported in SQLite too
            try {
                 $pdo->exec("ALTER TABLE $table ADD COLUMN $column VARCHAR(255)");
            } catch (\Exception $e2) {
                // Ignore if exists or error
            }
        }
    }

    private function scanAndLink(PDO $pdo, string $baseDir, string $table, string $idCol, string $urlCol = 'img_url'): int {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDir));
        $count = 0;
        $stmt = $pdo->prepare("UPDATE $table SET $urlCol = ? WHERE $idCol = ?");

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $filename = $file->getBasename('.' . $file->getExtension());
                    // For parts, filename is the part_num (e.g. u8004b)
                    // Construct relative path
                    $fullPath = $file->getPathname();
                    // Rel path from public
                    $relPath = '/images/' . str_replace('\\', '/', substr($fullPath, strpos($fullPath, 'images') + 7));
                    
                    // Update DB
                    $stmt->execute([$relPath, $filename]);
                    if ($stmt->rowCount() > 0) {
                        $count++;
                    }
                }
            }
        }
        return $count;
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
