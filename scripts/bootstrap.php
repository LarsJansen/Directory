<?php

declare(strict_types=1);

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
$GLOBALS['db'] = new App\Core\Database($dbConfig);

return [
    'basePath' => $basePath,
    'appConfig' => $appConfig,
    'db' => $GLOBALS['db'],
];
