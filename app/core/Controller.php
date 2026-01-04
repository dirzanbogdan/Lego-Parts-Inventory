<?php
declare(strict_types=1);
namespace App\Core;
class Controller {
    protected function render(string $view, array $params = []): void {
        extract($params);
        $csrf = Security::csrfToken();
        $base = \App\Config\Config::app()['base_url'] ?? '';
        include __DIR__ . '/../views/layout.php';
    }
    protected function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    protected function requirePost(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo 'method not allowed';
            exit;
        }
    }
}
