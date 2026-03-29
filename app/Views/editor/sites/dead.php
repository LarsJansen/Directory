<?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>

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

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <form method="post" action="<?= e(base_url('/editor/sites/dead/deactivate-all')) ?>" class="d-inline"
                  onsubmit="return confirm('Deactivate ALL dead sites? They will be hidden from the public directory.');">
                <?= csrf_input() ?>
                <button type="submit" class="btn btn-sm btn-outline-warning">Deactivate All Dead</button>
            </form>

            <form method="post" action="<?= e(base_url('/editor/sites/dead/delete-all')) ?>" class="d-inline"
                  onsubmit="return confirm('Delete ALL dead sites permanently? This cannot be undone.');">
                <?= csrf_input() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete All Dead</button>
            </form>

            <span class="small text-muted">Useful for clearing dead-site backlog quickly.</span>
        </div>

        <form method="post" action="<?= e(base_url('/editor/sites/bulk')) ?>" onsubmit="return confirmDeadBulkAction(this);">
            <?= csrf_input() ?>
            <input type="hidden" name="return_to" value="<?= e(page_url('/editor/sites/dead', array_filter(['q' => $query ?? '', 'page' => $pagination['page'] ?? 1], fn ($v) => $v !== null && $v !== ''))) ?>">

            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <select name="bulk_action" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Bulk action...</option>
                    <option value="deactivate">Deactivate selected</option>
                    <option value="reactivate">Reactivate selected</option>
                    <option value="delete">Delete selected</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">Apply</button>
            </div>

            <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th style="width: 38px;"><input type="checkbox" class="form-check-input" id="select-all-dead-sites"></th><th>Title</th><th>Category</th><th>URL</th><th>Latest Failure</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($sites)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No dead or failing sites found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sites as $site): ?>
                            <tr>
                                <td><input type="checkbox" class="form-check-input bulk-dead-site-checkbox" name="site_ids[]" value="<?= (int) $site['id'] ?>"></td>
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
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/editor/sites/' . $site['id'] . '/edit')) ?>">Review</a>
                                        <form method="post" action="<?= e(base_url('/editor/sites/' . $site['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this site permanently?');" class="d-inline">
                                            <?= csrf_input() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </form>

        <script>
        (function () {
            const selectAll = document.getElementById('select-all-dead-sites');
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    document.querySelectorAll('.bulk-dead-site-checkbox').forEach(function (checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                });
            }
        })();

        function confirmDeadBulkAction(form) {
            const action = form.querySelector('[name="bulk_action"]').value;
            if (!action) {
                alert('Choose a bulk action first.');
                return false;
            }

            if (action === 'delete') {
                return confirm('Delete the selected sites permanently?');
            }

            return true;
        }
        </script>

        <?php $path = '/editor/sites/dead'; $query = array_filter(['q' => $query], fn($v) => $v !== null && $v !== ''); require __DIR__ . '/../../layouts/pagination.php'; ?>
