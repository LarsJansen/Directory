<?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Manage Categories</h1>
        <p class="text-muted mb-0">Search and maintain the live directory taxonomy.</p>
    </div>

    <div>
        <a href="<?= e(base_url('/editor/categories/create')) ?>" class="btn btn-primary btn-sm">New Category</a>
    </div>
</div>

<form method="get" action="<?= e(base_url('/editor/categories')) ?>" class="card card-body mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-10">
            <label for="q" class="form-label">Search</label>
            <input
                type="text"
                class="form-control"
                id="q"
                name="q"
                value="<?= e($query ?? '') ?>"
                placeholder="Path, category name, parent name..."
            >
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Go</button>
        </div>
    </div>
</form>

<div class="d-flex justify-content-between align-items-center mb-3 small text-muted">
    <div>
        <?= (int) ($total ?? 0) ?> categor<?= ((int) ($total ?? 0) === 1) ? 'y' : 'ies' ?> found
        <?php if (($query ?? '') !== ''): ?>
            for <span class="fw-semibold text-dark"><?= e($query) ?></span>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($categories ?? [])): ?>
    <div class="alert alert-info">No categories matched your search.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Path</th>
                    <th>Parent</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($categories ?? []) as $category): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e(display_name($category['name'] ?? '')) ?></div>
                            <?php if (!empty($category['description'])): ?>
                                <div class="small text-muted"><?= e(mb_strimwidth((string) $category['description'], 0, 120, '...')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= e(base_url('/category/' . ($category['path'] ?? ''))) ?>" target="_blank" class="text-decoration-none">
                                <?= e($category['path'] ?? '') ?>
                            </a>
                        </td>
                        <td>
                            <?php if (!empty($category['parent_name'])): ?>
                                <?= e(display_name($category['parent_name'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Top level</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) ($category['is_active'] ?? 0) === 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="<?= e(base_url('/editor/categories/' . (int) ($category['id'] ?? 0) . '/edit')) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="<?= e(base_url('/editor/categories/' . (int) ($category['id'] ?? 0) . '/move')) ?>" class="btn btn-sm btn-outline-secondary">Move</a>
                            <a href="<?= e(base_url('/editor/categories/' . (int) ($category['id'] ?? 0) . '/merge')) ?>" class="btn btn-sm btn-outline-warning">Merge</a>
                            <a href="<?= e(base_url('/editor/categories/' . (int) ($category['id'] ?? 0) . '/delete')) ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $path = '/editor/categories';
    $paginationQuery = array_filter([
        'q' => $query ?? '',
    ], fn ($v) => $v !== null && $v !== '');
    require __DIR__ . '/../../layouts/pagination.php';
    ?>
<?php endif; ?>
