<?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>

<?php $isWiby = stripos((string) ($submission['notes'] ?? ''), 'Auto-discovered via Wiby.') !== false; ?>

<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <h1 class="h3 mb-0">Review submission</h1>
    <?php if ($isWiby): ?>
        <span class="badge text-bg-info">Auto-discovered via Wiby</span>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Title</dt><dd class="col-sm-9"><?= e($submission['title']) ?></dd>
            <dt class="col-sm-3">URL</dt><dd class="col-sm-9"><a href="<?= e($submission['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($submission['url']) ?></a></dd>
            <dt class="col-sm-3">Normalized URL</dt><dd class="col-sm-9"><?= e($submission['normalized_url'] ?: 'Not set') ?></dd>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?= nl2br(e($submission['description'])) ?></dd>
            <dt class="col-sm-3">Suggested category</dt><dd class="col-sm-9"><?= e($submission['category_path'] ?: 'Unassigned') ?></dd>
            <dt class="col-sm-3">Submitter</dt><dd class="col-sm-9"><?= e($submission['submitter_name'] ?: 'Unknown') ?><?= $submission['submitter_email'] ? ' (' . e($submission['submitter_email']) . ')' : '' ?></dd>
            <?php if (!empty($submission['notes'])): ?>
                <dt class="col-sm-3">Notes</dt><dd class="col-sm-9"><pre class="mb-0 small bg-light border rounded p-3"><?= e($submission['notes']) ?></pre></dd>
            <?php endif; ?>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <form method="post" action="<?= e(base_url('/editor/submissions/' . $submission['id'] . '/approve')) ?>" class="row g-3">
                    <?= csrf_input() ?>
                    <div class="col-12">
                        <label class="form-label">Approve into category</label>
                        <select class="form-select" name="category_id">
                            <option value="">Choose category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= (int) $submission['proposed_category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['path']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success" type="submit">Approve</button>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <form method="post" action="<?= e(base_url('/editor/submissions/' . $submission['id'] . '/reject')) ?>" onsubmit="return confirm('Reject this submission?');">
                    <?= csrf_input() ?>
                    <button class="btn btn-outline-danger" type="submit">Reject</button>
                </form>
            </div>
        </div>
    </div>
</div>
