<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Import batches</h1>
            <a class="btn btn-primary" href="<?= e(base_url('/editor/imports/create')) ?>">Create batch</a>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Batch</th><th>Source</th><th>Status</th><th>Started</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$batches): ?><tr><td colspan="5" class="text-center text-muted py-4">No import batches yet.</td></tr><?php endif; ?>
                    <?php foreach ($batches as $batch): ?>
                        <tr>
                            <td><?= e($batch['batch_label']) ?></td>
                            <td><?= e($batch['source_name']) ?><?= $batch['source_version'] ? ' / ' . e($batch['source_version']) : '' ?></td>
                            <td><?= e($batch['status']) ?></td>
                            <td><?= e($batch['started_at']) ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/editor/imports/' . $batch['id'])) ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
