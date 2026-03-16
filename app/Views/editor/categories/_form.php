<?php $isEdit = !empty($category); ?>
<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= e($action) ?>" method="post" novalidate>
            <div class="mb-3">
                <label class="form-label">Parent category</label>
                <select class="form-select <?= errors('parent_id') ? 'is-invalid' : '' ?>" name="parent_id">
                    <option value="">Top level</option>
                    <?php foreach ($parentOptions as $option): ?>
                        <?php $currentValue = old('parent_id', $category['parent_id'] ?? ''); ?>
                        <option value="<?= (int) $option['id'] ?>" <?= selected_if($currentValue, $option['id']) ?>><?= e($option['path']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (errors('parent_id')): ?><div class="invalid-feedback"><?= e(errors('parent_id')) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Category name</label>
                <input class="form-control <?= errors('name') ? 'is-invalid' : '' ?>" name="name" value="<?= e(old('name', $category['name'] ?? '')) ?>">
                <?php if (errors('name')): ?><div class="invalid-feedback"><?= e(errors('name')) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="4"><?= e(old('description', $category['description'] ?? '')) ?></textarea>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Sort order</label>
                    <input class="form-control <?= errors('sort_order') ? 'is-invalid' : '' ?>" name="sort_order" value="<?= e(old('sort_order', $category['sort_order'] ?? 0)) ?>">
                    <?php if (errors('sort_order')): ?><div class="invalid-feedback"><?= e(errors('sort_order')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Visibility</label>
                    <select class="form-select" name="is_active">
                        <option value="1" <?= selected_if(old('is_active', $category['is_active'] ?? 1), 1) ?>>Active</option>
                        <option value="0" <?= selected_if(old('is_active', $category['is_active'] ?? 1), 0) ?>>Hidden</option>
                    </select>
                </div>
                <?php if ($isEdit): ?>
                    <div class="col-md-4">
                        <label class="form-label">Current path</label>
                        <input class="form-control" value="<?= e($category['path']) ?>" disabled>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 d-flex gap-2 flex-wrap">
                <button class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create category' ?></button>
                <a class="btn btn-outline-secondary" href="/editor/categories">Cancel</a>
            </div>
        </form>
    </div>
</div>
