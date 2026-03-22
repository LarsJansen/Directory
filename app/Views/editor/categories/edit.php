<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <h1 class="h3 mb-3">Edit category</h1>
        <div class="card"><div class="card-body">
            <form method="post" action="<?= e(base_url('/editor/categories/' . $category['id'] . '/update')) ?>" class="row g-3">
                <?= csrf_input() ?>
                <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" type="text" name="name" required value="<?= e($category['name']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Slug</label><input class="form-control" type="text" name="slug" value="<?= e($category['slug']) ?>"></div>
                <div class="col-md-8"><label class="form-label">Parent category</label><select class="form-select" name="parent_id"><option value="">Top level</option><?php foreach ($categories as $row): if ((int) $row['id'] === (int) $category['id']) continue; ?><option value="<?= (int) $row['id'] ?>" <?= (int) $category['parent_id'] === (int) $row['id'] ? 'selected' : '' ?>><?= e($row['path']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Sort order</label><input class="form-control" type="number" name="sort_order" value="<?= (int) $category['sort_order'] ?>"></div>
                <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"><?= e($category['description']) ?></textarea></div>
                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= (int) $category['is_active'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="is_active">Active</label></div></div>
                <div class="col-12 d-flex gap-2 flex-wrap"><button class="btn btn-primary" type="submit">Save changes</button><a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/move')) ?>">Move branch</a><a class="btn btn-outline-danger" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/delete')) ?>">Delete category</a></div>
            </form>
        </div></div>
    </div>
</div>
