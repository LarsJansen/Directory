<?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Duplicate normalized URLs</h1>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/sites')) ?>">Back to sites</a>
        </div>

        <form class="card card-body mb-3" method="get" action="<?= e(base_url('/editor/sites/duplicates')) ?>">
            <div class="row g-2">
                <div class="col-md-10"><input class="form-control" type="search" name="q" value="<?= e($query) ?>" placeholder="Filter duplicate groups by normalized URL, title, or raw URL"></div>
                <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit">Filter</button></div>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Normalized URL</th><th>Count</th><th>Sites</th></tr></thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No duplicate URL groups found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $ids = array_map('trim', explode(',', (string) $row['site_ids']));
                            $titles = array_map('trim', explode(' || ', (string) $row['site_titles']));
                            $paths = array_map('trim', explode(' || ', (string) $row['category_paths']));
                        ?>
                        <tr>
                            <td><code><?= e($row['normalized_url']) ?></code></td>
                            <td><?= (int) $row['duplicate_count'] ?></td>
                            <td>
                                <ul class="mb-0 small ps-3">
                                    <?php foreach ($ids as $i => $siteId): ?>
                                        <li>
                                            <a href="<?= e(base_url('/editor/sites/' . (int) $siteId . '/edit')) ?>">#<?= (int) $siteId ?> <?= e($titles[$i] ?? 'Untitled') ?></a>
                                            <span class="text-muted">in <?= e($paths[$i] ?? 'unknown category') ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php $path = '/editor/sites/duplicates'; $query = $query !== '' ? ['q' => $query] : []; require __DIR__ . '/../../layouts/pagination.php'; ?>
