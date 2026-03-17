<div class="row g-4">
    <div class="col-lg-3">
        <?php require __DIR__ . '/../layouts/editor_nav.php'; ?>
    </div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Editor dashboard</h1>
            <div class="small text-muted">Phase 4B editor power tools</div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Pending submissions</div><div class="display-6"><?= (int) $pendingCount ?></div></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Live sites</div><div class="display-6"><?= (int) $siteCount ?></div></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Categories</div><div class="display-6"><?= (int) $categoryCount ?></div></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Duplicate URL groups</div><div class="display-6"><?= (int) $duplicateCount ?></div></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Recent audit activity</h2>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/editor/audit')) ?>">View all</a>
                        </div>
                        <?php if (empty($recentAudit)): ?>
                            <p class="text-muted mb-0">No audit events yet.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentAudit as $row): ?>
                                    <div class="list-group-item px-0">
                                        <div class="small text-muted"><?= e($row['created_at']) ?><?= !empty($row['username']) ? ' · ' . e($row['username']) : '' ?></div>
                                        <div><strong><?= e($row['action']) ?></strong> <?= e($row['entity_type']) ?> #<?= (int) $row['entity_id'] ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Recently updated sites</h2>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/editor/sites')) ?>">Manage sites</a>
                        </div>
                        <?php if (empty($recentSites)): ?>
                            <p class="text-muted mb-0">No sites yet.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentSites as $site): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between gap-3">
                                            <div>
                                                <div><strong><?= e($site['title']) ?></strong></div>
                                                <div class="small text-muted"><code><?= e($site['category_path']) ?></code></div>
                                            </div>
                                            <div class="text-end small text-muted"><?= e($site['updated_at']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h2 class="h5">Included in this phase</h2>
                <p class="mb-0">Bulk moderation, duplicate URL review, richer site filters, and a browsable audit log. This phase is aimed at making the editor workflow feel much closer to real directory software.</p>
            </div>
        </div>
    </div>
</div>
