<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Merge category</h1>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/edit')) ?>">Back to edit</a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small text-uppercase mb-1">Source category</div>
                        <code><?= e($category['path']) ?></code>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small text-uppercase mb-1">Direct children</div>
                        <div><?= (int) ($summary['direct_child_count'] ?? 0) ?></div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small text-uppercase mb-1">Descendants</div>
                        <div><?= (int) ($summary['descendant_count'] ?? 0) ?></div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small text-uppercase mb-1">Direct sites</div>
                        <div><?= (int) ($summary['direct_site_count'] ?? 0) ?></div>
                    </div>
                </div>
                <p class="text-muted small mt-3 mb-0">Merging moves directly attached sites into the target category, reparents direct child categories into the target category, rebuilds descendant paths for those moved child branches, and then deletes the source category. This action is intentionally conservative and will be blocked if child path collisions would occur in the target branch.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="get" action="<?= e(base_url('/editor/categories/' . $category['id'] . '/merge')) ?>" class="row g-3 mb-4">
                    <div class="col-md-9">
                        <label class="form-label" for="preview_target_id">Target category</label>
                        <select class="form-select" name="target_id" id="preview_target_id">
                            <option value="">Select target category</option>
                            <?php foreach ($targets as $target): ?>
                                <option value="<?= (int) $target['id'] ?>" <?= $selectedTargetId !== null && (int) $selectedTargetId === (int) $target['id'] ? 'selected' : '' ?>><?= e($target['path']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-outline-primary w-100" type="submit">Preview merge</button>
                    </div>
                </form>

                <?php if (!empty($preview)): ?>
                    <div class="alert alert-warning">
                        <div class="fw-semibold mb-2">Merge preview</div>
                        <div><span class="text-muted">Source:</span> <code><?= e($preview['source']['path']) ?></code></div>
                        <div><span class="text-muted">Target:</span> <code><?= e($preview['target']['path']) ?></code></div>
                        <div><span class="text-muted">Direct child categories moved:</span> <?= (int) ($preview['summary']['direct_child_count'] ?? 0) ?></div>
                        <div><span class="text-muted">Direct sites moved:</span> <?= (int) ($preview['summary']['direct_site_count'] ?? 0) ?></div>
                        <div><span class="text-muted">Sites in source branch:</span> <?= (int) ($preview['summary']['site_count_in_branch'] ?? 0) ?></div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('/editor/categories/' . $category['id'] . '/merge')) ?>" class="row g-3">
                    <?= csrf_input() ?>
                    <div class="col-md-9">
                        <label class="form-label" for="target_id">Confirm target category</label>
                        <select class="form-select" name="target_id" id="target_id" required>
                            <option value="">Select target category</option>
                            <?php foreach ($targets as $target): ?>
                                <option value="<?= (int) $target['id'] ?>" <?= $selectedTargetId !== null && (int) $selectedTargetId === (int) $target['id'] ? 'selected' : '' ?>><?= e($target['path']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-dark w-100" type="submit">Merge category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
