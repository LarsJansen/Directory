<?php
$currentPath = request_path();
$pageTitle = $pageTitle ?? config('name');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= e(config('name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(base_url('/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary-subtle">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e(base_url('/')) ?>"><?= e(config('name')) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link<?= $currentPath === '/' ? ' active' : '' ?>" href="<?= e(base_url('/')) ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link<?= str_starts_with($currentPath, '/category') ? ' active' : '' ?>" href="<?= e(base_url('/category')) ?>">Browse</a></li>
                <li class="nav-item"><a class="nav-link<?= str_starts_with($currentPath, '/submit') ? ' active' : '' ?>" href="<?= e(base_url('/submit')) ?>">Submit Site</a></li>
                <li class="nav-item"><a class="nav-link<?= str_starts_with($currentPath, '/search') ? ' active' : '' ?>" href="<?= e(base_url('/search')) ?>">Search</a></li>
                <li class="nav-item"><a class="nav-link<?= str_starts_with($currentPath, '/editor') ? ' active' : '' ?>" href="<?= e(base_url('/editor')) ?>">Editor</a></li>
            </ul>
            <form class="d-flex" method="get" action="<?= e(base_url('/search')) ?>">
                <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Search directory" value="<?= e($_GET['q'] ?? '') ?>">
                <button class="btn btn-outline-light btn-sm" type="submit">Search</button>
            </form>
        </div>
    </div>
</nav>
<div class="container my-4">
    <?php require __DIR__ . '/flash.php'; ?>
