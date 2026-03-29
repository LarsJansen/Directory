<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Add site or text archive</h1>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/sites')) ?>">Back to sites</a>
        </div>

        <div class="card"><div class="card-body">
            <form method="post" action="<?= e(base_url('/editor/sites')) ?>" class="row g-3">
                <?= csrf_input() ?>
                <div class="col-md-8"><label class="form-label">Title</label><input class="form-control" type="text" name="title" required></div>
                <div class="col-md-4"><label class="form-label">Slug</label><input class="form-control" type="text" name="slug" placeholder="leave blank to auto-generate"></div>
                <div class="col-md-8"><label class="form-label">Category</label><select class="form-select" name="category_id" required><option value="">Choose a category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['path']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Content type</label><select class="form-select" name="content_type" id="content_type"><option value="link" selected>External link</option><option value="text">Text archive</option></select></div>
                <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" selected>active</option><option value="flagged">flagged</option><option value="dead">dead</option><option value="hidden">hidden</option></select></div>
                <div class="col-12" data-link-only><label class="form-label">URL</label><input class="form-control" type="text" name="url"></div>
                <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="5" required></textarea></div>
                <div class="col-12" data-text-only><label class="form-label">Body text</label><textarea class="form-control" name="body_text" rows="16"></textarea><div class="form-text">Paste the preserved plain text here for mirrored text archive entries.</div></div>
                <div class="col-md-6" data-text-only><label class="form-label">Text author</label><input class="form-control" type="text" name="text_author"></div>
                <div class="col-md-6" data-text-only><label class="form-label">Source note</label><input class="form-control" type="text" name="text_source_note" placeholder="e.g. Mirrored from textfiles.com"></div>
                <div class="col-md-4"><label class="form-label">Original title</label><input class="form-control" type="text" name="original_title"></div>
                <div class="col-md-4"><label class="form-label">Original URL</label><input class="form-control" type="text" name="original_url"></div>
                <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked><label class="form-check-label" for="is_active">Active listing</label></div></div>
                <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"><label class="form-check-label" for="is_featured">Featured</label></div></div>
                <div class="col-12"><label class="form-label">Original description</label><textarea class="form-control" name="original_description" rows="4"></textarea></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">Create resource</button><a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/sites')) ?>">Cancel</a></div>
            </form>
        </div></div>
    </div>
</div>

<script>
(function () {
    const select = document.getElementById('content_type');
    if (!select) {
        return;
    }

    function toggleSiteTypeFields() {
        const isText = select.value === 'text';
        document.querySelectorAll('[data-text-only]').forEach(function (el) {
            el.style.display = isText ? '' : 'none';
        });
        document.querySelectorAll('[data-link-only]').forEach(function (el) {
            el.style.display = isText ? 'none' : '';
        });
    }

    select.addEventListener('change', toggleSiteTypeFields);
    toggleSiteTypeFields();
})();
</script>
