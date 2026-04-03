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

    if ($base === '') {
        return $path;
    }

    return $base . $path;
}

function storage_path(string $path = ''): string
{
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';

    if ($path === '') {
        return $base;
    }

    return $base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($path, '/\\'));
}

function cache_path(string $key): string
{
    $safeKey = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $key) ?: 'cache';
    return storage_path('cache/' . $safeKey . '.phpcache');
}

function cache_remember(string $key, int $ttlSeconds, callable $callback)
{
    $ttlSeconds = max(1, $ttlSeconds);
    $path = cache_path($key);

    if (is_file($path)) {
        $payload = @unserialize((string) file_get_contents($path));
        if (is_array($payload) && isset($payload['expires_at']) && $payload['expires_at'] >= time() && array_key_exists('value', $payload)) {
            return $payload['value'];
        }
    }

    $value = $callback();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $payload = [
        'expires_at' => time() + $ttlSeconds,
        'value' => $value,
    ];

    $tmpPath = $path . '.tmp';
    @file_put_contents($tmpPath, serialize($payload), LOCK_EX);
    @rename($tmpPath, $path);

    return $value;
}

function cache_version_path(string $name): string
{
    $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name) ?: 'version';
    return storage_path('cache/' . $safeName . '.version');
}

function cache_version(string $name): string
{
    $path = cache_version_path($name);

    if (!is_file($path)) {
        cache_bump($name);
    }

    $version = @file_get_contents($path);
    $version = is_string($version) ? trim($version) : '';

    return $version !== '' ? $version : '0';
}

function cache_bump(string $name): string
{
    $path = cache_version_path($name);
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $version = sprintf('%.6f', microtime(true));
    @file_put_contents($path, $version, LOCK_EX);

    return $version;
}


