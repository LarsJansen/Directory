<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Browse categories</h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/search')) ?>">Search listings</a>
</div>

<div class="row g-4">
    <?php foreach ($categories as $category): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 site-card">
                <div class="card-body">
                    <h2 class="h5"><a href="<?= e(base_url('/category/' . $category['path'])) ?>"><?= e($category['name']) ?></a></h2>
                    <p class="small text-muted mb-2"><?= e($category['path']) ?></p>
                    <p class="mb-3"><?= e($category['description'] ?? '') ?></p>
                    <div class="small text-muted"><?= (int) $category['child_count'] ?> subcategories &middot; <?= (int) $category['site_count'] ?> sites</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
