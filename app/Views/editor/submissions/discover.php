<?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>

<h1 class="h3 mb-3">Discover from Wiby</h1>

<div class="card mb-4">
    <div class="card-body">
        <p class="text-muted mb-3">Search Wiby for likely candidates, then add the results into your submissions queue for editorial review. Nothing is published automatically.</p>

        <form method="post" action="<?= e(base_url('/editor/submissions/discover')) ?>" class="row g-3 align-items-end">
            <?= csrf_input() ?>
            <div class="col-md-5">
                <label class="form-label">Wiby query</label>
                <input class="form-control" type="text" name="query" value="<?= e($defaults['query'] ?? '') ?>" placeholder="e.g. bbs, ftp archives, usenet">
            </div>
            <div class="col-md-2">
                <label class="form-label">Limit</label>
                <input class="form-control" type="number" name="limit" min="1" max="25" value="<?= (int) ($defaults['limit'] ?? 10) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">Target category</label>
                <select class="form-select" name="category_id">
                    <option value="">Choose category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (int) ($defaults['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['path']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Run discovery</button>
                <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/submissions')) ?>">Back to submissions</a>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($result)): ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                <h2 class="h5 mb-0">Last run</h2>
                <span class="badge <?= !empty($result['ok']) ? 'text-bg-success' : 'text-bg-danger' ?>"><?= !empty($result['ok']) ? 'Completed' : 'Failed' ?></span>
            </div>

            <p class="mb-3"><?= e($result['message'] ?? '') ?></p>

            <div class="row g-3 small">
                <div class="col-md-4"><strong>Query:</strong> <?= e($result['query'] ?? '') ?></div>
                <div class="col-md-4"><strong>Target category:</strong> <?= e($result['category']['path'] ?? '') ?></div>
                <div class="col-md-4"><strong>Wiby URL:</strong> <a href="<?= e($result['search_url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer">Open search</a></div>
                <div class="col-md-4"><strong>Profile:</strong> <?= e($result['profile']['name'] ?? 'General history') ?></div>
                <div class="col-md-3"><strong>Candidate URLs:</strong> <?= (int) ($result['candidate_count'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Inserted:</strong> <?= (int) ($result['inserted'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Skipped duplicates:</strong> <?= (int) ($result['skipped_duplicate'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Skipped fetch failures:</strong> <?= (int) ($result['skipped_fetch'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Skipped weak matches:</strong> <?= (int) ($result['skipped_weak'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Skipped secondary links:</strong> <?= (int) ($result['skipped_secondary'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Skipped file links:</strong> <?= (int) ($result['skipped_file_links'] ?? 0) ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h5 mb-3">Import log</h2>
            <?php if (empty($result['lines'])): ?>
                <p class="text-muted mb-0">No log lines captured.</p>
            <?php else: ?>
                <div class="small font-monospace" style="white-space: pre-wrap;">
                    <?php foreach ($result['lines'] as $line): ?>
                        <div class="mb-1 text-<?= ($line['type'] ?? '') === 'success' ? 'success' : (($line['type'] ?? '') === 'warning' ? 'warning' : 'danger') ?>"><?= e($line['message'] ?? '') ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
