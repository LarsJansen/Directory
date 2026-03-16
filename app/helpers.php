<?php

function config(string $key, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }

    return $config[$key] ?? $default;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function errors(string $key): ?string
{
    return $_SESSION['_errors'][$key] ?? null;
}

function flash(?string $key = null, mixed $value = null): mixed
{
    if ($key !== null && $value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    if ($key !== null) {
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    return $_SESSION['_flash'] ?? [];
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_editor_logged_in(): bool
{
    return !empty($_SESSION['editor']);
}

function current_editor(): ?array
{
    return $_SESSION['editor'] ?? null;
}

function selected_if(mixed $left, mixed $right): string
{
    return (string) $left === (string) $right ? 'selected' : '';
}
