<?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Pending submissions</h1>
        </div>

        <form method="post" action="<?= e(base_url('/editor/submissions/bulk')) ?>">
            <?= csrf_input() ?>
            <div class="card mb-3">
                <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                    <select class="form-select" name="bulk_action" style="max-width: 240px;">
                        <option value="">Bulk action…</option>
                        <option value="approve">Approve selected</option>
                        <option value="reject">Reject selected</option>
                    </select>
                    <button class="btn btn-primary" type="submit">Apply</button>
                    <div class="small text-muted">Bulk approve uses each submission's proposed category and skips rows with no category or duplicate normalized URLs.</div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-striped table-tight mb-0">
                        <thead><tr><th style="width: 40px;"><input class="form-check-input" type="checkbox" onclick="document.querySelectorAll('.submission-checkbox').forEach(cb => cb.checked = this.checked)"></th><th>Submitted</th><th>Title</th><th>Category</th><th>URL</th><th></th></tr></thead>
                        <tbody>
                        <?php if (!$submissions): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No pending submissions.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><input class="form-check-input submission-checkbox" type="checkbox" name="submission_ids[]" value="<?= (int) $submission['id'] ?>"></td>
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
        </form>
