<?php
declare(strict_types=1);
namespace App\Core;
class Router {
    private $routes = [];
    public function add(string $method, string $pattern, $handler): void {
        $this->routes[] = array($method, $pattern, $handler);
    }
    public function dispatch(): void {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (strpos($uri, '/public/') === 0) {
            $uri = substr($uri, 7);
            if ($uri === '' || $uri === false) $uri = '/';
        }
        if (preg_match('/index\.php$/', $uri)) {
            $uri = '/';
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $open = array(
            array('GET','/login'),
            array('POST','/login'),
            array('GET','/register'),
            array('POST','/register'),
        );
        $isOpen = false;
        foreach ($open as $o) {
            if ($o[0] === $method && $o[1] === $uri) {
                $isOpen = true;
                break;
            }
        }
        if (!$isOpen) \App\Core\Security::requireLogin();
        foreach ($this->routes as $route) {
            $m = $route[0];
            $p = $route[1];
            $h = $route[2];
            if ($m !== $method) continue;
            if ($p === $uri) {
                $this->invoke($h);
                return;
            }
        }
        http_response_code(404);
        echo 'not found';
    }
    private function invoke($handler): void {
        if (is_array($handler)) {
            $class = $handler[0];
            $method = $handler[1];
            $obj = new $class();
            $obj->$method();
        } else {
            $handler();
        }
    }
}
