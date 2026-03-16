<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

$appConfig = require dirname(__DIR__) . '/config/app.php';

session_name($appConfig['session_name']);
session_start();

require dirname(__DIR__) . '/app/helpers.php';

use App\Core\Database;
use App\Core\Router;

$databaseConfig = require dirname(__DIR__) . '/config/database.php';
$db = new Database($databaseConfig);

$router = new Router($db, $appConfig);
$routes = require dirname(__DIR__) . '/config/routes.php';
$routes($router);
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
