#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$args = parse_cli_args($argv);

$pathArg = isset($args['path']) ? trim((string) $args['path']) : '';
$idArg = isset($args['id']) ? (int) $args['id'] : 0;
$outputArg = isset($args['output']) ? trim((string) $args['output']) : '';
$statusArg = isset($args['status']) ? trim((string) $args['status']) : '';

$isAll = strtolower(trim($pathArg, '/')) === 'all';

if (!$isAll && $pathArg === '' && $idArg <= 0) {
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  php scripts/export_category_snapshot.php --path=computers/programming [--output=programming_snapshot.txt] [--status=active]\n");
    fwrite(STDERR, "  php scripts/export_category_snapshot.php --id=123 [--output=category_123_snapshot.txt] [--status=active,flagged]\n");
    fwrite(STDERR, "  php scripts/export_category_snapshot.php --path=all [--output=all_categories_snapshot.txt] [--status=active,flagged]\n");
    exit(1);
}

$category = null;
$basePath = '';
$rootLabel = 'All Categories';

if ($isAll) {
    $categories = db()->fetchAll(
        'SELECT id, parent_id, name, path, description, is_active
         FROM categories
         ORDER BY path ASC'
    );
} else {
    if ($idArg > 0) {
        $category = db()->fetch('SELECT * FROM categories WHERE id = ?', [$idArg]);
    } else {
        $category = db()->fetch('SELECT * FROM categories WHERE path = ?', [trim($pathArg, '/')]);
    }

    if (!$category) {
        fwrite(STDERR, "Category not found.\n");
        exit(1);
    }

    $basePath = trim((string) $category['path'], '/');
    $rootLabel = display_name((string) $category['name']);
    $branchLike = $basePath . '/%';

    $categories = db()->fetchAll(
        'SELECT id, parent_id, name, path, description, is_active
         FROM categories
         WHERE path = ? OR path LIKE ?
         ORDER BY path ASC',
        [$basePath, $branchLike]
    );
}

$statuses = parse_statuses($statusArg);

$sitesSql = 'SELECT
                s.id,
                s.category_id,
                s.title,
                s.url,
                s.normalized_url,
                s.description,
                s.status,
                s.is_active,
                s.created_at,
                s.updated_at,
                c.path AS category_path,
                c.name AS category_name
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id';

$siteParams = [];

if ($isAll) {
    $sitesSql .= ' WHERE 1=1';
} else {
    $sitesSql .= ' WHERE c.path = ? OR c.path LIKE ?';
    $siteParams = [$basePath, $branchLike];
}

if ($statuses !== []) {
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $sitesSql .= " AND s.status IN ({$placeholders})";
    $siteParams = array_merge($siteParams, $statuses);
}

$sitesSql .= ' ORDER BY c.path ASC, s.title ASC, s.id ASC';
$sites = db()->fetchAll($sitesSql, $siteParams);

$sitesByCategory = [];
foreach ($sites as $site) {
    $sitesByCategory[(string) $site['category_path']][] = $site;
}

$outputPath = resolve_output_path($outputArg, $basePath, $isAll);
$dir = dirname($outputPath);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$lines = [];
$lines[] = 'Category Snapshot';
$lines[] = 'Root Category: ' . $rootLabel;
$lines[] = 'Path: ' . ($isAll ? 'all' : $basePath);
$lines[] = 'Generated: ' . date('Y-m-d H:i:s');
$lines[] = 'Category Count: ' . count($categories);
$lines[] = 'Site Count: ' . count($sites);
$lines[] = 'Status Filter: ' . ($statuses === [] ? 'all' : implode(', ', $statuses));
$lines[] = '';
$lines[] = '== Categories ==';

foreach ($categories as $row) {
    $lines[] = '- ' . path_display((string) $row['path']);
}

$lines[] = '';
$lines[] = '== Sites ==';

if ($sites === []) {
    $lines[] = '(No sites found in this category subtree)';
} else {
    foreach ($categories as $row) {
        $categoryPath = (string) $row['path'];
        $categorySites = $sitesByCategory[$categoryPath] ?? [];

        if ($categorySites === []) {
            continue;
        }

        $lines[] = '[' . path_display($categoryPath) . ']';

        foreach ($categorySites as $site) {
            $lines[] = '- ' . (string) $site['title'];
            $lines[] = '  URL: ' . (string) $site['url'];
            $lines[] = '  Status: ' . (string) $site['status'];

            $description = trim((string) ($site['description'] ?? ''));
            if ($description !== '') {
                $description = preg_replace("/\r\n?|\n/", ' ', $description) ?? $description;
                $lines[] = '  Description: ' . $description;
            }

            $lines[] = '';
        }
    }
}

$content = implode(PHP_EOL, $lines) . PHP_EOL;
file_put_contents($outputPath, $content);

echo 'Snapshot written to: ' . $outputPath . PHP_EOL;
echo 'Categories exported: ' . count($categories) . PHP_EOL;
echo 'Sites exported: ' . count($sites) . PHP_EOL;

function parse_cli_args(array $argv): array
{
    $args = [];

    foreach (array_slice($argv, 1) as $arg) {
        if (!str_starts_with($arg, '--')) {
            continue;
        }

        $arg = substr($arg, 2);
        if ($arg === '') {
            continue;
        }

        $parts = explode('=', $arg, 2);
        $key = $parts[0];
        $value = $parts[1] ?? '1';
        $args[$key] = $value;
    }

    return $args;
}

function parse_statuses(string $statusArg): array
{
    if ($statusArg === '') {
        return [];
    }

    $allowed = ['active', 'dead', 'flagged', 'hidden'];
    $statuses = array_values(array_unique(array_filter(array_map(
        static fn (string $value): string => strtolower(trim($value)),
        explode(',', $statusArg)
    ))));

    foreach ($statuses as $status) {
        if (!in_array($status, $allowed, true)) {
            fwrite(STDERR, 'Invalid status: ' . $status . PHP_EOL);
            exit(1);
        }
    }

    return $statuses;
}

function resolve_output_path(string $outputArg, string $basePath, bool $isAll = false): string
{
    if ($outputArg !== '') {
        if (preg_match('~^[A-Za-z]:[\\\\/]~', $outputArg) || str_starts_with($outputArg, '/') || str_starts_with($outputArg, '\\')) {
            return $outputArg;
        }

        return dirname(__DIR__) . DIRECTORY_SEPARATOR . $outputArg;
    }

    if ($isAll) {
        $safeName = 'all_categories';
    } else {
        $safeName = str_replace('/', '_', trim($basePath, '/'));
        if ($safeName === '') {
            $safeName = 'category_snapshot';
        }
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'exports' . DIRECTORY_SEPARATOR . $safeName . '_snapshot.txt';
}

function path_display(string $path): string
{
    $parts = array_filter(explode('/', trim($path, '/')));
    $parts = array_map(static fn (string $part): string => display_name($part), $parts);
    return implode(' / ', $parts);
}