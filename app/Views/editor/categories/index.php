<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">Manage Categories</h1>
        <p class="text-muted mb-0">Safer category editing with path visibility, parent tracking, and descendant-safe moves.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(base_url('/editor/categories/create')) ?>">Create Category</a>
</div>

<?php if (file_exists(__DIR__ . '/../../layouts/editor_nav.php')): ?>
    <?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Path</th>
                    <th>Parent</th>
                    <th class="text-center">Children</th>
                    <th class="text-center">Sites</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($category['name']) ?></div>
                            <?php if (!empty($category['description'])): ?>
                                <div class="small text-muted text-truncate" style="max-width: 320px;"><?= e($category['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><code><?= e($category['path']) ?></code></td>
                        <td><?= e($category['parent_name'] ?? 'Root') ?></td>
                        <td class="text-center"><?= (int) $category['child_count'] ?></td>
                        <td class="text-center"><?= (int) $category['site_count'] ?></td>
                        <td class="text-center">
                            <?php if ((int) $category['is_active'] === 1): ?>
                                <span class="badge text-bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/edit')) ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
