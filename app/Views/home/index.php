<div class="p-4 p-md-5 mb-4 bg-white border rounded-3">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h1 class="display-6">A curated web directory for the old-web spirit</h1>
            <p class="lead mb-0">Browse human-edited categories, discover useful sites, and grow the directory over time with editorial review and historical import support.</p>
        </div>
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="d-grid gap-2">
                <a class="btn btn-primary btn-lg" href="<?= e(base_url('/category')) ?>">Browse Categories</a>
                <a class="btn btn-outline-secondary" href="<?= e(base_url('/submit')) ?>">Submit a Site</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5">Top categories</h2>
                <div class="list-group list-group-flush mt-3">
                    <?php foreach ($categories as $category): ?>
                        <a class="list-group-item list-group-item-action px-0" href="<?= e(base_url('/category/' . $category['path'])) ?>">
                            <div class="d-flex justify-content-between">
                                <span><?= e($category['name']) ?></span>
                                <span class="text-muted small"><?= (int) $category['child_count'] ?> subcategories</span>
                            </div>
                            <div class="directory-muted small mt-1"><?= e($category['description'] ?? '') ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5">Recently added listings</h2>
                <div class="list-group list-group-flush mt-3">
                    <?php foreach ($latestSites as $site): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div><a href="<?= e($site['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($site['title']) ?></a></div>
                                    <div class="small text-muted"><?= e($site['category_path']) ?></div>
                                    <div class="small mt-1"><?= e($site['description']) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
