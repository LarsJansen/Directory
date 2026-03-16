<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php $isLast = $index === array_key_last($breadcrumbs); ?>
            <li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>" <?= $isLast ? 'aria-current="page"' : '' ?>>
                <?php if ($isLast): ?>
                    <?= e($crumb['name']) ?>
                <?php else: ?>
                    <a href="/category/<?= e($crumb['path']) ?>"><?= e($crumb['name']) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>

<div class="mb-4 d-flex justify-content-between align-items-start gap-3 flex-wrap">
    <div>
        <h1 class="h2 mb-2"><?= e($category['name']) ?></h1>
        <p class="text-muted mb-1"><?= e($category['description']) ?></p>
        <div class="small text-secondary">Path: <?= e($category['path']) ?></div>
    </div>
    <a class="btn btn-outline-primary" href="/submit">Suggest a site here</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Subcategories</div>
            <div class="list-group list-group-flush">
                <?php if (!$children): ?>
                    <div class="list-group-item text-muted">No subcategories yet.</div>
                <?php endif; ?>
                <?php foreach ($children as $child): ?>
                    <a class="list-group-item list-group-item-action" href="/category/<?= e($child['path']) ?>">
                        <div class="fw-semibold"><?= e($child['name']) ?></div>
                        <small class="text-muted d-block"><?= e($child['path']) ?></small>
                        <small class="text-muted"><?= e($child['description']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Listings</div>
            <div class="list-group list-group-flush">
                <?php if (!$sites): ?>
                    <div class="list-group-item text-muted">No live listings in this category yet.</div>
                <?php endif; ?>

                <?php foreach ($sites as $site): ?>
                    <div class="list-group-item">
                        <h2 class="h5 mb-1"><?= e($site['title']) ?></h2>
                        <div class="mb-2"><a href="<?= e($site['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($site['url']) ?></a></div>
                        <p class="mb-0 text-muted"><?= e($site['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
