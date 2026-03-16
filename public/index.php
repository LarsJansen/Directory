<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$basePath = dirname(__DIR__);

spl_autoload_register(function (string $class) use ($basePath) {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $basePath . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require $basePath . '/app/helpers.php';

$appConfig = require $basePath . '/config/app.php';
$dbConfig = require $basePath . '/config/database.php';

$GLOBALS['app_config'] = $appConfig;

session_name($appConfig['session_name']);
session_start();

$db = new App\Core\Database($dbConfig);
$GLOBALS['db'] = $db;

$router = new App\Core\Router();
$routes = require $basePath . '/config/routes.php';
$routes($router);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $db, $appConfig);
