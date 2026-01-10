<?php

namespace App\Core;

class Router {
    protected $routes = [];

    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }

    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }

    public function resolve() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // Simple regex matching for dynamic routes
        foreach ($this->routes[$method] as $route => $callback) {
            // Convert route like /sets/{id} to regex /sets/([^/]+)
            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $route);
            if (preg_match("#^$pattern$#", $path, $matches)) {
                array_shift($matches); // Remove full match
                
                // Callback is [ControllerClass, MethodName]
                $controller = new $callback[0]();
                $action = $callback[1];
                
                return call_user_func_array([$controller, $action], $matches);
            }
        }

        // Fallback for exact matches if regex didn't catch (or if I implemented it poorly)
        // Actually, the above loop handles exact matches too if no params.
        
        http_response_code(404);
        echo "404 Not Found";
    }
}
