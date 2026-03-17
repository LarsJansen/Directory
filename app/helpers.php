<?php

function config(string $key, $default = null)
{
    $config = $GLOBALS['app_config'] ?? [];
    return $config[$key] ?? $default;
}

function db()
{
    return $GLOBALS['db'];
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) config('base_url', ''), '/');
    $path = '/' . ltrim($path, '/');

    return $base . ($path === '/' ? '' : $path);
}

function redirect_to(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function request_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return parse_url($uri, PHP_URL_PATH) ?: '/';
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function flash(string $key, ?string $message = null)
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function old(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function current_user(): ?array
{
    return $_SESSION['editor_user'] ?? null;
}

function is_editor_logged_in(): bool
{
    return current_user() !== null;
}

function normalize_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return strtolower(rtrim($url, '/'));
    }

    $scheme = strtolower($parts['scheme'] ?? 'https');
    $host = strtolower($parts['host']);
    $port = $parts['port'] ?? null;
    $path = $parts['path'] ?? '';
    $query = $parts['query'] ?? '';

    if (($scheme === 'http' && $port == 80) || ($scheme === 'https' && $port == 443)) {
        $port = null;
    }

    $normalized = $scheme . '://' . $host;
    if ($port) {
        $normalized .= ':' . $port;
    }

    $path = $path === '' ? '' : rtrim($path, '/');
    $normalized .= $path;

    if ($query !== '') {
        $normalized .= '?' . $query;
    }

    return $normalized;
}

function slugify(string $text): string
{
    $text = trim($text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text !== '' ? $text : 'item';
}

function build_pagination(int $total, int $page, int $perPage): array
{
    $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
    ];
}

function page_url(string $path, array $query = []): string
{
    $url = base_url($path);
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }
    return $url;
}
