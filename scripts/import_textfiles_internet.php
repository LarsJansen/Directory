#!/usr/bin/env php
<?php

declare(strict_types=1);

define('BASE_PATH', realpath(__DIR__ . '/..'));

$options = getopt('', [
    'source::',
    'index::',
    'output::',
    'category::',
    'include-dangerous',
    'min-size::',
    'max-size::',
    'limit::',
    'flat',
    'auto-create-categories::',
    'dry-run',
]);

$sourceDir = resolve_path($options['source'] ?? 'storage/textfiles/internet');
$indexFile = resolve_path($options['index'] ?? 'storage/textfiles/internet/index.html');
$outputSql = resolve_path($options['output'] ?? 'storage/exports/textfiles_internet_import.sql');
$outputReport = preg_replace('/\.sql$/i', '_report.txt', $outputSql) ?: ($outputSql . '_report.txt');
$categoryPath = trim((string) ($options['category'] ?? 'text-archives/internet'), '/');

$includeDangerous = array_key_exists('include-dangerous', $options);
$minSize = isset($options['min-size']) ? max(0, (int) $options['min-size']) : 0;
$maxSize = isset($options['max-size']) ? max(0, (int) $options['max-size']) : PHP_INT_MAX;
$limit = isset($options['limit']) ? max(0, (int) $options['limit']) : 0;
$recursive = !array_key_exists('flat', $options);
$autoCreateCategories = !isset($options['auto-create-categories']) || (string) $options['auto-create-categories'] !== '0';
$dryRun = array_key_exists('dry-run', $options);

echo "Resolved paths:\n";
echo "  Source Dir              : {$sourceDir}\n";
echo "  Root Index Override     : {$indexFile}\n";
echo "  Output SQL              : {$outputSql}\n";
echo "  Report TXT              : {$outputReport}\n";
echo "  Base Category           : {$categoryPath}\n";
echo "  Recursive Scan          : " . ($recursive ? 'yes' : 'no') . "\n";
echo "  Auto-create Categories  : " . ($autoCreateCategories ? 'yes' : 'no') . "\n";
echo "  Include Dangerous       : " . ($includeDangerous ? 'yes' : 'no') . "\n";
echo "  Dry Run                 : " . ($dryRun ? 'yes' : 'no') . "\n";
echo "  Limit                   : " . ($limit > 0 ? (string) $limit : '[none]') . "\n\n";

if (!is_dir($sourceDir)) {
    fwrite(STDERR, "Source directory not found: {$sourceDir}\n");
    exit(1);
}

if (!is_file($indexFile)) {
    fwrite(STDERR, "Index file not found: {$indexFile}\n");
    exit(1);
}

$indexHtml = file_get_contents($indexFile);
if ($indexHtml === false) {
    fwrite(STDERR, "Could not read index file: {$indexFile}\n");
    exit(1);
}

$rootEntries = parse_index_entries($indexHtml);

ensure_parent_dir($outputSql);
ensure_parent_dir($outputReport);

$reportHandle = fopen($outputReport, 'wb');
if ($reportHandle === false) {
    fwrite(STDERR, "Could not write report file: {$outputReport}\n");
    exit(1);
}

$sqlHandle = fopen($outputSql, 'wb');
if ($sqlHandle === false) {
    fclose($reportHandle);
    fwrite(STDERR, "Could not write SQL file: {$outputSql}\n");
    exit(1);
}

$tempSitesPath = tempnam(sys_get_temp_dir(), 'internet_sites_');
$tempSitesHandle = fopen($tempSitesPath, 'w+b');
if ($tempSitesHandle === false) {
    fclose($reportHandle);
    fclose($sqlHandle);
    fwrite(STDERR, "Could not create temp SQL buffer.\n");
    exit(1);
}

report_line($reportHandle, 'Textfiles.com Internet Import Report');
report_line($reportHandle, 'Generated: ' . date('Y-m-d H:i:s'));
report_line($reportHandle, 'Source Dir: ' . $sourceDir);
report_line($reportHandle, 'Index File: ' . $indexFile);
report_line($reportHandle, 'Base Category: ' . $categoryPath);
report_line($reportHandle, 'Recursive Scan: ' . ($recursive ? 'yes' : 'no'));
report_line($reportHandle, 'Auto-create Categories: ' . ($autoCreateCategories ? 'yes' : 'no'));
report_line($reportHandle, 'Dry Run: ' . ($dryRun ? 'yes' : 'no'));
report_line($reportHandle, '');

