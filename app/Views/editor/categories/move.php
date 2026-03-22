<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Move category branch</h1>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/edit')) ?>">Back to edit</a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small text-uppercase mb-1">Current path</div>
                        <code><?= e($category['path']) ?></code>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase mb-1">Descendants</div>
                        <div><?= (int) ($branchSummary['descendant_count'] ?? 0) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase mb-1">Sites in branch</div>
                        <div><?= (int) ($branchSummary['site_count_in_branch'] ?? 0) ?></div>
                    </div>
                </div>
                <p class="text-muted small mt-3 mb-0">This moves the selected category and all descendant categories with it. Sites remain attached to their existing category records and move with the branch automatically.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="get" action="<?= e(base_url('/editor/categories/' . $category['id'] . '/move')) ?>" class="row g-3 mb-4">
                    <div class="col-md-9">
                        <label class="form-label" for="preview_parent_id">New parent category</label>
                        <select class="form-select" name="parent_id" id="preview_parent_id">
                            <option value="" <?= $selectedParentId === null ? 'selected' : '' ?>>Top level</option>
                            <?php foreach ($targets as $target): ?>
                                <option value="<?= (int) $target['id'] ?>" <?= $selectedParentId !== null && (int) $selectedParentId === (int) $target['id'] ? 'selected' : '' ?>><?= e($target['path']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-outline-primary w-100" type="submit">Preview move</button>
                    </div>
                </form>

                <?php if (!empty($preview)): ?>
                    <div class="alert alert-info">
                        <div class="fw-semibold mb-2">Move preview</div>
                        <div><span class="text-muted">Old path:</span> <code><?= e($preview['old_path']) ?></code></div>
                        <div><span class="text-muted">New path:</span> <code><?= e($preview['new_path']) ?></code></div>
                        <div><span class="text-muted">New parent:</span> <?= !empty($preview['new_parent']) ? e($preview['new_parent']['path']) : 'Top level' ?></div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('/editor/categories/' . $category['id'] . '/move')) ?>" class="row g-3">
                    <?= csrf_input() ?>
                    <div class="col-md-9">
                        <label class="form-label" for="parent_id">Confirm new parent</label>
                        <select class="form-select" name="parent_id" id="parent_id">
                            <option value="" <?= $selectedParentId === null ? 'selected' : '' ?>>Top level</option>
                            <?php foreach ($targets as $target): ?>
                                <option value="<?= (int) $target['id'] ?>" <?= $selectedParentId !== null && (int) $selectedParentId === (int) $target['id'] ? 'selected' : '' ?>><?= e($target['path']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit">Move branch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
