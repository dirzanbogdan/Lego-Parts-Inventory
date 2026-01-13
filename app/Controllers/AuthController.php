<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Security;
use App\Core\Config;
use App\Models\User;
class AuthController extends Controller {
    public function loginForm(): void {
        $this->view('auth/login', ['csrf' => Security::csrfToken()]);
    }
    public function registerForm(): void {
        $this->view('auth/register', ['csrf' => Security::csrfToken()]);
    }
    public function login(): void {
        $this->requirePost();
        $this->ensureUsersTable();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = User::findByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']];
            header('Location: /');
            return;
        }
        $this->view('auth/login', ['error' => 'Login esuat', 'csrf' => Security::csrfToken()]);
    }
    public function register(): void {
        $this->requirePost();
        $this->ensureUsersTable();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$username || !$password) {
            $this->view('auth/register', ['error' => 'Date invalide', 'csrf' => Security::csrfToken()]);
            return;
        }
        $ok = User::create($username, $password);
        if ($ok) {
            header('Location: /login');
            return;
        }
        $this->view('auth/register', ['error' => 'Inregistrare esuata', 'csrf' => Security::csrfToken()]);
    }
    public function logout(): void {
        unset($_SESSION['user']);
        header('Location: /');
    }
    
    private function ensureUsersTable(): void {
        try {
            $pdo = Config::db();
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(100) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    role ENUM('admin','user') NOT NULL DEFAULT 'user',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
