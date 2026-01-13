<?php

namespace App\Services;

use App\Core\Config;
use PDO;

class IdentifyService {
    
    /**
     * Simulates AI recognition of Lego parts from an image.
     * In a real implementation, this would call an external API or load a local ML model.
     *
     * @param string $imagePath
     * @return array
     */
    public function analyze($imagePath) {
        $pdo = Config::db();
        
        // Mock: Select random parts to simulate recognition
        // We join with colors to get valid combinations
        $sql = "
            SELECT 
                p.part_num, 
                p.name AS part_name, 
                p.img_url, 
                c.id AS color_id, 
                c.name AS color_name, 
                c.rgb
            FROM parts p
            CROSS JOIN colors c
            WHERE p.img_url IS NOT NULL 
            ORDER BY RAND() 
            LIMIT 4
        ";
        
        $stmt = $pdo->query($sql);
        $results = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Simulate random quantity and confidence
            $results[] = [
                'part_num' => $row['part_num'],
                'part_name' => $row['part_name'],
                'img_url' => $row['img_url'],
                'color_id' => $row['color_id'],
                'color_name' => $row['color_name'],
                'color_rgb' => $row['rgb'],
                'quantity' => rand(1, 5),
                'confidence' => rand(85, 99)
            ];
        }
        
        return $results;
    }
}
