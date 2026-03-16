<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Manage Sites</h1>
        <p class="text-muted mb-0">Edit listings, move them between categories, and flag dead or duplicate entries.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/editor">Back to dashboard</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sites as $site): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($site['title']) ?></div>
                            <small class="text-muted d-block"><?= e($site['url']) ?></small>
                        </td>
                        <td><code><?= e($site['category_path']) ?></code></td>
                        <td><span class="badge text-bg-<?= $site['status'] === 'active' ? 'success' : ($site['status'] === 'flagged' ? 'warning' : 'secondary') ?>"><?= e($site['status']) ?></span></td>
                        <td><?= e($site['source_type']) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/editor/sites/<?= (int) $site['id'] ?>/edit">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
