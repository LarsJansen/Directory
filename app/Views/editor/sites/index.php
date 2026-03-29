<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Manage Sites</h1>
        <p class="text-muted mb-0">Search, filter, and maintain live directory listings.</p>
    </div>

    <div>
        <a href="/editor/sites/duplicates" class="btn btn-outline-secondary btn-sm">Duplicate URLs</a>
    </div>
</div>

<form method="get" action="/editor/sites" class="card card-body mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label for="query" class="form-label">Search</label>
            <input
                type="text"
                class="form-control"
                id="query"
                name="q"
                value="<?= e($query ?? '') ?>"
                placeholder="Title, URL, description..."
            >
        </div>

        <div class="col-md-2">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Any status</option>
                <option value="active" <?= (($status ?? '') === 'active') ? 'selected' : '' ?>>Status: active</option>
                <option value="dead" <?= (($status ?? '') === 'dead') ? 'selected' : '' ?>>Status: dead</option>
                <option value="flagged" <?= (($status ?? '') === 'flagged') ? 'selected' : '' ?>>Status: flagged</option>
                <option value="active_only" <?= (($status ?? '') === 'active_only') ? 'selected' : '' ?>>Active listings only</option>
                <option value="inactive_only" <?= (($status ?? '') === 'inactive_only') ? 'selected' : '' ?>>Inactive listings only</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="category_id" class="form-label">Category</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">All categories</option>
                <?php foreach (($categories ?? []) as $category): ?>
                    <option
                        value="<?= (int) ($category['id'] ?? 0) ?>"
                        <?= ((string) ($categoryId ?? '') === (string) ($category['id'] ?? '')) ? 'selected' : '' ?>
                    >
                        <?= e($category['path'] ?? $category['name'] ?? 'Unknown') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="sort" class="form-label">Sort</label>
            <select class="form-select" id="sort" name="sort">
                <option value="recent_checks" <?= (($sort ?? 'recent_checks') === 'recent_checks') ? 'selected' : '' ?>>Recent checks</option>
                <option value="featured_first" <?= (($sort ?? '') === 'featured_first') ? 'selected' : '' ?>>Featured first</option>
                <option value="featured_only" <?= (($sort ?? '') === 'featured_only') ? 'selected' : '' ?>>Featured only</option>
                <option value="updated_desc" <?= (($sort ?? '') === 'updated_desc') ? 'selected' : '' ?>>Recently updated</option>
                <option value="title_asc" <?= (($sort ?? '') === 'title_asc') ? 'selected' : '' ?>>Title A–Z</option>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Go</button>
        </div>
    </div>
</form>

<?php if (empty($sites ?? [])): ?>
    <div class="alert alert-info">
        No sites matched your filters.
    </div>
<?php else: ?>
    <form method="post" action="<?= e(base_url('/editor/sites/bulk')) ?>" onsubmit="return confirmBulkAction(this);">
        <?= csrf_input() ?>
        <input type="hidden" name="return_to" value="<?= e(page_url('/editor/sites', array_filter([
            'q' => $query ?? '',
            'status' => $status ?? '',
            'check' => $check ?? '',
            'category_id' => ($categoryId ?? 0) > 0 ? $categoryId : null,
            'sort' => $sort ?? 'recent_checks',
            'page' => $pagination['page'] ?? 1,
        ], fn ($v) => $v !== null && $v !== ''))) ?>">
        <input type="hidden" name="filter_q" value="<?= e($query ?? '') ?>">
        <input type="hidden" name="filter_status" value="<?= e($status ?? '') ?>">
        <input type="hidden" name="filter_check" value="<?= e($check ?? '') ?>">
        <input type="hidden" name="filter_category_id" value="<?= (int) ($categoryId ?? 0) ?>">
        <input type="hidden" name="filter_sort" value="<?= e($sort ?? 'recent_checks') ?>">

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <select name="bulk_action" class="form-select form-select-sm" style="width:auto;">
                <option value="">Bulk action...</option>
                <option value="deactivate">Deactivate selected</option>
                <option value="reactivate">Reactivate selected</option>
                <option value="delete">Delete selected</option>
                <option value="deactivate_flagged_filtered">Deactivate all flagged in current results</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Apply</button>
            <span class="small text-muted">Use the checkboxes to update many sites at once.</span>
        </div>

        <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 38px;">
                        <input type="checkbox" class="form-check-input" id="select-all-sites">
                    </th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>URL</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($sites ?? []) as $site): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input bulk-site-checkbox" name="site_ids[]" value="<?= (int) ($site['id'] ?? 0) ?>">
                        </td>
                        <td>
                            <div class="fw-semibold d-flex align-items-center gap-2 flex-wrap">
                                <span><?= e($site['title'] ?? '(Untitled)') ?></span>
                                <?php if ((int) ($site['is_featured'] ?? 0) === 1): ?>
                                    <span class="badge bg-warning text-dark">Featured</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($site['description'])): ?>
                                <div class="small text-muted">
                                    <?= e(mb_strimwidth((string) $site['description'], 0, 120, '...')) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!empty($site['category_path'])): ?>
                                <a href="/category/<?= e($site['category_path']) ?>" target="_blank" class="text-decoration-none">
                                    <?= e($site['category_path']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!empty($site['url'])): ?>
                                <a href="<?= e($site['url']) ?>" target="_blank" rel="noopener noreferrer">
                                    <?= e($site['url']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">No URL</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php
                            $isActive = (int) ($site['is_active'] ?? 0);
                            $latestStatus = strtolower((string) ($site['latest_status'] ?? ''));
                            $badgeClass = $isActive ? 'bg-success' : 'bg-secondary';
                            $badgeText = $isActive ? 'Active' : 'Inactive';

                            if ($latestStatus === 'dead') {
                                $badgeClass = 'bg-danger';
                                $badgeText = 'Dead';
                            } elseif ($latestStatus === 'flagged') {
                                $badgeClass = 'bg-warning text-dark';
                                $badgeText = 'Flagged';
                            }
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= e($badgeText) ?>
                            </span>

                            <?php if (!empty($site['checked_at'])): ?>
                                <div class="small text-muted mt-1">
                                    Checked: <?= e($site['checked_at']) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <a href="/editor/sites/<?= (int) ($site['id'] ?? 0) ?>/edit" class="btn btn-sm btn-outline-primary">
                                    Edit
                                </a>
                                <form method="post" action="/editor/sites/<?= (int) ($site['id'] ?? 0) ?>/delete" onsubmit="return confirm('Delete this site permanently?');" class="d-inline">
                                    <?= csrf_input() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </form>

    <script>
    (function () {
        const selectAll = document.getElementById('select-all-sites');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.bulk-site-checkbox').forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
            });
        }
    })();

    function confirmBulkAction(form) {
        const action = form.querySelector('[name="bulk_action"]').value;
        if (!action) {
            alert('Choose a bulk action first.');
            return false;
        }

        if (action === 'delete') {
            return confirm('Delete the selected sites permanently?');
        }

        if (action === 'deactivate_flagged_filtered') {
            return confirm('Deactivate all flagged sites in the current filtered results?');
        }

        return true;
    }
    </script>

    <?php
    $path = '/editor/sites';
    $paginationQuery = array_filter([
        'query' => $query ?? '',
        'status' => $status ?? '',
        'category_id' => ($categoryId ?? '') !== '' ? $categoryId : null,
        'sort' => $sort ?? 'recent_checks',
    ], fn ($v) => $v !== null && $v !== '');
    require __DIR__ . '/../../layouts/pagination.php';
    ?>
<?php endif; ?>