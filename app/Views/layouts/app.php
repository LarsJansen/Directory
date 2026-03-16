<?php /** @var string $viewPath */ ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? config('name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="/"><?= e(config('name')) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="/submit">Suggest a Site</a></li>
                <?php if (is_editor_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="/editor">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/editor/categories">Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="/editor/sites">Sites</a></li>
                    <li class="nav-item"><a class="nav-link" href="/editor/submissions">Submissions</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/editor">Editor</a></li>
                <?php endif; ?>
            </ul>
            <form class="d-flex" action="/search" method="get">
                <input class="form-control me-2" type="search" name="q" placeholder="Search directory" value="<?= e($_GET['q'] ?? '') ?>">
                <button class="btn btn-outline-light" type="submit">Search</button>
            </form>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <?php if ($success = flash('success')): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error = flash('error')): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <?php require $viewPath; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
