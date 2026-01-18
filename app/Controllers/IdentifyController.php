<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Config;
use App\Core\Security;
use App\Services\IdentifyService;
use PDO;

class IdentifyController extends Controller {
    
    private $userId = 1; // Hardcoded for now, similar to MyController

    public function index() {
        $this->view('identify/index');
    }

    public function analyze() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /identify');
            return;
        }

        $gallery = $_FILES['image_gallery'] ?? null;
        $camera = $_FILES['image_camera'] ?? null;

        $file = null;
        if (is_array($gallery) && ($gallery['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $file = $gallery;
        } elseif (is_array($camera) && ($camera['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $file = $camera;
        } else {
            $galleryError = is_array($gallery) ? ($gallery['error'] ?? null) : null;
            $cameraError = is_array($camera) ? ($camera['error'] ?? null) : null;
            $maxUpload = ini_get('upload_max_filesize');
            $maxPost = ini_get('post_max_size');
            $contentLength = $_SERVER['CONTENT_LENGTH'] ?? null;
            error_log(
                'Identify upload failed. gallery_error=' . var_export($galleryError, true) .
                ' camera_error=' . var_export($cameraError, true) .
                ' upload_max_filesize=' . var_export($maxUpload, true) .
                ' post_max_size=' . var_export($maxPost, true) .
                ' content_length=' . var_export($contentLength, true)
            );
            $message = 'We could not upload the image. Please try again with a smaller file.';
            if (
                $galleryError === UPLOAD_ERR_INI_SIZE ||
                $galleryError === UPLOAD_ERR_FORM_SIZE ||
                $cameraError === UPLOAD_ERR_INI_SIZE ||
                $cameraError === UPLOAD_ERR_FORM_SIZE
            ) {
                $message = 'We could not upload the image because the server reported it is too large.';
            }
            $this->view('identify/index', ['error' => $message]);
            return;
        }

        $tmpPath = $file['tmp_name'];
        $mimeType = $file['type'];
        
        $service = new IdentifyService();
        $results = $service->analyze($tmpPath, $mimeType);

        // Convert uploaded image to base64 to show it back to user
        $imageData = base64_encode(file_get_contents($tmpPath));
        $src = 'data: ' . $mimeType . ';base64,' . $imageData;

        $this->view('identify/results', [
            'results' => $results, 
            'uploadedImage' => $src
        ]);
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /identify');
            return;
        }
        
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        $part_num = $_POST['part_num'] ?? null;
        $color_id = $_POST['color_id'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 0);

        if (!$part_num || !$color_id || $quantity <= 0) {
            header('Location: /identify');
            return;
        }

        $this->addToMyParts($part_num, $color_id, $quantity);

        // Redirect back is tricky because results are transient (POST).
        // For better UX, we could use AJAX or store results in session.
        // For now, we'll redirect to My Parts to confirm success.
        header('Location: /my/parts?success=' . urlencode("Added part $part_num to inventory."));
    }

    public function addAll() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /identify');
            return;
        }
        
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }

        $items = json_decode($_POST['items'], true);
        if (!is_array($items)) {
            header('Location: /identify');
            return;
        }

        foreach ($items as $item) {
            if (isset($item['part_num'], $item['color_id'], $item['quantity'])) {
                $this->addToMyParts($item['part_num'], $item['color_id'], (int)$item['quantity']);
            }
        }

        header('Location: /my/parts?success=' . urlencode("Added all identified parts to inventory."));
    }

    private function addToMyParts($part_num, $color_id, $quantity) {
        $pdo = Config::db();
        $this->ensureUserPartsTable($pdo);
        
        $stmt = $pdo->prepare("
            INSERT INTO user_parts (user_id, part_num, color_id, quantity) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt->execute([$this->userId, $part_num, $color_id, $quantity]);
    }

    private function ensureUserPartsTable(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_parts (
                user_id INT NOT NULL,
                part_num VARCHAR(50) NOT NULL,
                color_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 0,
                PRIMARY KEY (user_id, part_num, color_id)
            )
        ");
    }
}
