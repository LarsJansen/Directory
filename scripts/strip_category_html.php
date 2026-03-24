<?php
// scripts/strip_category_html.php

$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=dmoz_mvp;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$stmt = $pdo->query("SELECT id, description FROM categories WHERE description IS NOT NULL AND description <> ''");
$rows = $stmt->fetchAll();

$update = $pdo->prepare("UPDATE categories SET description = ? WHERE id = ?");

foreach ($rows as $row) {
    $original = $row['description'];

    $clean = html_entity_decode($original, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $clean = strip_tags($clean);
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = trim($clean);

    if ($clean !== $original) {
        $update->execute([$clean, $row['id']]);
        echo "Updated category {$row['id']}\n";
    }
}

echo "Done.\n";
