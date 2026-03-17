<div class="card mb-4 editor-sidebar">
    <div class="card-body">
        <h6 class="text-uppercase text-muted">Editor</h6>
        <div class="nav flex-column nav-pills gap-1 mt-3">
            <a class="nav-link<?= request_path() === '/editor' ? ' active' : '' ?>" href="<?= e(base_url('/editor')) ?>">Dashboard</a>
            <a class="nav-link<?= str_starts_with(request_path(), '/editor/submissions') ? ' active' : '' ?>" href="<?= e(base_url('/editor/submissions')) ?>">Submissions</a>
            <a class="nav-link<?= str_starts_with(request_path(), '/editor/sites/checks') ? ' active' : '' ?>" href="<?= e(base_url('/editor/sites/checks')) ?>">Site Checks</a>
            <a class="nav-link<?= str_starts_with(request_path(), '/editor/sites/dead') ? ' active' : '' ?>" href="<?= e(base_url('/editor/sites/dead')) ?>">Dead Queue</a>
            <a class="nav-link<?= str_starts_with(request_path(), '/editor/sites/duplicates') ? ' active' : '' ?>" href="<?= e(base_url('/editor/sites/duplicates')) ?>">Duplicate URLs</a>
            <a class="nav-link<?= request_path() === '/editor/sites' || (str_starts_with(request_path(), '/editor/sites/') && !str_starts_with(request_path(), '/editor/sites/duplicates') && !str_starts_with(request_path(), '/editor/sites/checks') && !str_starts_with(request_path(), '/editor/sites/dead')) ? ' active' : '' ?>" href="<?= e(base_url('/editor/sites')) ?>">Sites</a>
            <a class="nav-link<?= str_starts_with(request_path(), '/editor/categories') ? ' active' : '' ?>" href="<?= e(base_url('/editor/categories')) ?>">Categories</a>
            <a class="nav-link<?= str_starts_with(request_path(), '/editor/audit') ? ' active' : '' ?>" href="<?= e(base_url('/editor/audit')) ?>">Audit Log</a>
            <a class="nav-link<?= str_starts_with(request_path(), '/editor/imports') ? ' active' : '' ?>" href="<?= e(base_url('/editor/imports')) ?>">Imports</a>
        </div>
        <?php if (is_editor_logged_in()): ?>
            <form method="post" action="<?= e(base_url('/editor/logout')) ?>" class="mt-3">
                <button class="btn btn-outline-secondary btn-sm w-100" type="submit">Log out <?= e(current_user()['username']) ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>
