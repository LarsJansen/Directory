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
                name="query"
                value="<?= e($query ?? '') ?>"
                placeholder="Title, URL, description..."
            >
        </div>

        <div class="col-md-3">
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

        <div class="col-md-3">
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
            <button type="submit" class="btn btn-primary w-100">Go</button>
        </div>
    </div>
</form>

<?php if (empty($sites ?? [])): ?>
    <div class="alert alert-info">
        No sites matched your filters.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
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
                            <div class="fw-semibold">
                                <?= e($site['title'] ?? '(Untitled)') ?>
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
                            <a href="/editor/sites/<?= (int) ($site['id'] ?? 0) ?>/edit" class="btn btn-sm btn-outline-primary">
                                Edit
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $paginationQuery = array_filter([
        'query' => $query ?? '',
        'status' => $status ?? '',
        'category_id' => ($categoryId ?? '') !== '' ? $categoryId : null,
    ], fn ($v) => $v !== null && $v !== '');
    require __DIR__ . '/../../layouts/pagination.php';
    ?>
<?php endif; ?>