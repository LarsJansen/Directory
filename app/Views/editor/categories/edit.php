<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Edit Category</h1>
        <p class="text-muted mb-0">Changing the parent or name will refresh descendant paths automatically.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/editor/categories">Back to categories</a>
</div>

<?php $action = '/editor/categories/' . (int) $category['id'] . '/update'; require __DIR__ . '/_form.php'; ?>
