<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Pending submissions</h1>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped table-tight mb-0">
                    <thead><tr><th>Submitted</th><th>Title</th><th>Category</th><th>URL</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$submissions): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No pending submissions.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= e($submission['created_at']) ?></td>
                            <td><?= e($submission['title']) ?></td>
                            <td><?= e($submission['category_path'] ?: 'Unassigned') ?></td>
                            <td class="small"><?= e($submission['url']) ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-primary" href="<?= e(base_url('/editor/submissions/' . $submission['id'])) ?>">Review</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
