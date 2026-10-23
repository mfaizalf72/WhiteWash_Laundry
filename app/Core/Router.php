<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Core;

class Router {
    private array $routes = [];

    public function get(string $path, string $handler, array $middlewares = []): void {
        $this->routes['GET'][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function post(string $path, string $handler, array $middlewares = []): void {
        $this->routes['POST'][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch(string $uri, string $method): void {
        $basePath = '/laundry-lms';
        $path = parse_url($uri, PHP_URL_PATH);
        
        if (str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        if ($path === '' || $path === false) {
            $path = '/';
        }

        if (isset($this->routes[$method][$path])) {
            $routeData = $this->routes[$method][$path];
            $handler = is_array($routeData) ? $routeData['handler'] : $routeData;
            $middlewares = is_array($routeData) ? ($routeData['middlewares'] ?? []) : [];

            foreach ($middlewares as $middleware) {
                $param = null;
                if (str_contains($middleware, ':')) {
                    [$mwClass, $param] = explode(':', $middleware, 2);
                } else {
                    $mwClass = $middleware;
                }

                if (class_exists($mwClass)) {
                    $mwInstance = new $mwClass();
                    $mwInstance->handle($param);
                }
            }

            [$class, $methodName] = explode('@', $handler);
            
            if (class_exists($class) && method_exists($class, $methodName)) {
                $controller = new $class();
                $controller->$methodName();
            } else {
                $this->abort(500, "Controller or Method not found: {$handler}");
            }
        } else {
            $this->abort(404, "Page not found at path: {$path}");
        }
    }

    public static function view(string $view, array $data = [], string $layout = 'app'): void {
        extract($data);
        $content = __DIR__ . "/../../views/pages/{$view}.php";
        if ($layout === '' || $layout === 'none') {
            if (file_exists($content)) {
                require_once $content;
                return;
            }
            die("View file not found.");
        }
        $layoutFile = __DIR__ . "/../../views/layouts/{$layout}.php";
        
        if (file_exists($content) && file_exists($layoutFile)) {
            require_once $layoutFile;
        } else {
            die("View or Layout file not found.");
        }
    }

    public static function redirect(string $path): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header("Location: " . (function_exists('url') ? url($path) : ('/laundry-lms' . $path)));
        exit;
    }

    private function abort(int $code, string $message): void {
        http_response_code($code);
        echo "<div style='padding: 3rem; text-align: center; font-family: sans-serif;'><h1>{$code}</h1><p>{$message}</p></div>";
        exit;
    }
}
