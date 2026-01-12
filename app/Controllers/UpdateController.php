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
            'latest_debug_sets' => $this->latestDebugUrl('sets'),
            'latest_debug_parts' => $this->latestDebugUrl('parts'),
            'latest_debug_themes' => $this->latestDebugUrl('themes'),
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

        // 2. Scan recursively starting from public/images (or best guess)
        $baseImagesDir = $this->findBestImagesDir($log);
        
        if ($baseImagesDir) {
            $log[] = "Scanning directory recursively: $baseImagesDir";

            $webBase = '/images';
            $realBase = realpath($baseImagesDir) ?: $baseImagesDir;
            $normRealBase = str_replace('\\', '/', $realBase);
            $isPartsImagesScan = false;
            if (strpos($normRealBase, '/parts and sets/') !== false || strpos($normRealBase, '/parts%20and%20sets/') !== false) {
                $webBase = '/parts_images';
                $isPartsImagesScan = true;
            } elseif (strpos($normRealBase, '/parts and sets') !== false) {
                $webBase = '/parts_images';
                $isPartsImagesScan = true;
            }
            $log[] = "Using web prefix: $webBase";
            if ($isPartsImagesScan) {
                $pdo->exec("UPDATE themes SET img_url = NULL WHERE img_url LIKE '/parts_images/%'");
                $log[] = "Cleared theme images pointing to /parts_images (avoids part photos on themes).";
            }
            
            $stats = [
                'parts' => 0,
                'sets' => 0,
                'themes' => 0
            ];
            $samples = 0;

            // Create iterators
            $dirIterator = new \RecursiveDirectoryIterator(
                $baseImagesDir,
                \FilesystemIterator::SKIP_DOTS
                    | \FilesystemIterator::FOLLOW_SYMLINKS
            );
            $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

            // Prepare statements
            $stmtPart = $pdo->prepare("UPDATE parts SET img_url = ? WHERE part_num = ?");
            $stmtSet = $pdo->prepare("UPDATE sets SET img_url = ? WHERE set_num = ?");
            $stmtTheme = $pdo->prepare("UPDATE themes SET img_url = ? WHERE id = ?");
            $checkPart = $pdo->prepare("SELECT 1 FROM parts WHERE part_num = ? LIMIT 1");
            $checkSet = $pdo->prepare("SELECT 1 FROM sets WHERE set_num = ? LIMIT 1");
            $checkTheme = $pdo->prepare("SELECT 1 FROM themes WHERE id = ? LIMIT 1");
            
            // Normalize base dir for path calculation
            $normBase = str_replace('\\', '/', (realpath($baseImagesDir) ?: $baseImagesDir));

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $ext = strtolower($file->getExtension());
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $filename = $file->getBasename('.' . $file->getExtension());
                        $candidateIds = [$filename];
                        $trimmed = ltrim($filename, '0');
                        if ($trimmed !== '' && $trimmed !== $filename) {
                            $candidateIds[] = $trimmed;
                        }
                        
                        // Calculate relative path
                        $fullPath = $file->getPathname();
                        $normFull = str_replace('\\', '/', $fullPath);
                        
                        // Default to /images prefix
                        // If normBase is found in normFull, replace it
                        if (strpos($normFull, $normBase) === 0) {
                            $relFromBase = substr($normFull, strlen($normBase));
                            $relPath = $webBase . $relFromBase;
                        } else {
                            // Fallback if path mismatch
                            $relPath = $webBase . '/' . $file->getFilename();
                        }

                        if ($samples < 20) {
                            $hasPart = false;
                            $hasSet = false;
                            $hasTheme = false;
                            foreach ($candidateIds as $cid) {
                                $checkPart->execute([$cid]);
                                $hasPart = $hasPart || ($checkPart->fetchColumn() !== false);
                                $checkSet->execute([$cid]);
                                $hasSet = $hasSet || ($checkSet->fetchColumn() !== false);
                                if (!$isPartsImagesScan && !$hasTheme && is_numeric($cid)) {
                                    $checkTheme->execute([$cid]);
                                    $hasTheme = $checkTheme->fetchColumn() !== false;
                                }
                            }
                            $log[] = "Sample: file={$filename}, rel={$relPath}, part=" . ($hasPart ? 'Y' : 'N') . ", set=" . ($hasSet ? 'Y' : 'N') . ", theme=" . ($isPartsImagesScan ? 'N' : ($hasTheme ? 'Y' : 'N'));
                            $samples++;
                        }
                        
                        // Try matching Part
                        foreach ($candidateIds as $cid) {
                            $stmtPart->execute([$relPath, $cid]);
                            if ($stmtPart->rowCount() > 0) {
                                $stats['parts']++;
                                continue 2;
                            }
                        }

                        // Try matching Set
                        foreach ($candidateIds as $cid) {
                            $stmtSet->execute([$relPath, $cid]);
                            if ($stmtSet->rowCount() > 0) {
                                $stats['sets']++;
                                continue 2;
                            }
                        }

                        // Try matching Theme (ID) – only when not scanning parts_images
                        if (!$isPartsImagesScan) {
                            foreach ($candidateIds as $cid) {
                                if (is_numeric($cid)) {
                                    $stmtTheme->execute([$relPath, $cid]);
                                    if ($stmtTheme->rowCount() > 0) {
                                        $stats['themes']++;
                                        continue 2;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $log[] = "Scan complete.";
            $log[] = "Parts updated: {$stats['parts']}";
            $log[] = "Sets updated: {$stats['sets']}";
            $log[] = "Themes updated: {$stats['themes']}";

        } else {
            $log[] = "Error: Could not find a valid 'images' directory. Checked standard paths.";
        }

        // Return view with log
        $this->view('admin/update', [
            'scan_log' => implode("\n", $log),
            'csrf' => Security::csrfToken(),
            'local' => '', 'remote' => '', 'local_short' => '', 'remote_short' => '', 'status' => '', 'last_backup' => '' 
        ]);
    }
    
    public function imageStats(): void {
        $this->requirePost();
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $type = $_POST['type'] ?? '';
        $segment = $_POST['segment'] ?? '';
        $pdo = Config::db();
        $statsSets = [];
        $statsParts = [];
        $statsThemes = [];
        $detailItems = [];
        
        $compute = function(string $table) use ($pdo): array {
            $total = (int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $local = (int)$pdo->query("SELECT COUNT(*) FROM $table WHERE img_url LIKE '/images%' OR img_url LIKE '/parts_images%'")->fetchColumn();
            $noimg = (int)$pdo->query("SELECT COUNT(*) FROM $table WHERE img_url IS NULL OR img_url = '' OR img_url = '/images/no-image.png'")->fetchColumn();
            $cdn = (int)$pdo->query("SELECT COUNT(*) FROM $table WHERE img_url LIKE 'http%' AND img_url LIKE '%rebrickable%'")->fetchColumn();
            return ['total' => $total, 'local' => $local, 'no_image' => $noimg, 'cdn' => $cdn];
        };
        
        if ($type === 'sets') {
            $statsSets = $compute('sets');
            if ($segment !== '') {
                if ($segment === 'local') {
                    $sql = "SELECT set_num, name, img_url FROM sets WHERE img_url LIKE '/images%' OR img_url LIKE '/parts_images%' ORDER BY set_num LIMIT 500";
                } elseif ($segment === 'no_image') {
                    $sql = "SELECT set_num, name, img_url FROM sets WHERE img_url IS NULL OR img_url = '' OR img_url = '/images/no-image.png' ORDER BY set_num LIMIT 500";
                } else {
                    $sql = null;
                }
                if (!empty($sql)) {
                    $detailItems = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } elseif ($type === 'parts') {
            $statsParts = $compute('parts');
            if ($segment !== '') {
                if ($segment === 'local') {
                    $sql = "SELECT part_num, name, img_url FROM parts WHERE img_url LIKE '/images%' OR img_url LIKE '/parts_images%' ORDER BY part_num LIMIT 500";
                } elseif ($segment === 'no_image') {
                    $sql = "SELECT part_num, name, img_url FROM parts WHERE img_url IS NULL OR img_url = '' OR img_url = '/images/no-image.png' ORDER BY part_num LIMIT 500";
                } else {
                    $sql = null;
                }
                if (!empty($sql)) {
                    $detailItems = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } elseif ($type === 'themes') {
            $statsThemes = $compute('themes');
            if ($segment !== '') {
                if ($segment === 'local') {
                    $sql = "SELECT id, name, img_url FROM themes WHERE img_url LIKE '/images%' OR img_url LIKE '/parts_images%' ORDER BY id LIMIT 500";
                } elseif ($segment === 'no_image') {
                    $sql = "SELECT id, name, img_url FROM themes WHERE img_url IS NULL OR img_url = '' OR img_url = '/images/no-image.png' ORDER BY id LIMIT 500";
                } else {
                    $sql = null;
                }
                if (!empty($sql)) {
                    $detailItems = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }
        
        $this->view('admin/update', [
            'csrf' => Security::csrfToken(),
            'local' => '', 'remote' => '', 'local_short' => '', 'remote_short' => '', 'status' => '', 'last_backup' => '',
            'stats_sets' => $statsSets,
            'stats_parts' => $statsParts,
            'stats_themes' => $statsThemes,
            'active_tab' => $type ?: 'sets',
            'detail_type' => $type,
            'detail_segment' => $segment,
            'detail_items' => $detailItems,
            'latest_debug_sets' => $this->latestDebugUrl('sets'),
            'latest_debug_parts' => $this->latestDebugUrl('parts'),
            'latest_debug_themes' => $this->latestDebugUrl('themes'),
        ]);
    }

    public function exportDebug(): void {
        $this->requirePost();
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        $type = $_POST['type'] ?? 'sets';
        $pdo = Config::db();
        
        $sql = "";
        $headers = [];
        
        if ($type === 'sets') {
            $sql = "SELECT set_num, name, img_url FROM sets WHERE img_url IS NULL OR img_url = '' OR (img_url NOT LIKE '/images%' AND img_url NOT LIKE '/parts_images%')";
            $headers = ['set_num', 'name', 'img_url'];
        } elseif ($type === 'parts') {
            $sql = "SELECT part_num, name, img_url FROM parts WHERE img_url IS NULL OR img_url = '' OR (img_url NOT LIKE '/images%' AND img_url NOT LIKE '/parts_images%')";
            $headers = ['part_num', 'name', 'img_url'];
        } elseif ($type === 'themes') {
            $sql = "SELECT id, name, img_url FROM themes WHERE img_url IS NULL OR img_url = '' OR (img_url NOT LIKE '/images%' AND img_url NOT LIKE '/parts_images%')";
            $headers = ['id', 'name', 'img_url'];
        } else {
            header('Location: /admin/update');
            return;
        }

        $stmt = $pdo->query($sql);
        
        $debugDir = __DIR__ . '/../../public/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0777, true);
        }

        $filename = 'debug_' . $type . '_' . date('Ymd_His') . '.csv';
        $filepath = $debugDir . '/' . $filename;

        $fp = fopen($filepath, 'w');
        fputs($fp, "\xEF\xBB\xBF"); // BOM
        fputcsv($fp, $headers);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        $downloadUrl = '/debug/' . $filename;
        
        $local = trim($this->cmd('git rev-parse HEAD'));
        $remote = trim($this->cmd('git ls-remote origin HEAD'));
        $remoteHash = trim(explode("\t", $remote)[0] ?? '');
        $localShort = $local ? substr($local, -7) : '';
        $remoteShort = ($remoteHash && $remoteHash !== $local) ? substr($remoteHash, -7) : '';

        $this->view('admin/update', [
            'local' => $local,
            'remote' => $remoteHash,
            'local_short' => $localShort,
            'remote_short' => $remoteShort,
            'status' => $this->cmd('git status -sb'),
            'last_backup' => $this->lastBackupPath(),
            'csrf' => Security::csrfToken(),
            'debug_file' => $downloadUrl,
            'debug_type' => $type,
            'active_tab' => $type,
            'latest_debug_sets' => $this->latestDebugUrl('sets'),
            'latest_debug_parts' => $this->latestDebugUrl('parts'),
            'latest_debug_themes' => $this->latestDebugUrl('themes'),
        ]);
    }

    public function redirectBack(): void {
        header('Location: /admin/update');
        exit;
    }

    public function populateThemeUrls(): void {
        $this->requirePost();
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        $pdo = Config::db();
        
        // Populate themes using representative set images
        // We select the set with the most parts for each theme as the representative
        $sql = "
            UPDATE themes t
            JOIN (
                SELECT s1.theme_id, s1.img_url
                FROM sets s1
                LEFT JOIN sets s2 ON s1.theme_id = s2.theme_id AND s1.num_parts < s2.num_parts
                WHERE s2.set_num IS NULL AND s1.img_url LIKE 'http%'
            ) s ON t.id = s.theme_id
            SET t.img_url = s.img_url
            WHERE t.img_url IS NULL OR t.img_url = ''
        ";
        
        // Since the above query is complex and might be slow or not supported by all MySQL versions/modes
        // Let's use a safer approach with a subquery update or PHP loop if needed.
        // But for simplicity and performance on typical MySQL, let's try a simpler JOIN update first.
        // A more robust query for 'max parts set per theme':
        
        $sql = "
            UPDATE themes t
            INNER JOIN (
               SELECT theme_id, img_url 
               FROM sets 
               WHERE (theme_id, num_parts) IN (
                   SELECT theme_id, MAX(num_parts)
                   FROM sets
                   WHERE img_url LIKE 'http%'
                   GROUP BY theme_id
               )
               GROUP BY theme_id
            ) s ON t.id = s.theme_id
            SET t.img_url = s.img_url
            WHERE (t.img_url IS NULL OR t.img_url = '')
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $count = $stmt->rowCount();
            
            // Redirect back with success message (or just refresh)
            // We can't easily pass a message without session flash, so we just redirect
            header('Location: /admin/update');
        } catch (\PDOException $e) {
            // Log error
            error_log("Populate Theme URLs Error: " . $e->getMessage());
            header('Location: /admin/update');
        }
    }

    public function downloadMissingImages(): void {
        $this->requirePost();
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        set_time_limit(0); // Unlimited time
        
        $type = $_POST['type'] ?? 'sets';
        
        // Detect if we should stream (check for fetch/ajax header or param)
        // For now, we assume the new JS implementation will handle the stream
        $isStream = true; 

        if ($isStream) {
            // Disable buffering
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }
            @ini_set('output_buffering', '0');
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
            while (ob_get_level()) ob_end_clean();
            
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Accel-Buffering: no'); // Nginx
            
            echo "STARTED: Download process for {$type}\n";
            flush();
        }

        $pdo = Config::db();
        
        // Target directory: public/images/{type}
        $targetDir = __DIR__ . '/../../public/images/' . $type;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Select items with external URL
        $sql = "";
        if ($type === 'sets') {
            $sql = "SELECT set_num as id, img_url FROM sets WHERE img_url LIKE 'http%'";
        } elseif ($type === 'parts') {
            $sql = "SELECT part_num as id, img_url FROM parts WHERE img_url LIKE 'http%'";
        } elseif ($type === 'themes') {
            $sql = "SELECT id, img_url FROM themes WHERE img_url LIKE 'http%'";
        } else {
            if ($isStream) {
                echo "ERROR: Invalid type selected.\n";
                return;
            }
            header('Location: /admin/update');
            return;
        }

        $stmt = $pdo->query($sql);
        $total = $stmt->rowCount();
        
        if ($isStream) {
            echo "INFO: Found {$total} items to process in database.\n";
            echo "INFO: Target directory: " . realpath($targetDir) . "\n";
            flush();
        }

        $downloaded = 0;
        $failed = 0;
        $skipped = 0;

        $updateStmt = null;
        if ($type === 'sets') {
            $updateStmt = $pdo->prepare("UPDATE sets SET img_url = ? WHERE set_num = ?");
        } elseif ($type === 'parts') {
            $updateStmt = $pdo->prepare("UPDATE parts SET img_url = ? WHERE part_num = ?");
        } elseif ($type === 'themes') {
            $updateStmt = $pdo->prepare("UPDATE themes SET img_url = ? WHERE id = ?");
        }

        $counter = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $counter++;
            $id = $row['id'];
            $url = $row['img_url'];
            
            // Determine extension
            $ext = 'jpg';
            if (preg_match('/\.(\w{3,4})$/', $url, $m)) {
                $ext = $m[1];
            }
            
            // Sanitize filename (replace invalid chars)
            $safeId = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $id);
            $filename = $safeId . '.' . $ext;
            
            $localPath = $targetDir . '/' . $filename;
            $webPath = '/images/' . $type . '/' . $filename;

            if (file_exists($localPath) && filesize($localPath) > 0) {
                // File exists, just update DB
                $updateStmt->execute([$webPath, $id]);
                $skipped++;
                if ($isStream && $skipped % 50 === 0) {
                     echo "SKIPPED: {$counter}/{$total} - $id (already local)\n";
                     flush();
                }
                continue;
            }

            if ($isStream) {
                echo "DOWNLOADING: {$counter}/{$total} - ID: $id | URL: $url ... ";
                flush();
            }

            // Download using CURL for better reliability and debug info
            $content = false;
            $errorMsg = '';
            
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Try to be permissive with SSL
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $content = curl_exec($ch);
                if ($content === false) {
                    $errorMsg = curl_error($ch);
                } else {
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($httpCode !== 200) {
                        $content = false;
                        $errorMsg = "HTTP $httpCode";
                    }
                }
                curl_close($ch);
            } else {
                // Fallback to file_get_contents
                $context = stream_context_create([
                    'http' => [
                        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ]);
                $content = @file_get_contents($url, false, $context);
                if ($content === false) {
                    $error = error_get_last();
                    $errorMsg = $error['message'] ?? 'Unknown error';
                }
            }
            
            if ($content !== false && strlen($content) > 0) {
                file_put_contents($localPath, $content);
                $updateStmt->execute([$webPath, $id]);
                $downloaded++;
                if ($isStream) {
                    echo "OK -> Saved to $webPath\n";
                    flush();
                }
            } else {
                $failed++;
                if ($isStream) {
                    echo "FAILED ($errorMsg)\n";
                    flush();
                }
            }
        }

        $log = "Download complete for $type.\nDownloaded: $downloaded\nSkipped (already local): $skipped\nFailed: $failed";
        
        if ($isStream) {
            echo "\n----------------------------------------\n";
            echo $log . "\n";
            echo "FINISHED";
            exit;
        }
        
        // Reuse scanImages view logic or redirect
        $this->view('admin/update', [
            'scan_log' => $log,
            'csrf' => Security::csrfToken(),
            'local' => '', 'remote' => '', 'local_short' => '', 'remote_short' => '', 'status' => '', 'last_backup' => '',
            'active_tab' => $type
        ]);
    }

    private function findBestImagesDir(array &$log): ?string {
        $candidates = [
            __DIR__ . '/../../parts and sets/png',
            __DIR__ . '/../../parts and sets',
            __DIR__ . '/../../public/images',
            $_SERVER['DOCUMENT_ROOT'] . '/public/images',
            $_SERVER['DOCUMENT_ROOT'] . '/images',
            __DIR__ . '/../../images',
        ];

        foreach ($candidates as $path) {
            if (is_dir($path)) {
                $real = realpath($path);
                $log[] = "Found directory: $real";
                return $real;
            }
        }
        return null;
    }

    private function hasSubDirs(string $path): bool {
        return (
            is_dir($path . '/parts') || is_dir($path . '/Parts') ||
            is_dir($path . '/themes') || is_dir($path . '/Themes') ||
            is_dir($path . '/sets') || is_dir($path . '/Sets')
        );
    }

    private function findImagesDir(): ?string {
        // Deprecated, replaced by findBestImagesDir
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

        // Normalize baseDir for string operations
        $normBase = str_replace('\\', '/', realpath($baseDir));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $filename = $file->getBasename('.' . $file->getExtension());
                    
                    $fullPath = $file->getPathname();
                    $normFull = str_replace('\\', '/', $fullPath);
                    
                    // Calculate relative path from baseDir
                    // Check if normFull starts with normBase to be safe
                    if (strpos($normFull, $normBase) === 0) {
                        $relFromBase = substr($normFull, strlen($normBase));
                        // Ensure it starts with /
                        if (substr($relFromBase, 0, 1) !== '/') {
                            $relFromBase = '/' . $relFromBase;
                        }
                        
                        // Assume the base dir maps to /images
                        $relPath = '/images' . $relFromBase;
                        
                        // Update DB
                        $stmt->execute([$relPath, $filename]);
                        if ($stmt->rowCount() > 0) {
                            $count++;
                        }
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
    private function latestDebugUrl(string $type): ?string {
        $dir = __DIR__ . '/../../public/debug';
        if (!is_dir($dir)) return null;
        $files = array_values(array_filter(scandir($dir) ?: [], function($f) use ($type){
            return strpos($f, 'debug_' . $type . '_') === 0 && preg_match('/\\.csv$/', $f);
        }));
        if (!$files) return null;
        usort($files, function($a, $b) use ($dir){
            $pa = $dir . '/' . $a;
            $pb = $dir . '/' . $b;
            return filemtime($pb) <=> filemtime($pa);
        });
        return '/debug/' . $files[0];
    }
}
