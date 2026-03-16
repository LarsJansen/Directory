<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Pending Submissions</h1>
        <p class="text-muted mb-0">Incoming site suggestions waiting for editor review.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/editor">Back to dashboard</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$submissions): ?>
                    <tr><td colspan="5" class="text-muted">No pending submissions.</td></tr>
                <?php endif; ?>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?= (int) $submission['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= e($submission['title']) ?></div>
                            <small class="text-muted d-block"><?= e($submission['url']) ?></small>
                        </td>
                        <td><?= e($submission['category_path'] ?: 'Unassigned') ?></td>
                        <td><?= e($submission['created_at']) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-primary" href="/editor/submissions/<?= (int) $submission['id'] ?>">Review</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
