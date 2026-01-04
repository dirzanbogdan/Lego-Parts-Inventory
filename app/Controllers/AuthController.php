<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Security;
use App\Models\User;
class AuthController extends Controller {
    public function loginForm(): void {
        $this->render('auth/login', []);
    }
    public function registerForm(): void {
        $this->render('auth/register', []);
    }
    public function login(): void {
        $this->requirePost();
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
        $this->render('auth/login', ['error' => 'Login esuat']);
    }
    public function register(): void {
        $this->requirePost();
        if (!Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'bad request';
            return;
        }
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$username || !$password) {
            $this->render('auth/register', ['error' => 'Date invalide']);
            return;
        }
        $ok = User::create($username, $password);
        if ($ok) {
            header('Location: /login');
            return;
        }
        $this->render('auth/register', ['error' => 'Inregistrare esuata']);
    }
    public function logout(): void {
        unset($_SESSION['user']);
        header('Location: /');
    }
}
