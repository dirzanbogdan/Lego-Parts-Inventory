<?php
declare(strict_types=1);
namespace App\Core;
class Security {
    public static function initSession(): void {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    public static function csrfToken(): string {
        return $_SESSION['csrf_token'] ?? '';
    }
    public static function verifyCsrf(?string $token): bool {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
    }
    public static function requireRole(string $role): void {
        $user = $_SESSION['user'] ?? null;
        if (!$user || ($user['role'] ?? 'user') !== $role) {
            http_response_code(403);
            echo 'forbidden';
            exit;
        }
    }
}
