<?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <h1 class="h3 mb-0"><?= !empty($showWibyOnly) ? 'Pending Wiby submissions' : 'Pending submissions' ?></h1>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm <?= !empty($showWibyOnly) ? 'btn-outline-secondary' : 'btn-outline-primary' ?>" href="<?= e(base_url('/editor/submissions')) ?>">All pending</a>
        <a class="btn btn-sm <?= !empty($showWibyOnly) ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(base_url('/editor/submissions?source=wiby')) ?>">Wiby only</a>
        <a class="btn btn-sm btn-primary" href="<?= e(base_url('/editor/submissions/discover')) ?>">Discover from Wiby</a>
    </div>
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
            <table class="table table-striped table-tight mb-0 align-middle">
                <thead>
                <tr>
                    <th style="width: 40px;"><input class="form-check-input" type="checkbox" onclick="document.querySelectorAll('.submission-checkbox').forEach(cb => cb.checked = this.checked)"></th>
                    <th>Submitted</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Source</th>
                    <th>URL</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$submissions): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No pending submissions.</td></tr>
                <?php endif; ?>
                <?php foreach ($submissions as $submission): ?>
                    <?php $isWiby = stripos((string) ($submission['notes'] ?? ''), 'Auto-discovered via Wiby.') !== false; ?>
                    <tr>
                        <td><input class="form-check-input submission-checkbox" type="checkbox" name="submission_ids[]" value="<?= (int) $submission['id'] ?>"></td>
                        <td><?= e($submission['created_at']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e($submission['title']) ?></div>
                            <?php if ($isWiby): ?>
                                <span class="badge text-bg-info mt-1">Wiby</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($submission['category_path'] ?: 'Unassigned') ?></td>
                        <td><?= $isWiby ? 'Auto-discovered' : 'Manual' ?></td>
                        <td class="small"><a href="<?= e($submission['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($submission['url']) ?></a></td>
                        <td class="text-end"><a class="btn btn-sm btn-primary" href="<?= e(base_url('/editor/submissions/' . $submission['id'])) ?>">Review</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
