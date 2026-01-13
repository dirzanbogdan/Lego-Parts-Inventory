<?php

namespace App\Services;

use App\Core\Config;
use PDO;

class IdentifyService {
    
    /**
     * Identifies Lego parts using the Brickognize API.
     *
     * @param string $imagePath
     * @param string $mimeType Optional mime type override
     * @return array
     */
    public function analyze($imagePath, $mimeType = null) {
        // 1. Send image to Brickognize API
        $apiUrl = 'https://api.brickognize.com/predict/';
        
        $mime = $mimeType;
        if (!$mime) {
            $mime = 'application/octet-stream';
            if (function_exists('mime_content_type')) {
                $mime = mime_content_type($imagePath);
            } elseif (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $imagePath);
                finfo_close($finfo);
            }
        }

        $cfile = new \CURLFile($imagePath, $mime, 'query_image');
        $data = ['query_image' => $cfile];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout after 30s
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            // Fallback or error handling
            error_log("Brickognize API Error: $error (HTTP $httpCode)");
            return [];
        }

        $predictions = json_decode($response, true);
        
        // Debug API response structure
        if (!is_array($predictions)) {
            error_log("Brickognize API: Invalid JSON response");
            return [];
        }

        // Brickognize API sometimes returns wrapped structure: {"items": [...]} 
        // OR a flat array of items. Let's handle both.
        $items = isset($predictions['items']) ? $predictions['items'] : $predictions;
        
        if (!is_array($items)) {
            error_log("Brickognize API: Unexpected structure: " . print_r($predictions, true));
            return [];
        }

        // 2. Process results and match with local DB
        $results = [];
        $pdo = Config::db();

        // Brickognize returns top matches. We'll take the top 5 with reasonable confidence.
        // Structure of item: { "id": "3001", "name": "Brick 2x4", "score": 0.95, ... }
        // Note: Brickognize usually returns the Part Num (id). It does NOT predict color reliably yet, 
        // so we often have to assume a default color or let the user choose.
        // HOWEVER, sometimes the item has color info if it's a specific patterned part.
        
        foreach ($items as $pred) {
            if (!isset($pred['score']) || !isset($pred['id'])) {
                continue; // Skip malformed items
            }

            // Skip low confidence (lowered threshold to 10% to see more options)
            $confidence = $pred['score'] * 100;
            if ($confidence < 10) continue; 
            if (count($results) >= 10) break; // Increased limit to 10

            $partNum = $pred['id']; // This is the LDraw/Rebrickable ID usually
            
            // Check if we have this part in our DB
            $stmt = $pdo->prepare("
                SELECT 
                    p.part_num, 
                    p.name AS part_name, 
                    p.img_url
                FROM parts p
                WHERE p.part_num = ?
                LIMIT 1
            ");
            $stmt->execute([$partNum]);
            $localPart = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($localPart) {
                // If found locally, use local details
                $results[] = [
                    'part_num' => $localPart['part_num'],
                    'part_name' => $localPart['part_name'],
                    'img_url' => $localPart['img_url'] ?: '/images/no-image.png',
                    'color_id' => 15, // Default to White (15) or Black (0) as we don't know color yet
                    'color_name' => 'Unknown (Default: White)', 
                    'color_rgb' => 'FFFFFF',
                    'quantity' => 1,
                    'confidence' => round($confidence, 1),
                    'in_db' => true
                ];
            } else {
                // If not found locally, still show it but mark it
                // Brickognize provides name in 'name' field usually
                $name = $pred['name'] ?? 'Unknown Part';
                
                // Try to get image from Rebrickable CDN if not in local DB
                // Pattern: https://cdn.rebrickable.com/media/parts/elements/{id}.jpg or similar
                // But Brickognize doesn't give element ID, just part num.
                // Best guess image:
                $imgUrl = "https://cdn.rebrickable.com/media/parts/ldraw/15/{$partNum}.png";

                $results[] = [
                    'part_num' => $partNum,
                    'part_name' => $name . ' (Not in DB)',
                    'img_url' => $imgUrl, // Fallback external image
                    'color_id' => 15,
                    'color_name' => 'Unknown (Default: White)', 
                    'color_rgb' => 'CCCCCC', // Gray out
                    'quantity' => 1,
                    'confidence' => round($confidence, 1),
                    'in_db' => false
                ];
            }
        }
        
        return $results;
    }
}
