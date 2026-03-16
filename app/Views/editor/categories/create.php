<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">Create Category</h1>
        <p class="text-muted mb-0">Create a new category and let the system build the correct slug and path.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/categories')) ?>">Back to Categories</a>
</div>

<?php if (file_exists(__DIR__ . '/../../layouts/editor_nav.php')): ?>
    <?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('/editor/categories')) ?>" class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Category name</label>
                <input type="text" name="name" class="form-control" required value="<?= e(old('name')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Parent category</label>
                <select name="parent_id" class="form-select">
                    <option value="">Top level</option>
                    <?php foreach ($parentOptions as $option): ?>
                        <option value="<?= (int) $option['id'] ?>"<?= old('parent_id') == $option['id'] ? ' selected' : '' ?>><?= e($option['path']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= e(old('description')) ?></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sort order</label>
                <input type="number" name="sort_order" class="form-control" value="<?= e(old('sort_order', '0')) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"<?= old('is_active', '1') ? ' checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Create Category</button>
            </div>
        </form>
    </div>
</div>
