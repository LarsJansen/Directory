<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Manage categories</h1>
            <a class="btn btn-primary" href="<?= e(base_url('/editor/categories/create')) ?>">Create category</a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="get" action="<?= e(base_url('/editor/categories')) ?>" class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label for="q" class="form-label mb-1">Search categories</label>
                        <input
                            type="search"
                            class="form-control"
                            id="q"
                            name="q"
                            value="<?= e($query ?? '') ?>"
                            placeholder="Search by path, name, or parent"
                        >
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">Search</button>
                        <?php if (!empty($query)): ?>
                            <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/categories')) ?>">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php
        $pagination = $pagination ?? null;
        $total = (int) ($total ?? 0);
        $currentPage = $pagination['page'] ?? 1;
        $perPage = $pagination['per_page'] ?? max(1, count($categories ?? []));
        $from = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $to = $total > 0 ? min($total, ($currentPage - 1) * $perPage + count($categories)) : 0;
        ?>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted small">
                <?php if ($total > 0): ?>
                    Showing <?= $from ?>-<?= $to ?> of <?= $total ?> categor<?= $total === 1 ? 'y' : 'ies' ?>
                <?php else: ?>
                    No categories found.
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped table-tight mb-0">
                    <thead><tr><th>Path</th><th>Name</th><th>Parent</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><code><?= e($category['path']) ?></code></td>
                                <td><?= e($category['name']) ?></td>
                                <td><?= e($category['parent_name'] ?? 'Top level') ?></td>
                                <td><?= (int) $category['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Category actions">
                                        <a class="btn btn-outline-primary" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/edit')) ?>">Edit</a>
                                        <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/move')) ?>">Move</a>
                                        <a class="btn btn-outline-danger" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/delete')) ?>">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No categories found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
        $path = '/editor/categories';
        $query = !empty($query) ? ['q' => $query] : [];
        require __DIR__ . '/../../layouts/pagination.php';
        ?>
    </div>
</div>
