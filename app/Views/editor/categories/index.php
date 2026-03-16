<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Manage categories</h1>
            <a class="btn btn-primary" href="<?= e(base_url('/editor/categories/create')) ?>">Create category</a>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped table-tight mb-0">
                    <thead><tr><th>Path</th><th>Name</th><th>Parent</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><code><?= e($category['path']) ?></code></td>
                            <td><?= e($category['name']) ?></td>
                            <td><?= e($category['parent_name'] ?? 'Top level') ?></td>
                            <td><?= (int) $category['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/edit')) ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
