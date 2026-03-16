<div class="row g-4">
    <div class="col-lg-3">
        <?php require __DIR__ . '/../layouts/editor_nav.php'; ?>
    </div>
    <div class="col-lg-9">
        <h1 class="h3 mb-4">Editor dashboard</h1>
        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="card"><div class="card-body"><div class="text-muted small">Pending submissions</div><div class="display-6"><?= (int) $pendingCount ?></div></div></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card"><div class="card-body"><div class="text-muted small">Live sites</div><div class="display-6"><?= (int) $siteCount ?></div></div></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card"><div class="card-body"><div class="text-muted small">Categories</div><div class="display-6"><?= (int) $categoryCount ?></div></div></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card"><div class="card-body"><div class="text-muted small">Import batches</div><div class="display-6"><?= (int) $importBatchCount ?></div></div></div>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <h2 class="h5">Phase 3 status</h2>
                <p class="mb-0">This bundle adds pagination, split header/footer partials, stronger editor tools, public search, and a first import-batch UI skeleton.</p>
            </div>
        </div>
    </div>
</div>
