<?php
declare(strict_types=1);

namespace App\Services;

class RebrickableService {
    private const BASE_URL = 'https://cdn.rebrickable.com/media/downloads/';
    private $storagePath;

    public function __construct() {
        $this->storagePath = __DIR__ . '/../../storage/rebrickable/';
    }

    private function initStorage(): void {
        if (!file_exists($this->storagePath)) {
            if (!mkdir($this->storagePath, 0777, true) && !is_dir($this->storagePath)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->storagePath));
            }
        }
        if (!is_writable($this->storagePath)) {
            throw new \RuntimeException(sprintf('Directory "%s" is not writable', $this->storagePath));
        }
    }

    public function getFilePath(string $filename): string {
        $this->initStorage();
        return $this->storagePath . $filename;
    }

    public function downloadFile(string $filename): bool {
        $this->initStorage();
        if (!extension_loaded('zlib')) {
            throw new \RuntimeException('Zlib extension is required for reading gz files.');
        }
        $url = self::BASE_URL . $filename . '.gz';
        $dest = $this->storagePath . $filename . '.gz';
        
        $fp = fopen($dest, 'w+');
        if ($fp === false) return false;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 mins
        curl_setopt($ch, CURLOPT_USERAGENT, 'LegoInventoryApp/1.0');
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($code !== 200) {
            @unlink($dest);
            return false;
        }

        // Decompress
        $sfp = gzopen($dest, "rb");
        $out = fopen($this->storagePath . $filename, "w");
        while (!gzeof($sfp)) {
            fwrite($out, gzread($sfp, 4096));
        }
        gzclose($sfp);
        fclose($out);
        
        // Remove gz to save space? Or keep it? Let's remove gz
        @unlink($dest);

        return true;
    }

    public function ensureFile(string $filename, int $maxAgeSeconds = 86400): bool {
        $path = $this->storagePath . $filename;
        if (!file_exists($path) || (time() - filemtime($path) > $maxAgeSeconds)) {
            return $this->downloadFile($filename);
        }
        return true;
    }

    /**
     * Generator to read CSV file line by line
     */
    public function readCsv(string $filename) {
        $path = $this->storagePath . $filename;
        if (!file_exists($path)) return;

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($header)) {
                yield array_combine($header, $row);
            }
        }
        fclose($handle);
    }

    /**
     * Search a CSV for a specific value in a column and return first match
     */
    public function findInCsv(string $filename, string $col, string $val): ?array {
        foreach ($this->readCsv($filename) as $row) {
            if (isset($row[$col]) && $row[$col] == $val) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Search a CSV for all matches
     */
    public function findAllInCsv(string $filename, string $col, string $val): array {
        $results = [];
        foreach ($this->readCsv($filename) as $row) {
            if (isset($row[$col]) && $row[$col] == $val) {
                $results[] = $row;
            }
        }
        return $results;
    }
}
