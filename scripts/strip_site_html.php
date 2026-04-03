#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$options = getopt('', ['dry-run', 'help']);

if (isset($options['help'])) {
    echo "Usage: php scripts/strip_site_html.php [--dry-run]
";
    exit(0);
}

$dryRun = array_key_exists('dry-run', $options);

$rows = db()->fetchAll(
    "SELECT id, description, original_description
     FROM sites
     WHERE (description IS NOT NULL AND description <> '')
        OR (original_description IS NOT NULL AND original_description <> '')
     ORDER BY id ASC"
);

$updated = 0;

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $oldDescription = (string) ($row['description'] ?? '');
    $oldOriginalDescription = (string) ($row['original_description'] ?? '');

    $newDescription = sanitize_plain_text($oldDescription);
    $newOriginalDescription = sanitize_plain_text($oldOriginalDescription);

    if ($newDescription === $oldDescription && $newOriginalDescription === $oldOriginalDescription) {
        continue;
    }

    if (!$dryRun) {
        db()->query(
            "UPDATE sites
             SET description = ?, original_description = ?, updated_at = NOW()
             WHERE id = ?",
            [$newDescription, $newOriginalDescription, $id]
        );
    }

    $updated++;
    echo ($dryRun ? '[DRY RUN] ' : '') . "Updated site ID {$id}" . PHP_EOL;
}

echo ($dryRun ? '[DRY RUN] ' : '') . "Done. Updated {$updated} site(s)." . PHP_EOL;
