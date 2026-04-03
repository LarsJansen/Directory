<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Very small route matcher for the directory's friendly URLs.
 *
 * It supports static paths plus named placeholders such as:
 *   /category/{path:.+}
 */
class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    private function addRoute(string $method, string $pattern, array $handler): void
    {
        $this->routes[] = compact('method', 'pattern', 'handler');
    }

    public function dispatch(string $method, string $uri, Database $db, array $appConfig): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            [$controllerClass, $action] = $route['handler'];
            $controller = new $controllerClass($db, $appConfig);
            $controller->$action(...array_values($params));
            return;
        }

        http_response_code(404);
        $controller = new \App\Controllers\HomeController($db, $appConfig);
        $controller->notFound('The requested page could not be found.');
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(:([^}]+))?\}/', function ($matches) {
            $name = $matches[1];
            $subPattern = $matches[3] ?? '[^/]+';

            return '(?P<' . $name . '>' . $subPattern . ')';
        }, $pattern);

        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_int($key)) {
                continue;
            }

            // Route placeholders arrive from preg_match() as strings. Cast simple
            // integer-looking values so controller actions with int type hints work
            // correctly for routes like /editor/sites/{id}/edit.
            if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
                $params[$key] = (int) $value;
                continue;
            }

            $params[$key] = $value;
        }

        return $params;
    }
}
