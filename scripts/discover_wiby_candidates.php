<?php

declare(strict_types=1);

ini_set('memory_limit', '512M');
set_time_limit(120);

require __DIR__ . '/bootstrap.php';

use App\Models\Category;
use App\Models\Submission;

$db = db();
$submissionModel = new Submission($db);
$categoryModel = new Category($db);

$query = trim((string) ($_GET['q'] ?? $argv[1] ?? 'bulletin board systems'));
$limit = max(1, min(25, (int) ($_GET['limit'] ?? $argv[2] ?? 10)));
$categoryPath = trim((string) ($_GET['category'] ?? $argv[3] ?? ''));

if ($query === '' || $categoryPath === '') {
    echo "Usage (CLI): php scripts/discover_wiby_candidates.php \"query\" 10 \"category/path\"\n";
    echo "Usage (web): /scripts/discover_wiby_candidates.php?q=bbs&limit=10&category=text-archives/bulletin-board-system\n";
    exit(1);
}

$category = $categoryModel->findByPath($categoryPath);
if (!$category) {
    echo "Category not found: {$categoryPath}\n";
    exit(1);
}

echo "Use the editor tool instead for the full guided workflow:\n";
echo base_url('/editor/submissions/discover') . "\n\n";
echo "This script intentionally remains a lightweight wrapper.\n";
echo "Query: {$query}\nLimit: {$limit}\nCategory: {$category['path']}\n";
