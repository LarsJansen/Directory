<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Manage Categories</h1>
        <p class="text-muted mb-0">Phase 2 adds path-based category routing and editor CRUD.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="/editor/categories/create">Create category</a>
        <a class="btn btn-outline-secondary" href="/editor">Back to dashboard</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Path</th>
                    <th>Parent</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($category['name']) ?></div>
                            <small class="text-muted">Sort order: <?= (int) $category['sort_order'] ?></small>
                        </td>
                        <td><code><?= e($category['path']) ?></code></td>
                        <td><?= e($category['parent_name'] ?: 'Top level') ?></td>
                        <td>
                            <span class="badge <?= $category['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= $category['is_active'] ? 'Active' : 'Hidden' ?>
                            </span>
                        </td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/editor/categories/<?= (int) $category['id'] ?>/edit">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
