<div class="p-5 mb-4 bg-light rounded-3 border">
    <div class="container-fluid py-3">
        <h1 class="display-6 fw-bold">Human-curated web directory</h1>
        <p class="col-md-9 fs-5 mb-4">
            A small DMOZ-style MVP with curated categories, editor review, category paths, duplicate URL checks, and room for future legacy imports and spider checks.
        </p>
        <a class="btn btn-primary btn-lg" href="/submit">Suggest a site</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold">Top Categories</div>
            <div class="list-group list-group-flush">
                <?php foreach ($categories as $category): ?>
                    <a class="list-group-item list-group-item-action" href="/category/<?= e($category['path']) ?>">
                        <div class="fw-semibold"><?= e($category['name']) ?></div>
                        <small class="text-muted d-block"><?= e($category['path']) ?></small>
                        <small class="text-muted"><?= e($category['description']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-semibold">Recently Added Listings</div>
            <div class="list-group list-group-flush">
                <?php foreach ($latestSites as $site): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold"><?= e($site['title']) ?></div>
                                <div><a href="<?= e($site['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($site['url']) ?></a></div>
                                <small class="text-muted"><?= e($site['description']) ?></small>
                            </div>
                            <a class="badge text-bg-secondary text-decoration-none" href="/category/<?= e($site['category_path']) ?>">
                                <?= e($site['category_name']) ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
