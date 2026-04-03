#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$options = getopt('', ['dry-run', 'help']);

if (isset($options['help'])) {
    echo "Usage: php scripts/strip_category_html.php [--dry-run]
";
    exit(0);
}

$dryRun = array_key_exists('dry-run', $options);

$rows = db()->fetchAll(
    "SELECT id, description
     FROM categories
     WHERE description IS NOT NULL
       AND description <> ''
     ORDER BY id ASC"
);

$updated = 0;

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $oldDescription = (string) ($row['description'] ?? '');
    $newDescription = sanitize_plain_text($oldDescription);

    if ($newDescription === $oldDescription) {
        continue;
    }

    if (!$dryRun) {
        db()->query(
            'UPDATE categories SET description = ?, updated_at = NOW() WHERE id = ?',
            [$newDescription, $id]
        );
    }

    $updated++;
    echo ($dryRun ? '[DRY RUN] ' : '') . "Updated category ID {$id}" . PHP_EOL;
}

echo ($dryRun ? '[DRY RUN] ' : '') . "Done. Updated {$updated} categor" . ($updated === 1 ? 'y.' : 'ies.') . PHP_EOL;
