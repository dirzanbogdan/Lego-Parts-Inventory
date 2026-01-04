<?php
declare(strict_types=1);
namespace App\Core;
class Router {
    private array $routes = [];
    public function add(string $method, string $pattern, callable|array $handler): void {
        $this->routes[] = [$method, $pattern, $handler];
    }
    public function dispatch(): void {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        foreach ($this->routes as [$m, $p, $h]) {
            if ($m !== $method) continue;
            if ($p === $uri) {
                $this->invoke($h);
                return;
            }
        }
        http_response_code(404);
        echo 'not found';
    }
    private function invoke(callable|array $handler): void {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $obj = new $class();
            $obj->$method();
        } else {
            $handler();
        }
    }
}