write_sql_header($sqlHandle, $categoryPath, $recursive, $autoCreateCategories, $dryRun);

$categoryPaths = [$categoryPath => true];
$slugRegistry = [];
$createdCategoryCount = 0;
$importedCount = 0;
$skippedCount = 0;
$processedCount = 0;

if ($recursive) {
    $entriesByRelativePath = build_recursive_entry_map($sourceDir, $rootEntries);
    ksort($entriesByRelativePath, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($entriesByRelativePath as $relativePathKey => $entry) {
        if ($limit > 0 && $processedCount >= $limit) {
            break;
        }

        $processedCount++;
        $relativePath = (string) $relativePathKey;
        $relativeDir = trim(str_replace('\\', '/', dirname($relativePath)), './');
        if ($relativeDir === '.' || $relativeDir === '/') {
            $relativeDir = '';
        }

        $filename = basename($relativePath);
        $categoryForEntry = map_directory_to_category($categoryPath, $relativeDir);

        process_entry(
            $entry,
            $filename,
            $relativePath,
            $sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath),
            $categoryForEntry,
            $categoryPaths,
            $slugRegistry,
            $includeDangerous,
            $minSize,
            $maxSize,
            $dryRun,
            $autoCreateCategories,
            $sqlHandle,
            $tempSitesHandle,
            $reportHandle,
            $importedCount,
            $skippedCount,
            $createdCategoryCount
        );
    }
} else {
    foreach ($rootEntries as $entry) {
        if ($limit > 0 && $processedCount >= $limit) {
            break;
        }

        $processedCount++;
        $filename = (string) ($entry['filename'] ?? '');
        process_entry(
            $entry,
            $filename,
            $filename,
            $sourceDir . DIRECTORY_SEPARATOR . $filename,
            $categoryPath,
            $categoryPaths,
            $slugRegistry,
            $includeDangerous,
            $minSize,
            $maxSize,
            $dryRun,
            $autoCreateCategories,
            $sqlHandle,
            $tempSitesHandle,
            $reportHandle,
            $importedCount,
            $skippedCount,
            $createdCategoryCount
        );
    }
}

write_sql_footer($sqlHandle, $tempSitesHandle, $tempSitesPath, $importedCount, count($categoryPaths), $dryRun);

report_line($reportHandle, '');
report_line($reportHandle, 'Imported: ' . $importedCount);
report_line($reportHandle, 'Skipped: ' . $skippedCount);
report_line($reportHandle, 'Categories Touched: ' . count($categoryPaths));
report_line($reportHandle, 'Categories Auto-Created: ' . $createdCategoryCount);

fclose($reportHandle);
fclose($sqlHandle);

echo 'Imported entries: ' . $importedCount . PHP_EOL;
echo 'Skipped entries: ' . $skippedCount . PHP_EOL;
echo 'Categories touched: ' . count($categoryPaths) . PHP_EOL;
echo 'Categories auto-created: ' . $createdCategoryCount . PHP_EOL;
echo 'SQL file written to: ' . $outputSql . PHP_EOL;
echo 'Report written to: ' . $outputReport . PHP_EOL;

