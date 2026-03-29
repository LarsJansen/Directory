<?php $currentPath = request_path(); ?>
<div class="card mb-4 editor-top-nav">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-uppercase text-muted small fw-semibold me-2">Editor</span>
                <a class="btn btn-sm <?= $currentPath === '/editor' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(base_url('/editor')) ?>">Dashboard</a>
                <a class="btn btn-sm <?= str_starts_with($currentPath, '/editor/submissions') ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(base_url('/editor/submissions')) ?>">Submissions</a>
                <a class="btn btn-sm <?= str_starts_with($currentPath, '/editor/sites/create') ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(base_url('/editor/sites/create')) ?>">Add Resource</a>
                <?php $sitesActive = $currentPath === '/editor/sites' || (str_starts_with($currentPath, '/editor/sites/') && !str_starts_with($currentPath, '/editor/sites/dead') && !str_starts_with($currentPath, '/editor/sites/checks') && !str_starts_with($currentPath, '/editor/sites/duplicates') && !str_starts_with($currentPath, '/editor/sites/create')); ?>
                <a class="btn btn-sm <?= $sitesActive ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(base_url('/editor/sites')) ?>">Sites</a>
                <a class="btn btn-sm <?= str_starts_with($currentPath, '/editor/sites/dead') ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(base_url('/editor/sites/dead')) ?>">Dead Queue</a>
                <a class="btn btn-sm <?= str_starts_with($currentPath, '/editor/sites/duplicates') ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(base_url('/editor/sites/duplicates')) ?>">Duplicate URLs</a>
                <a class="btn btn-sm <?= str_starts_with($currentPath, '/editor/sites/checks') ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(base_url('/editor/sites/checks')) ?>">Site Checks</a>
                <a class="btn btn-sm <?= str_starts_with($currentPath, '/editor/categories') ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(base_url('/editor/categories')) ?>">Categories</a>
                <a class="btn btn-sm <?= str_starts_with($currentPath, '/editor/audit') ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(base_url('/editor/audit')) ?>">Audit Log</a>
                <a class="btn btn-sm <?= str_starts_with($currentPath, '/editor/imports') ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(base_url('/editor/imports')) ?>">Imports</a>
            </div>
            <?php if (is_editor_logged_in()): ?>
                <form method="post" action="<?= e(base_url('/editor/logout')) ?>" class="m-0">
                    <?= csrf_input() ?>
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Log out <?= e(current_user()['username']) ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
