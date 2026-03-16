<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <h1 class="h3 mb-3">Create category</h1>
        <div class="card"><div class="card-body">
            <form method="post" action="<?= e(base_url('/editor/categories')) ?>" class="row g-3">
                <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" type="text" name="name" required></div>
                <div class="col-md-6"><label class="form-label">Slug</label><input class="form-control" type="text" name="slug" placeholder="leave blank to auto-generate"></div>
                <div class="col-md-8"><label class="form-label">Parent category</label><select class="form-select" name="parent_id"><option value="">Top level</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['path']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Sort order</label><input class="form-control" type="number" name="sort_order" value="0"></div>
                <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"></textarea></div>
                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked><label class="form-check-label" for="is_active">Active</label></div></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Create category</button></div>
            </form>
        </div></div>
    </div>
</div>
