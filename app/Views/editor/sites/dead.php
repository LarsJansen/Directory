<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Dead site queue</h1>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/sites/checks')) ?>">All Site Checks</a>
        </div>

        <form class="card card-body mb-3" method="get" action="<?= e(base_url('/editor/sites/dead')) ?>">
            <div class="row g-2">
                <div class="col-md-11"><input class="form-control" type="search" name="q" value="<?= e($query) ?>" placeholder="Search dead or failing sites"></div>
                <div class="col-md-1 d-grid"><button class="btn btn-outline-primary" type="submit">Go</button></div>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Title</th><th>Category</th><th>URL</th><th>Latest Failure</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($sites)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No dead or failing sites found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sites as $site): ?>
                            <tr>
                                <td><?= e($site['title']) ?></td>
                                <td><code><?= e($site['category_path']) ?></code></td>
                                <td class="small"><?= e($site['url']) ?></td>
                                <td class="small">
                                    <div>
                                        <span class="badge text-bg-danger"><?= e((string) ($site['latest_check_status'] ?? 'dead')) ?></span>
                                        <?= e((string) ($site['latest_http_status'] ?? '')) ?>
                                    </div>
                                    <?php if (!empty($site['latest_check_error'])): ?>
                                        <div class="text-muted"><?= e($site['latest_check_error']) ?></div>
                                    <?php endif; ?>
                                    <div class="text-muted"><?= e((string) ($site['latest_checked_at'] ?? '')) ?></div>
                                </td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/editor/sites/' . $site['id'] . '/edit')) ?>">Review</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php $path = '/editor/sites/dead'; $query = array_filter(['q' => $query], fn($v) => $v !== null && $v !== ''); require __DIR__ . '/../../layouts/pagination.php'; ?>
    </div>
</div>
