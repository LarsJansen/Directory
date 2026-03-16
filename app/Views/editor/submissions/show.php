<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Review Submission #<?= (int) $submission['id'] ?></h1>
        <p class="text-muted mb-0">Moderate this site suggestion.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/editor/submissions">Back to queue</a>
</div>

<?php if ($duplicates): ?>
    <div class="alert alert-warning">
        <div class="fw-semibold mb-2">Possible duplicate listing found</div>
        <?php foreach ($duplicates as $duplicate): ?>
            <div>
                <a href="/editor/sites/<?= (int) $duplicate['id'] ?>/edit">#<?= (int) $duplicate['id'] ?> <?= e($duplicate['title']) ?></a>
                <span class="text-muted">in <?= e($duplicate['category_path']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Title</dt>
            <dd class="col-sm-9"><?= e($submission['title']) ?></dd>

            <dt class="col-sm-3">URL</dt>
            <dd class="col-sm-9"><a href="<?= e($submission['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($submission['url']) ?></a></dd>

            <dt class="col-sm-3">Category</dt>
            <dd class="col-sm-9"><?= e($submission['category_path'] ?: 'Unassigned') ?></dd>

            <dt class="col-sm-3">Submitter</dt>
            <dd class="col-sm-9"><?= e($submission['submitter_name'] ?: 'Anonymous') ?><?= $submission['submitter_email'] ? ' (' . e($submission['submitter_email']) . ')' : '' ?></dd>

            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9"><?= nl2br(e($submission['description'])) ?></dd>

            <dt class="col-sm-3">Notes</dt>
            <dd class="col-sm-9"><?= $submission['notes'] ? nl2br(e($submission['notes'])) : '<span class="text-muted">None</span>' ?></dd>
        </dl>
    </div>
</div>

<div class="d-flex gap-2 flex-wrap">
    <form action="/editor/submissions/<?= (int) $submission['id'] ?>/approve" method="post">
        <button class="btn btn-success" <?= $duplicates ? 'disabled' : '' ?>>Approve and publish</button>
    </form>
    <form action="/editor/submissions/<?= (int) $submission['id'] ?>/reject" method="post">
        <button class="btn btn-outline-danger">Reject</button>
    </form>
</div>