function process_entry(
    array $entry,
    string $filename,
    string $relativePath,
    string $filePath,
    string $categoryPath,
    array &$categoryPaths,
    array &$slugRegistry,
    bool $includeDangerous,
    int $minSize,
    int $maxSize,
    bool $dryRun,
    bool $autoCreateCategories,
    $sqlHandle,
    $tempSitesHandle,
    $reportHandle,
    int &$importedCount,
    int &$skippedCount,
    int &$createdCategoryCount
): void {
    $filename = (string) $filename;
    $title = (string) ($entry['title'] ?? '');
    $bodyDescription = (string) ($entry['description'] ?? '');
    $sizeFromIndex = (int) ($entry['size'] ?? 0);

    if ($filename === '' || $title === '' || $bodyDescription === '') {
        report_line($reportHandle, '[SKIP INVALID] ' . $relativePath);
        $skippedCount++;
        return;
    }

    if (looks_like_collection_directory($filename)) {
        report_line($reportHandle, '[SKIP DIR] ' . $relativePath . ' | ' . $bodyDescription);
        $skippedCount++;
        return;
    }

    if (!$includeDangerous && is_excluded_by_description($bodyDescription)) {
        report_line($reportHandle, '[SKIP FILTER] ' . $relativePath . ' | ' . $bodyDescription);
        $skippedCount++;
        return;
    }

    if (!is_file($filePath)) {
        report_line($reportHandle, '[SKIP MISSING] ' . $relativePath . ' | ' . $bodyDescription);
        $skippedCount++;
        return;
    }

    ensure_category_paths($sqlHandle, $categoryPath, $categoryPaths, $createdCategoryCount, $autoCreateCategories);

    $actualSize = filesize($filePath);
    if ($actualSize === false) {
        $actualSize = 0;
    }

    if ($actualSize < $minSize) {
        report_line($reportHandle, '[SKIP SMALL] ' . $relativePath . ' | ' . $actualSize . ' bytes | ' . $bodyDescription);
        $skippedCount++;
        return;
    }

    if ($actualSize > $maxSize) {
        report_line($reportHandle, '[SKIP LARGE] ' . $relativePath . ' | ' . $actualSize . ' bytes | ' . $bodyDescription);
        $skippedCount++;
        return;
    }

    $bodyText = normalize_text_file($filePath);
    if ($bodyText === '') {
        report_line($reportHandle, '[SKIP EMPTY] ' . $relativePath . ' | ' . $bodyDescription);
        $skippedCount++;
        return;
    }

    $slug = unique_slug_for_category($title, $filename, $categoryPath, $slugRegistry);
    $shortDescription = 'Historical internet-era document: ' . $bodyDescription;
    $originalUrl = 'http://textfiles.com/internet/' . str_replace('%2F', '/', rawurlencode(str_replace('\\', '/', $relativePath)));
    $sourceKey = 'textfiles:internet:' . str_replace('\\', '/', $relativePath);

    if ($dryRun) {
        report_line($reportHandle, '[DRY RUN] ' . $relativePath . ' | ' . $slug . ' | ' . $bodyDescription);
        $importedCount++;
        return;
    }

    write_site_insert(
        $tempSitesHandle,
        $relativePath,
        $sizeFromIndex,
        $actualSize,
        $categoryPath,
        $title,
        $slug,
        $shortDescription,
        $originalUrl,
        $sourceKey,
        $bodyText
    );

    report_line($reportHandle, '[IMPORT] ' . $relativePath . ' | ' . $slug . ' | ' . $bodyDescription);
    $importedCount++;
}