function directory_cache_token(): string
{
    static $token = null;
    if ($token !== null) {
        return $token;
    }

    $version = cache_version('directory-content');
    $db = $GLOBALS['db'] ?? null;
    if (!is_object($db) || !method_exists($db, 'fetch')) {
        $token = $version;
        return $token;
    }

    try {
        $row = $db->fetch(
            "SELECT
                (SELECT COALESCE(MAX(id), 0) FROM categories) AS category_max_id,
                (SELECT COUNT(*) FROM categories) AS category_count,
                (SELECT COALESCE(MAX(id), 0) FROM sites) AS site_max_id,
                (SELECT COUNT(*) FROM sites) AS site_count"
        );

        if (!is_array($row)) {
            $token = $version;
            return $token;
        }

        $token = implode('-', [
            $version,
            (int) ($row['category_max_id'] ?? 0),
            (int) ($row['category_count'] ?? 0),
            (int) ($row['site_max_id'] ?? 0),
            (int) ($row['site_count'] ?? 0),
        ]);
        return $token;
    } catch (Throwable $e) {
        $token = $version;
        return $token;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(419);
        flash('error', 'Your session expired or the request could not be verified. Please try again.');

        $redirect = $_SERVER['HTTP_REFERER'] ?? request_path();
        header('Location: ' . $redirect);
        exit;
    }
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

function display_name(?string $name): string
{
    return str_replace('_', ' ', (string) $name);
}

function sanitize_plain_text(?string $value): string
{
    $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('~<\s*br\s*/?>~i', "
", $value);
    $value = preg_replace('~</\s*p\s*>~i', "

", $value);
    $value = strip_tags($value);
    $value = str_replace(["
", "
"], "
", $value);
    $value = preg_replace('/[ 	]+/', ' ', $value);
    $value = preg_replace('/
{3,}/', "

", $value);
    return trim($value);
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function text_substr(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null
            ? mb_substr($value, $start, null, 'UTF-8')
            : mb_substr($value, $start, $length, 'UTF-8');
    }

    return $length === null ? substr($value, $start) : substr($value, $start, $length);
}

function text_strrpos(string $haystack, string $needle)
{
    return function_exists('mb_strrpos') ? mb_strrpos($haystack, $needle, 0, 'UTF-8') : strrpos($haystack, $needle);
}


function first_meaningful_paragraph(?string $value, int $maxLength = 220): string
{
    $text = sanitize_plain_text($value);
    if ($text === '') {
        return '';
    }

    $paragraphs = preg_split('/\n\s*\n+/', $text) ?: [$text];

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim((string) $paragraph);
        if ($paragraph === '') {
            continue;
        }

        $lines = preg_split('/\n+/', $paragraph) ?: [$paragraph];
        $cleanLines = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(file|path|from|date|archive|source|author|title|subject|newsgroups|organization|keywords)\s*:/i', $line)) {
                continue;
            }

            if (preg_match('/^[=\-*#_~`]{3,}$/', $line)) {
                continue;
            }

            if (preg_match('/^[A-Z0-9 \-\._]{8,}$/', $line) && !preg_match('/[a-z]/', $line)) {
                continue;
            }

            $cleanLines[] = $line;
        }

        $candidate = trim(implode(' ', $cleanLines));
        if ($candidate === '') {
            continue;
        }

        if (text_length($candidate) < 40) {
            continue;
        }

        return excerpt_text($candidate, $maxLength);
    }

    return excerpt_text($text, $maxLength);
}

function archive_meta_source(?string $value, int $maxLength = 120): string
{
    $text = sanitize_plain_text($value);
    if ($text === '') {
        return '';
    }

    $text = preg_replace('/^\s*source\s*:\s*/i', '', $text);
    $text = preg_replace('/^\s*from\s*:\s*/i', '', $text);
    $text = preg_replace('/\s*[\|\-–—:]+\s*/u', ' — ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim((string) $text, " \t\n\r\0\x0B.,;:-");

    return excerpt_text($text, $maxLength);
}

function excerpt_text(?string $value, int $maxLength = 160): string
{
    $text = sanitize_plain_text($value);
    if ($text === '') {
        return '';
    }

    $maxLength = max(50, $maxLength);
    if (text_length($text) <= $maxLength) {
        return $text;
    }

    $excerpt = text_substr($text, 0, $maxLength + 1);
    $lastSpace = text_strrpos($excerpt, ' ');

    if ($lastSpace !== false && $lastSpace >= (int) floor($maxLength * 0.6)) {
        $excerpt = text_substr($excerpt, 0, (int) $lastSpace);
    } else {
        $excerpt = text_substr($excerpt, 0, $maxLength);
    }

    return rtrim($excerpt, " 	

 .,;:-") . '…';
}

function meta_description(?string $preferred, ?string $fallback = null, int $maxLength = 160): string
{
    $preferredText = excerpt_text($preferred, $maxLength);
    if ($preferredText !== '') {
        return $preferredText;
    }

    return excerpt_text($fallback, $maxLength);
}

function build_site_meta_description(array $site, array $category = []): string
{
    if (is_text_entry($site)) {
        $preferred = $site['description'] ?? '';
        $fallbackParts = [];

        $meaningfulParagraph = first_meaningful_paragraph($site['body_text'] ?? '');
        if ($meaningfulParagraph !== '') {
            $fallbackParts[] = $meaningfulParagraph;
        }

        if (!empty($site['text_source_note'])) {
            $fallbackParts[] = archive_meta_source((string) $site['text_source_note']);
        }
        if (!empty($category['name'])) {
            $fallbackParts[] = 'From ' . display_name((string) $category['name']) . ' in the Internet History Directory.';
        }

        return meta_description($preferred, implode(' ', array_filter($fallbackParts)), 160);
    }

    $fallback = trim((string) ($site['title'] ?? '') . '. ' . ($site['description'] ?? '') . ' ' . (!empty($category['name']) ? 'Listed under ' . display_name((string) $category['name']) . ' in the Internet History Directory.' : ''));
    return meta_description($site['description'] ?? '', $fallback, 160);
}

function build_category_meta_description(array $category, int $childCount = 0, int $siteCount = 0): string
{
    $preferred = $category['description'] ?? '';
    $name = display_name((string) ($category['name'] ?? 'Category'));
    $parts = [
        $name . ' in the Internet History Directory.',
    ];

    if ($siteCount > 0) {
        $parts[] = $siteCount . ' resource' . ($siteCount === 1 ? '' : 's') . ' listed';
    }

    if ($childCount > 0) {
        $parts[] = $childCount . ' subcategor' . ($childCount === 1 ? 'y' : 'ies');
    }

    return meta_description($preferred, implode('. ', $parts) . '.', 160);
}

function is_text_entry(array $site): bool
{
    return (($site['content_type'] ?? 'link') === 'text');
}

function entry_url(array $site, ?string $fallbackCategoryPath = null): string
{
    $categoryPath = trim((string) ($site['category_path'] ?? $fallbackCategoryPath ?? ''), '/');
    $slug = trim((string) ($site['slug'] ?? ''), '/');

    if (is_text_entry($site) && $categoryPath !== '' && $slug !== '') {
        return base_url('/category/' . $categoryPath . '/' . $slug);
    }

    if (!empty($site['url'])) {
        return (string) $site['url'];
    }

    return base_url('/category');
}
