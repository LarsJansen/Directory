<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <h1 class="h3 mb-3">Edit site</h1>

        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h5">Maintenance status</h2>
                <?php if (empty($latestCheck)): ?>
                    <p class="text-muted mb-2">This site has not been checked yet.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="small text-muted">Latest result</div>
                            <span class="badge text-bg-<?= $latestCheck['result_status'] === 'ok' ? 'success' : ($latestCheck['result_status'] === 'warn' ? 'warning' : 'danger') ?>">
                                <?= e($latestCheck['result_status']) ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">HTTP status</div>
                            <div><?= e((string) ($latestCheck['http_status'] ?? '')) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Checked at</div>
                            <div><?= e($latestCheck['checked_at']) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Response time</div>
                            <div><?= e((string) ($latestCheck['response_time_ms'] ?? '')) ?> ms</div>
                        </div>
                    </div>
                    <?php if (!empty($latestCheck['redirect_url'])): ?>
                        <div class="mt-3 small"><strong>Redirect:</strong> <?= e($latestCheck['redirect_url']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($latestCheck['error_message'])): ?>
                        <div class="mt-2 small text-danger"><?= e($latestCheck['error_message']) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="mt-3"><a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('/editor/sites/checks')) ?>">View all checks</a></div>
            </div>
        </div>

        <div class="card"><div class="card-body">
            <form method="post" action="<?= e(base_url('/editor/sites/' . $site['id'] . '/update')) ?>" class="row g-3">
                <?= csrf_input() ?>
                <div class="col-md-8"><label class="form-label">Title</label><input class="form-control" type="text" name="title" value="<?= e($site['title']) ?>" required></div>
                <div class="col-md-4"><label class="form-label">Slug</label><input class="form-control" type="text" name="slug" value="<?= e($site['slug']) ?>"></div>
                <div class="col-md-8"><label class="form-label">Category</label><select class="form-select" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $site['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['path']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?= $site['status'] === 'active' ? 'selected' : '' ?>>active</option><option value="dead" <?= $site['status'] === 'dead' ? 'selected' : '' ?>>dead</option><option value="flagged" <?= $site['status'] === 'flagged' ? 'selected' : '' ?>>flagged</option></select></div>
                <div class="col-12"><label class="form-label">URL</label><input class="form-control" type="text" name="url" value="<?= e($site['url']) ?>" required></div>
                <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="5" required><?= e($site['description']) ?></textarea></div>
                <div class="col-md-4"><label class="form-label">Original title</label><input class="form-control" type="text" name="original_title" value="<?= e($site['original_title']) ?>"></div>
                <div class="col-md-4"><label class="form-label">Original URL</label><input class="form-control" type="text" name="original_url" value="<?= e($site['original_url']) ?>"></div>
                <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= (int) $site['is_active'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="is_active">Active listing</label></div></div>
                <div class="col-12"><label class="form-label">Original description</label><textarea class="form-control" name="original_description" rows="4"><?= e($site['original_description']) ?></textarea></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Save changes</button></div>
            </form>
        </div></div>
    </div>
</div>
