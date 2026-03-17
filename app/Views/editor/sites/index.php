<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Manage sites</h1>
            <a class="btn btn-outline-primary" href="<?= e(base_url('/editor/sites/duplicates')) ?>">Duplicate URLs</a>
        </div>
        <form class="card card-body mb-3" method="get" action="<?= e(base_url('/editor/sites')) ?>">
            <div class="row g-2">
                <div class="col-md-5"><input class="form-control" type="search" name="q" value="<?= e($query) ?>" placeholder="Search by title, URL, normalized URL, or category path"></div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Any status</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Status: active</option>
                        <option value="dead" <?= $status === 'dead' ? 'selected' : '' ?>>Status: dead</option>
                        <option value="flagged" <?= $status === 'flagged' ? 'selected' : '' ?>>Status: flagged</option>
                        <option value="active_only" <?= $status === 'active_only' ? 'selected' : '' ?>>Active listings only</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive listings only</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="category_id">
                        <option value="0">All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= (int) $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['path']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-grid"><button class="btn btn-outline-primary" type="submit">Go</button></div>
            </div>
        </form>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped table-tight mb-0">
                    <thead><tr><th>Title</th><th>Category</th><th>URL</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($sites as $site): ?>
                        <tr>
                            <td><?= e($site['title']) ?></td>
                            <td><code><?= e($site['category_path']) ?></code></td>
                            <td class="small"><?= e($site['url']) ?></td>
                            <td><?= e($site['status']) ?><?= (int) $site['is_active'] === 0 ? ' / inactive' : '' ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/editor/sites/' . $site['id'] . '/edit')) ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php $path = '/editor/sites'; $query = array_filter(['q' => $query, 'status' => $status, 'category_id' => $categoryId ?: null], fn($v) => $v !== null && $v !== ''); require __DIR__ . '/../../layouts/pagination.php'; ?>
    </div>
</div>
