<?php
$page = $page ?? [];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= e(base_url('/')) ?>">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($page['title'] ?? 'Page') ?></li>
    </ol>
</nav>

<article class="card shadow-sm">
    <div class="card-body p-4 p-lg-5">
        <header class="mb-4">
            <h1 class="h2 mb-2"><?= e($page['title'] ?? 'Page') ?></h1>
            <?php if (!empty($page['lead'])): ?>
                <p class="text-muted mb-0"><?= e($page['lead']) ?></p>
            <?php endif; ?>
        </header>

        <div class="page-content">
            <?php require __DIR__ . '/content/' . $page['view'] . '.php'; ?>
        </div>
    </div>
</article>
