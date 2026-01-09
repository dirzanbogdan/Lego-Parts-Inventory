<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Config\Config;
use App\Services\RebrickableService;

class ConfigController extends Controller {
    private RebrickableService $rebrickable;

    public function __construct() {
        parent::__construct();
        $this->rebrickable = new RebrickableService();
    }

    public function page(): void {
        Security::requireRole('admin');
        $this->render('admin/config');
    }

    public function scrapePartsOne(): void {
        header('Location: /parts');
    }

    public function scrapeSetsOne(): void {
        header('Location: /sets');
    }

    /**
     * Seeds colors from Rebrickable's colors.csv.gz
     */
    public function seedColors(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        if (!$this->rebrickable->ensureFile('colors.csv')) {
             // Flash error?
             header('Location: /admin/config?error=download_failed');
             return;
        }

        $pdo = Config::db();
        $count = 0;
        
        foreach ($this->rebrickable->readCsv('colors.csv') as $row) {
            // id, name, rgb, is_trans
            $code = $row['id'];
            $name = $row['name'];
            
            // Upsert
            $st = $pdo->prepare('INSERT INTO colors (color_name, color_code) VALUES (?,?) ON DUPLICATE KEY UPDATE color_name=VALUES(color_name)');
            $st->execute([$name, $code]);
            $count++;
        }

        // Log action
        try {
            $uid = $_SESSION['user']['id'] ?? null;
            $stLog = $pdo->prepare("INSERT INTO entity_history (entity_type, entity_id, user_id, changes) VALUES ('system', 0, ?, ?)");
            $stLog->execute([$uid, 'Seeded Colors from Rebrickable: ' . $count . ' colors.']);
        } catch (\Throwable $e) {}

        header('Location: /admin/config?success=colors_seeded');
    }

    public function seedParts(): void {
        $this->requirePost();
        Security::requireRole('admin');
        header('Location: /admin/config');
    }

    public function seedSets(): void {
        $this->requirePost();
        Security::requireRole('admin');
        header('Location: /admin/config');
    }
}
