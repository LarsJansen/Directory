<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function __construct(
        private Database $db,
        private array $appConfig
    ) {
    }

    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        $paramNames = [];
        $pattern = preg_replace_callback('/\{([^}]+)\}/', function ($matches) use (&$paramNames) {
            $definition = $matches[1];
            $parts = explode(':', $definition, 2);
            $name = $parts[0];
            $regex = $parts[1] ?? '[^/]+';
            $paramNames[] = $name;
            return '(' . $regex . ')';
        }, $path);

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => '#^' . $pattern . '$#',
            'params' => $paramNames,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                $params = [];

                foreach ($route['params'] as $index => $name) {
                    $params[$name] = $matches[$index] ?? null;
                }

                [$controllerClass, $action] = $route['handler'];
                $controller = new $controllerClass($this->db, $this->appConfig);
                $controller->{$action}($params);
                $this->clearFormState();
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    private function clearFormState(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            unset($_SESSION['_old'], $_SESSION['_errors']);
        }
    }
}