function resolve_path(string $path): string
{
    $path = trim($path);

    if ($path === '') {
        return BASE_PATH;
    }

    if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
        return $path;
    }

    if (str_starts_with($path, '/') || str_starts_with($path, '\\')) {
        return $path;
    }

    return BASE_PATH . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function ensure_parent_dir(string $filePath): void
{
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function parse_index_entries(string $html): array
{
    $entries = [];

    if (!preg_match_all(
        '~<TR\s+VALIGN=TOP><TD[^>]*><A\s+HREF="([^"]+)">.*?</A>\s*<tab\s+to=T><TD>\s*([0-9]+)?<BR><TD>\s*(.*?)(?=<TR|$)~is',
        $html,
        $matches,
        PREG_SET_ORDER
    )) {
        return $entries;
    }

    foreach ($matches as $match) {
        $filename = trim(html_entity_decode((string) $match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $size = isset($match[2]) && $match[2] !== '' ? (int) $match[2] : 0;
        $description = trim(html_entity_decode(strip_tags((string) $match[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($filename === '' || $description === '') {
            continue;
        }

        $entries[] = [
            'filename' => $filename,
            'size' => $size,
            'description' => $description,
            'title' => $description,
        ];
    }

    return $entries;
}

function build_recursive_entry_map(string $sourceDir, array $rootEntries): array
{
    $rootMap = [];
    foreach ($rootEntries as $entry) {
        $key = str_replace('\\', '/', (string) ($entry['filename'] ?? ''));
        if ($key !== '') {
            $rootMap[$key] = $entry;
        }
    }

    $map = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $fileInfo) {
        /** @var SplFileInfo $fileInfo */
        if (!$fileInfo->isFile()) {
            continue;
        }

        $basename = $fileInfo->getBasename();
        if (strcasecmp($basename, 'index.html') === 0 || strcasecmp($basename, 'index.htm') === 0) {
            continue;
        }

        $fullPath = $fileInfo->getPathname();
        $relativePath = ltrim(str_replace('\\', '/', substr($fullPath, strlen($sourceDir))), '/');

        if ($relativePath === '') {
            continue;
        }

        if (isset($rootMap[$relativePath])) {
            $entry = $rootMap[$relativePath];
            $entry['filename'] = $relativePath;
            $map[$relativePath] = $entry;
            continue;
        }

        $map[$relativePath] = [
            'filename' => $relativePath,
            'size' => (int) $fileInfo->getSize(),
            'description' => derive_description_from_path($relativePath),
            'title' => derive_title_from_path($relativePath),
        ];
    }

    foreach ($rootMap as $relativePath => $entry) {
        if (!isset($map[$relativePath])) {
            $map[$relativePath] = $entry;
        }
    }

    return $map;
}

function derive_title_from_path(string $relativePath): string
{
    $name = pathinfo($relativePath, PATHINFO_FILENAME);
    $name = str_replace(['_', '-'], ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name) ?? $name;
    $name = trim($name);

    if ($name === '') {
        $name = basename($relativePath);
    }

    return ucwords($name);
}

function derive_description_from_path(string $relativePath): string
{
    $base = basename($relativePath);
    return 'Imported from local textfiles dump: ' . $base;
}

function looks_like_collection_directory(string|int $filename): bool
{
    $filename = trim((string) $filename);
    if ($filename === '') {
        return false;
    }

    if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '.')) {
        return false;
    }

    if (ctype_digit($filename)) {
        return false;
    }

    return preg_match('/^[A-Z][A-Z0-9_-]{1,}$/', $filename) === 1;
}

function is_excluded_by_description(string $description): bool
{
    $needles = [
        'how to crash',
        'crash and destroy',
        'bbs infiltration',
        'hack cbv',
        'bust avoidance',
        'phreak',
        'pirate bbs',
        'break into a bbs',
        'uploaded program alert',
        'scraping the bottom of the barrel',
        'fucking hostile',
    ];

    $haystack = strtolower($description);

    foreach ($needles as $needle) {
        if (str_contains($haystack, $needle)) {
            return true;
        }
    }

    return false;
}

function unique_slug_for_category(string $title, string $filename, string $categoryPath, array &$slugRegistry): string
{
    $base = unique_slug($title, $filename);

    if (!isset($slugRegistry[$categoryPath])) {
        $slugRegistry[$categoryPath] = [];
    }

    if (!isset($slugRegistry[$categoryPath][$base])) {
        $slugRegistry[$categoryPath][$base] = 1;
        return $base;
    }

    $slugRegistry[$categoryPath][$base]++;
    return $base . '-' . $slugRegistry[$categoryPath][$base];
}

function unique_slug(string $title, string $filename): string
{
    $base = slugify($title);

    if ($base !== 'item') {
        return $base;
    }

    return slugify(pathinfo($filename, PATHINFO_FILENAME));
}

function slugify(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return 'item';
    }

    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;
    $text = trim($text, '-');

    return $text !== '' ? $text : 'item';
}

function map_directory_to_category(string $baseCategory, string $relativeDir): string
{
    $relativeDir = trim(str_replace('\\', '/', $relativeDir), '/');
    if ($relativeDir === '') {
        return $baseCategory;
    }

    $segments = array_filter(explode('/', $relativeDir), static fn ($segment) => trim($segment) !== '');
    $slugs = array_map(
        static fn ($segment) => slugify((string) $segment),
        $segments
    );

    return trim($baseCategory . '/' . implode('/', $slugs), '/');
}

function ensure_category_paths($sqlHandle, string $categoryPath, array &$categoryPaths, int &$createdCategoryCount, bool $autoCreateCategories): void
{
    $parts = explode('/', trim($categoryPath, '/'));
    $current = '';
    $parent = null;

    foreach ($parts as $part) {
        $current = $current === '' ? $part : $current . '/' . $part;
        if (!isset($categoryPaths[$current])) {
            $categoryPaths[$current] = true;
            $createdCategoryCount++;

            if ($autoCreateCategories) {
                fwrite(
                    $sqlHandle,
                    category_sql_block(
                        $current,
                        $parent,
                        humanize_slug($part),
                        'Auto-created by recursive textfiles Computers importer.'
                    )
                );
            }
        }

        $parent = $current;
    }
}

function normalize_text_file(string $path): string
{
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return '';
    }

    if (mb_check_encoding($raw, 'UTF-8')) {
        $text = $raw;
    } else {
        $text = null;
        $iconvEncodings = ['IBM437', 'CP850', 'Windows-1252', 'ISO-8859-1'];

        foreach ($iconvEncodings as $encoding) {
            $converted = @iconv($encoding, 'UTF-8//IGNORE', $raw);
            if ($converted !== false && $converted !== '') {
                $text = $converted;
                break;
            }
        }

        if ($text === null) {
            $mbEncodings = ['Windows-1252', 'ISO-8859-1'];
            foreach ($mbEncodings as $encoding) {
                try {
                    $converted = @mb_convert_encoding($raw, 'UTF-8', $encoding);
                    if ($converted !== false && $converted !== '') {
                        $text = $converted;
                        break;
                    }
                } catch (\ValueError $e) {
                }
            }
        }

        if ($text === null) {
            $text = $raw;
        }
    }

    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = str_replace("\0", '', $text);

    return $text;
}

function sql_quote(string $value): string
{
    return "'" . str_replace(
        ["\\", "'", "\n", "\r", "\x1a"],
        ["\\\\", "\\'", "\\n", "\\r", "\\Z"],
        $value
    ) . "'";
}

function report_line($handle, string $line): void
{
    fwrite($handle, $line . PHP_EOL);
}

function write_sql_header($handle, string $categoryPath, bool $recursive, bool $autoCreateCategories, bool $dryRun): void
{
    fwrite($handle, "START TRANSACTION;\n\n");
    fwrite($handle, "-- Textfiles.com Computers recursive import generated by scripts/import_textfiles_computers.php\n");
    fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "-- Base Category: {$categoryPath}\n");
    fwrite($handle, "-- Recursive Scan: " . ($recursive ? 'yes' : 'no') . "\n");
    fwrite($handle, "-- Auto-create Categories: " . ($autoCreateCategories ? 'yes' : 'no') . "\n");
    fwrite($handle, "-- Dry Run: " . ($dryRun ? 'yes' : 'no') . "\n\n");

    fwrite($handle, "INSERT INTO import_batches (source_name, batch_label, notes, status, total_categories, total_sites, started_at, completed_at)\n");
    fwrite($handle, "VALUES ('textfiles.com', 'Computers Textfiles Import', 'Generated from local textfiles Computers dump (recursive)', 'completed', 0, 0, NOW(), NOW());\n\n");
    fwrite($handle, "SET @batch_id = LAST_INSERT_ID();\n");
    fwrite($handle, "SET @created_categories = 0;\n");
    fwrite($handle, "SET @imported_sites = 0;\n\n");

    fwrite($handle, category_sql_block('text-archives', null, 'Text Archives', 'Auto-created by recursive textfiles Computers importer.'));
    if ($categoryPath !== 'text-archives') {
        $segments = explode('/', $categoryPath);
        $running = '';
        $parent = null;
        foreach ($segments as $segment) {
            $running = $running === '' ? $segment : $running . '/' . $segment;
            if ($running === 'text-archives') {
                $parent = 'text-archives';
                continue;
            }
            fwrite($handle, category_sql_block($running, $parent, humanize_slug($segment), 'Auto-created by recursive textfiles Computers importer.'));
            $parent = $running;
        }
    }
}

function category_sql_block(string $path, ?string $parentPath, string $name, string $description): string
{
    $slug = basename($path);
    $parentSelect = $parentPath === null
        ? 'NULL'
        : '(SELECT id FROM categories WHERE path = ' . sql_quote($parentPath) . ' LIMIT 1)';

    return "-- Ensure category exists: {$path}\n"
        . "INSERT INTO categories (\n"
        . "    parent_id,\n"
        . "    slug,\n"
        . "    path,\n"
        . "    name,\n"
        . "    description,\n"
        . "    sort_order,\n"
        . "    is_active,\n"
        . "    source_type,\n"
        . "    source_key,\n"
        . "    import_batch_id,\n"
        . "    created_at,\n"
        . "    updated_at\n"
        . ")\n"
        . "SELECT\n"
        . "    {$parentSelect},\n"
        . "    " . sql_quote($slug) . ",\n"
        . "    " . sql_quote($path) . ",\n"
        . "    " . sql_quote($name) . ",\n"
        . "    " . sql_quote($description) . ",\n"
        . "    0,\n"
        . "    1,\n"
        . "    'manual',\n"
        . "    " . sql_quote('textfiles:auto-category:' . $path) . ",\n"
        . "    @batch_id,\n"
        . "    NOW(),\n"
        . "    NOW()\n"
        . "FROM DUAL\n"
        . "WHERE NOT EXISTS (\n"
        . "    SELECT 1 FROM categories WHERE path = " . sql_quote($path) . "\n"
        . ");\n\n";
}

function write_site_insert(
    $handle,
    string $relativePath,
    int $sizeFromIndex,
    int $actualSize,
    string $categoryPath,
    string $title,
    string $slug,
    string $shortDescription,
    string $originalUrl,
    string $sourceKey,
    string $bodyText
): void {
    fwrite($handle, '-- ' . $relativePath . ' (' . $sizeFromIndex . ' bytes in index; ' . $actualSize . " bytes on disk)\n");
    fwrite($handle, "INSERT INTO sites (\n");
    fwrite($handle, "    category_id,\n");
    fwrite($handle, "    title,\n");
    fwrite($handle, "    slug,\n");
    fwrite($handle, "    url,\n");
    fwrite($handle, "    normalized_url,\n");
    fwrite($handle, "    description,\n");
    fwrite($handle, "    original_url,\n");
    fwrite($handle, "    status,\n");
    fwrite($handle, "    is_active,\n");
    fwrite($handle, "    source_type,\n");
    fwrite($handle, "    source_key,\n");
    fwrite($handle, "    import_batch_id,\n");
    fwrite($handle, "    content_type,\n");
    fwrite($handle, "    body_text,\n");
    fwrite($handle, "    text_source_note,\n");
    fwrite($handle, "    text_author,\n");
    fwrite($handle, "    created_at,\n");
    fwrite($handle, "    updated_at\n");
    fwrite($handle, ")\n");
    fwrite($handle, "SELECT\n");
    fwrite($handle, "    c.id,\n");
    fwrite($handle, '    ' . sql_quote($title) . ",\n");
    fwrite($handle, '    ' . sql_quote($slug) . ",\n");
    fwrite($handle, "    NULL,\n");
    fwrite($handle, "    NULL,\n");
    fwrite($handle, '    ' . sql_quote($shortDescription) . ",\n");
    fwrite($handle, '    ' . sql_quote($originalUrl) . ",\n");
    fwrite($handle, "    'active',\n");
    fwrite($handle, "    1,\n");
    fwrite($handle, "    'manual',\n");
    fwrite($handle, '    ' . sql_quote($sourceKey) . ",\n");
    fwrite($handle, "    @batch_id,\n");
    fwrite($handle, "    'text',\n");
    fwrite($handle, '    ' . sql_quote($bodyText) . ",\n");
    fwrite($handle, "    'Mirrored from textfiles.com for historical preservation.',\n");
    fwrite($handle, "    NULL,\n");
    fwrite($handle, "    NOW(),\n");
    fwrite($handle, "    NOW()\n");
    fwrite($handle, "FROM categories c\n");
    fwrite($handle, 'WHERE c.path = ' . sql_quote($categoryPath) . "\n");
    fwrite($handle, "  AND NOT EXISTS (\n");
    fwrite($handle, "      SELECT 1\n");
    fwrite($handle, "      FROM sites s\n");
    fwrite($handle, '      WHERE s.source_key = ' . sql_quote($sourceKey) . "\n");
    fwrite($handle, "  );\n\n");
}

function write_sql_footer($sqlHandle, $tempSitesHandle, string $tempSitesPath, int $importedCount, int $categoryCount, bool $dryRun): void
{
    fflush($tempSitesHandle);
    rewind($tempSitesHandle);
    stream_copy_to_stream($tempSitesHandle, $sqlHandle);
    fclose($tempSitesHandle);
    @unlink($tempSitesPath);

    fwrite($sqlHandle, "UPDATE import_batches\n");
    fwrite($sqlHandle, "SET total_categories = " . max(1, $categoryCount) . ",\n");
    fwrite($sqlHandle, "    total_sites = {$importedCount},\n");
    fwrite($sqlHandle, "    completed_at = NOW(),\n");
    fwrite($sqlHandle, "    status = 'completed'\n");
    fwrite($sqlHandle, "WHERE id = @batch_id;\n\n");
    fwrite($sqlHandle, $dryRun ? "ROLLBACK;\n" : "COMMIT;\n");
}

function humanize_slug(string $slug): string
{
    $slug = str_replace(['-', '_'], ' ', $slug);
    $slug = preg_replace('/\s+/', ' ', $slug) ?? $slug;
    return ucwords(trim($slug));
}
