<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Audit log</h1>
        </div>

        <form class="card card-body mb-3" method="get" action="<?= e(base_url('/editor/audit')) ?>">
            <div class="row g-2">
                <div class="col-md-5">
                    <select class="form-select" name="entity_type">
                        <option value="">All entity types</option>
                        <?php foreach (['submission', 'site', 'category', 'import_batch'] as $type): ?>
                            <option value="<?= e($type) ?>" <?= $entityType === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <select class="form-select" name="action">
                        <option value="">All actions</option>
                        <?php foreach (['created', 'updated', 'approved', 'rejected', 'imported'] as $item): ?>
                            <option value="<?= e($item) ?>" <?= $action === $item ? 'selected' : '' ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit">Filter</button></div>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>When</th><th>User</th><th>Entity</th><th>Action</th><th>Details</th></tr></thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No audit rows found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="small"><?= e($row['created_at']) ?></td>
                            <td><?= e($row['username'] ?: 'system') ?></td>
                            <td><?= e($row['entity_type']) ?> #<?= (int) $row['entity_id'] ?></td>
                            <td><span class="badge text-bg-light"><?= e($row['action']) ?></span></td>
                            <td class="small"><code><?= e($row['details'] ?? '') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php $path = '/editor/audit'; $query = array_filter(['entity_type' => $entityType, 'action' => $action], fn($v) => $v !== ''); require __DIR__ . '/../../layouts/pagination.php'; ?>
    </div>
</div>
