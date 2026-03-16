<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1"><?= e($batch['batch_label']) ?></h1>
                <div class="text-muted small"><?= e($batch['source_name']) ?><?= $batch['source_version'] ? ' / ' . e($batch['source_version']) : '' ?> &middot; <?= e($batch['status']) ?></div>
            </div>
        </div>

        <div class="card mb-4"><div class="card-body">
            <h2 class="h5">Batch notes</h2>
            <p class="mb-0"><?= nl2br(e($batch['notes'] ?: 'No notes yet.')) ?></p>
        </div></div>

        <div class="alert alert-info">This is the manual-first staging review screen. In the next step you can wire a CLI or parser to load rows into <code>imported_site_staging</code>, then review mapping and duplicate status here.</div>

        <div class="card">
            <div class="card-body">
                <h2 class="h5">Staging rows</h2>
                <?php if (!$rows): ?>
                    <p class="text-muted mb-0">No staging rows loaded yet for this batch.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead><tr><th>Title</th><th>Raw category path</th><th>Mapped category</th><th>Status</th><th>Duplicate</th></tr></thead>
                            <tbody><?php foreach ($rows as $row): ?><tr><td><?= e($row['raw_title']) ?></td><td><code><?= e($row['raw_category_path']) ?></code></td><td><?= e($row['mapped_category_path'] ?: 'Not mapped') ?></td><td><?= e($row['import_status']) ?></td><td><?= $row['duplicate_site_id'] ? 'Yes (#' . (int) $row['duplicate_site_id'] . ')' : 'No' ?></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
