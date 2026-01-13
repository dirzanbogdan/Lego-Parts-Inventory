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
    // Common Lego Colors (Rebrickable IDs and RGBs)
    private $legoColors = [
        0 => ['name' => 'Black', 'rgb' => '05131D'],
        1 => ['name' => 'Blue', 'rgb' => '0055BF'],
        2 => ['name' => 'Green', 'rgb' => '237841'],
        4 => ['name' => 'Red', 'rgb' => 'C91A09'],
        5 => ['name' => 'Dark Pink', 'rgb' => 'C870A0'],
        14 => ['name' => 'Yellow', 'rgb' => 'F2CD37'],
        15 => ['name' => 'White', 'rgb' => 'FFFFFF'],
        19 => ['name' => 'Tan', 'rgb' => 'E4CD9E'],
        27 => ['name' => 'Lime', 'rgb' => 'BBE90B'],
        28 => ['name' => 'Dark Tan', 'rgb' => '958A73'],
        71 => ['name' => 'Light Bluish Gray', 'rgb' => 'A0A5A9'],
        72 => ['name' => 'Dark Bluish Gray', 'rgb' => '6C6E68'],
        320 => ['name' => 'Dark Red', 'rgb' => '720E0F'],
        321 => ['name' => 'Dark Azure', 'rgb' => '078BC9'],
        322 => ['name' => 'Medium Azure', 'rgb' => '36AEBF'],
        323 => ['name' => 'Light Aqua', 'rgb' => 'ADC3C0'],
        326 => ['name' => 'Yellowish Green', 'rgb' => 'DFEEA5'],
        378 => ['name' => 'Sand Purple', 'rgb' => '845E84'],
    ];

    public function analyze($imagePath, $mimeType = null) {
        // 1. Run Python segmentation script
        $scriptPath = __DIR__ . '/segment_parts.py';
        $outputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lego_crops_' . uniqid();
        
        $cmd = 'python "' . $scriptPath . '" "' . $imagePath . '" "' . $outputDir . '"';
        
        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);
        
        $crops = [];
        $jsonStr = implode("\n", $output);
        $crops = json_decode($jsonStr, true);
        
        $aggregatedResults = [];

        // If segmentation failed or returned no crops, fallback to full image
        if (!is_array($crops) || count($crops) === 0 || isset($crops['error'])) {
             // Fallback
             return $this->analyzeFullImage($imagePath, $mimeType);
        }

        // Process each crop
        foreach ($crops as $crop) {
            $cropPath = $crop['path'];
            $candidates = $this->analyzeFullImage($cropPath, 'image/jpeg');
            
            if (!empty($candidates)) {
                $bestMatch = $candidates[0]; // Take top 1
                if ($bestMatch['confidence'] < 20) continue;

                // Determine Color
                $detectedColorHex = $crop['color_hex'] ?? 'FFFFFF';
                $matchedColor = $this->findClosestColor($detectedColorHex);
                
                $bestMatch['color_id'] = $matchedColor['id'];
                $bestMatch['color_name'] = $matchedColor['name'];
                $bestMatch['color_rgb'] = $matchedColor['rgb'];

                // Aggregate by Part Num AND Color
                $key = $bestMatch['part_num'] . '_' . $bestMatch['color_id'];
                
                if (isset($aggregatedResults[$key])) {
                    $aggregatedResults[$key]['quantity']++;
                } else {
                    $bestMatch['quantity'] = 1;
                    $bestMatch['crop_path'] = $cropPath;
                    $aggregatedResults[$key] = $bestMatch;
                }
            }
        }
        
        // Clean up temp files
        foreach ($crops as $crop) {
            if (file_exists($crop['path'])) unlink($crop['path']);
        }
        if (is_dir($outputDir)) rmdir($outputDir);

        return array_values($aggregatedResults);
    }

    private function findClosestColor($hex) {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $minDist = PHP_INT_MAX;
        $bestColor = ['id' => 15, 'name' => 'White', 'rgb' => 'FFFFFF'];

        foreach ($this->legoColors as $id => $color) {
            $cr = hexdec(substr($color['rgb'], 0, 2));
            $cg = hexdec(substr($color['rgb'], 2, 2));
            $cb = hexdec(substr($color['rgb'], 4, 2));

            // Euclidean distance
            $dist = sqrt(pow($r - $cr, 2) + pow($g - $cg, 2) + pow($b - $cb, 2));

            if ($dist < $minDist) {
                $minDist = $dist;
                $bestColor = array_merge(['id' => $id], $color);
            }
        }
        return $bestColor;
    }


    /**
     * Internal method to call API for a single image (full or crop)
     * Returns list of candidates
     */
    private function analyzeFullImage($imagePath, $mimeType) {
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            error_log("Brickognize API Error: $error (HTTP $httpCode)");
            return [];
        }

        $predictions = json_decode($response, true);
        if (!is_array($predictions)) return [];

        $items = isset($predictions['items']) ? $predictions['items'] : $predictions;
        if (!is_array($items)) return [];

        $results = [];
        $pdo = Config::db();

        foreach ($items as $pred) {
            if (!isset($pred['score']) || !isset($pred['id'])) continue;

            $confidence = $pred['score'] * 100;
            // Lower threshold for raw API results, filtering happens in caller
            if ($confidence < 5) continue; 
            if (count($results) >= 5) break;

            $partNum = $pred['id'];
            
            $stmt = $pdo->prepare("SELECT p.part_num, p.name AS part_name, p.img_url FROM parts p WHERE p.part_num = ? LIMIT 1");
            $stmt->execute([$partNum]);
            $localPart = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($localPart) {
                $results[] = [
                    'part_num' => $localPart['part_num'],
                    'part_name' => $localPart['part_name'],
                    'img_url' => $localPart['img_url'] ?: '/images/no-image.png',
                    'color_id' => 15,
                    'color_name' => 'Unknown (Default: White)', 
                    'color_rgb' => 'FFFFFF',
                    'quantity' => 1,
                    'confidence' => round($confidence, 1),
                    'in_db' => true
                ];
            } else {
                $name = $pred['name'] ?? 'Unknown Part';
                $imgUrl = "https://cdn.rebrickable.com/media/parts/ldraw/15/{$partNum}.png";
                $results[] = [
                    'part_num' => $partNum,
                    'part_name' => $name . ' (Not in DB)',
                    'img_url' => $imgUrl,
                    'color_id' => 15,
                    'color_name' => 'Unknown (Default: White)', 
                    'color_rgb' => 'CCCCCC',
                    'quantity' => 1,
                    'confidence' => round($confidence, 1),
                    'in_db' => false
                ];
            }
        }
        return $results;
    }

}
