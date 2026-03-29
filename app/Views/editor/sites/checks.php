<?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Site checks</h1>
            <div class="small text-muted">Latest HTTP maintenance history</div>
        </div>

        <form class="card card-body mb-3" method="get" action="<?= e(base_url('/editor/sites/checks')) ?>">
            <div class="row g-2">
                <div class="col-md-8"><input class="form-control" type="search" name="q" value="<?= e($query) ?>" placeholder="Search by title, URL, or category path"></div>
                <div class="col-md-3">
                    <select class="form-select" name="result">
                        <option value="">Any result</option>
                        <option value="ok" <?= ($result ?? '') === 'ok' ? 'selected' : '' ?>>OK</option>
                        <option value="warn" <?= ($result ?? '') === 'warn' ? 'selected' : '' ?>>Warn</option>
                        <option value="fail" <?= ($result ?? '') === 'fail' ? 'selected' : '' ?>>Fail</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid"><button class="btn btn-outline-primary" type="submit">Go</button></div>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Checked At</th><th>Site</th><th>Category</th><th>Result</th><th>HTTP</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No site checks found yet. Run <code>php scripts/check_sites.php</code> first.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="small"><?= e($row['checked_at']) ?></td>
                                <td>
                                    <div><strong><?= e($row['site_title']) ?></strong></div>
                                    <div class="small text-muted"><?= e($row['site_url']) ?></div>
                                </td>
                                <td><code><?= e($row['category_path']) ?></code></td>
                                <td><span class="badge text-bg-<?= $row['result_status'] === 'ok' ? 'success' : ($row['result_status'] === 'warn' ? 'warning' : 'danger') ?>"><?= e($row['result_status']) ?></span></td>
                                <td><?= e((string) ($row['http_status'] ?? '')) ?></td>
                                <td class="small">
                                    <?php if (!empty($row['error_message'])): ?>
                                        <div><?= e($row['error_message']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['redirect_url'])): ?>
                                        <div>Redirect: <?= e($row['redirect_url']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['response_time_ms'])): ?>
                                        <div class="text-muted"><?= e($row['response_time_ms']) ?> ms</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php $path = '/editor/sites/checks'; $query = array_filter(['q' => $query, 'result' => $result ?? null], fn($v) => $v !== null && $v !== ''); require __DIR__ . '/../../layouts/pagination.php'; ?>
