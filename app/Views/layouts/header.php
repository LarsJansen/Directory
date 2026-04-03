<?php
$currentPath = request_path();
$pageTitle = $pageTitle ?? config('name');
$headerSearchQuery = $headerSearchQuery ?? '';
$metaDescription = trim((string)($metaDescription ?? 'Human-curated. Historically focused. Built link by link.'));
$bootstrapCssHref = base_url('/assets/css/bootstrap.min.css');
$appCssHref = base_url('/assets/css/app.css');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= e(config('name')) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link rel="preload" href="<?= e($bootstrapCssHref) ?>" as="style">
    <link rel="stylesheet" href="<?= e($bootstrapCssHref) ?>">
    <link rel="stylesheet" href="<?= e($appCssHref) ?>">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= e(base_url('/')) ?>"><?= e(config('name')) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link<?= $currentPath === '/' ? ' active' : '' ?>" href="<?= e(base_url('/')) ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link<?= str_starts_with($currentPath, '/category') ? ' active' : '' ?>" href="<?= e(base_url('/category')) ?>">Browse</a></li>
                <li class="nav-item"><a class="nav-link<?= str_starts_with($currentPath, '/submit') ? ' active' : '' ?>" href="<?= e(base_url('/submit')) ?>">Submit Site</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle<?= str_starts_with($currentPath, '/pages') ? ' active' : '' ?>" href="#" id="pagesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Pages</a>
                    <ul class="dropdown-menu" aria-labelledby="pagesDropdown">
                        <li><a class="dropdown-item" href="<?= e(base_url('/pages/history-of-the-internet')) ?>">The History of the Internet</a></li>
                        <li><a class="dropdown-item" href="<?= e(base_url('/pages/bulletin-board-systems')) ?>">Bulletin Board Systems (BBS)</a></li>
                        <li><a class="dropdown-item" href="<?= e(base_url('/pages/ftp-archives-and-early-file-sharing')) ?>">FTP Archives and Early File Sharing</a></li>
                        <li><a class="dropdown-item" href="<?= e(base_url('/pages/web-directories-vs-search-engines')) ?>">Web Directories vs Search Engines</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= e(base_url('/pages/about')) ?>">About</a></li>
                        <li><a class="dropdown-item" href="<?= e(base_url('/pages/privacy-policy')) ?>">Privacy Policy</a></li>
                        <li><a class="dropdown-item" href="<?= e(base_url('/pages/terms')) ?>">Terms of Use</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link<?= str_starts_with($currentPath, '/editor') ? ' active' : '' ?>" href="<?= e(base_url('/editor')) ?>">Editor</a></li>
            </ul>
            <form class="d-flex" method="get" action="<?= e(base_url('/search')) ?>" role="search" aria-label="Search directory">
                <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Search directory" value="<?= e($headerSearchQuery) ?>" aria-label="Search directory">
                <button class="btn btn-outline-light btn-sm" type="submit">Search</button>
            </form>
        </div>
    </div>
</nav>

<main class="container my-4 flex-grow-1">
    <?php require __DIR__ . '/flash.php'; ?>
