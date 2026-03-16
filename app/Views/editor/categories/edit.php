<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">Edit Category</h1>
        <p class="text-muted mb-0">Renames and moves now rebuild descendant paths safely.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/categories')) ?>">Back to Categories</a>
</div>

<?php if (file_exists(__DIR__ . '/../../layouts/editor_nav.php')): ?>
    <?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>
<?php endif; ?>

<div class="alert alert-warning border-0 shadow-sm">
    <div class="fw-semibold mb-1">Category integrity protection is active</div>
    <div class="small mb-0">This editor prevents moving a category under itself or under one of its descendants. If you rename or move this category, descendant paths are rebuilt automatically.</div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-6">
                <div class="text-muted">Current path</div>
                <code><?= e($category['path']) ?></code>
            </div>
            <div class="col-md-6">
                <div class="text-muted">Breadcrumb trail</div>
                <div>
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                        <?php if ($index > 0): ?> &raquo; <?php endif; ?>
                        <span><?= e($crumb['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('/editor/categories/' . $category['id'] . '/update')) ?>" class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Category name</label>
                <input type="text" name="name" class="form-control" required value="<?= e(old('name', $category['name'])) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Parent category</label>
                <select name="parent_id" class="form-select">
                    <option value="">Top level</option>
                    <?php $selectedParent = old('parent_id', (string) ($category['parent_id'] ?? '')); ?>
                    <?php foreach ($parentOptions as $option): ?>
                        <option value="<?= (int) $option['id'] ?>"<?= (string) $selectedParent === (string) $option['id'] ? ' selected' : '' ?>><?= e($option['path']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= e(old('description', $category['description'] ?? '')) ?></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sort order</label>
                <input type="number" name="sort_order" class="form-control" value="<?= e(old('sort_order', (string) $category['sort_order'])) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <?php $active = old('is_active', (string) $category['is_active']); ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"<?= (string) $active === '1' ? ' checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
