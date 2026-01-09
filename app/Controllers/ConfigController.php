<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Config\Config;

class ConfigController extends Controller {

    public function page(): void {
        Security::requireRole('admin');
        $this->render('admin/config');
    }

    public function scrapePartsOne(): void {
        // Forward to SyncController or implement single scrape
        // For now, redirect to parts
        header('Location: /parts');
    }

    public function scrapeSetsOne(): void {
        header('Location: /sets');
    }

    /**
     * Populates the colors table with official LEGO color definitions.
     * Instead of scraping (which yields numbers/errors), we use a standard list.
     */
    public function seedColors(): void {
        $this->requirePost();
        Security::requireRole('admin');
        if (!\App\Core\Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        // Standard LEGO Color Palette (Partial list of most common colors)
        // Format: [LegoID, Name]
        $colors = [
            [1, 'White'],
            [5, 'Brick Yellow'], // Tan
            [18, 'Nougat'],
            [21, 'Bright Red'], // Red
            [23, 'Bright Blue'], // Blue
            [24, 'Bright Yellow'], // Yellow
            [26, 'Black'],
            [28, 'Dark Green'], // Green
            [37, 'Bright Green'],
            [38, 'Dark Orange'],
            [102, 'Medium Blue'],
            [106, 'Bright Orange'], // Orange
            [119, 'Bright Yellowish Green'], // Lime
            [124, 'Reddish Brown'],
            [131, 'Silver Flip Flop'],
            [135, 'Sand Blue'],
            [138, 'Sand Yellow'], // Dark Tan
            [140, 'Earth Blue'], // Dark Blue
            [141, 'Earth Green'], // Dark Green
            [148, 'Metallic Dark Grey'],
            [151, 'Sand Green'],
            [154, 'Dark Red'], // New Dark Red
            [191, 'Flame Yellowish Orange'], // Bright Light Orange
            [192, 'Reddish Brown'],
            [194, 'Medium Stone Grey'], // Light Bluish Gray
            [199, 'Dark Stone Grey'], // Dark Bluish Gray
            [208, 'Light Stone Grey'], // Very Light Gray
            [212, 'Light Royal Blue'], // Bright Light Blue
            [221, 'Bright Purple'], // Dark Pink
            [222, 'Light Purple'], // Bright Pink
            [226, 'Cool Yellow'], // Bright Light Yellow
            [268, 'Medium Lilac'], // Dark Purple
            [283, 'Light Nougat'], // Light Flesh
            [297, 'Warm Gold'], // Pearl Gold
            [308, 'Dark Brown'],
            [311, 'Transparent'], // Trans-Clear
            [312, 'Transparent Red'],
            [321, 'Transparent Dark Pink'],
            [322, 'Transparent Bright Orange'],
            [323, 'Transparent Light Blue'], // Trans-Aqua
            [324, 'Transparent Bright Violet'],
            [325, 'Transparent Bright Green'],
            [326, 'Transparent Fluorescent Green'],
            [330, 'Olive Green'],
            // Add more as needed or fetch from a reliable API if possible
        ];

        $pdo = Config::db();
        $count = 0;

        foreach ($colors as $row) {
            $code = (string)$row[0];
            $name = $row[1];
            
            // Upsert based on color_code (LEGO ID) or Name
            // We'll trust the code as the unique identifier for LEGO
            $st = $pdo->prepare('INSERT INTO colors (color_name, color_code) VALUES (?,?) ON DUPLICATE KEY UPDATE color_name=VALUES(color_name)');
            $st->execute([$name, $code]);
            $count++;
        }

        // Log action
        try {
            $uid = $_SESSION['user']['id'] ?? null;
            $stLog = $pdo->prepare("INSERT INTO entity_history (entity_type, entity_id, user_id, changes) VALUES ('system', 0, ?, ?)");
            $stLog->execute([$uid, 'Seeded Colors: Inserted/Updated ' . $count . ' standard LEGO colors.']);
        } catch (\Throwable $e) {}

        header('Location: /admin/config');
    }

    // Placeholder for Parts/Sets - handled by SyncController mainly, 
    // but if we need a bulk seed, we can add it here.
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
