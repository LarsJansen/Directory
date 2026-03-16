<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Browse Categories</h1>
        <p class="text-muted mb-0">Explore the top-level sections of the directory.</p>
    </div>
</div>

<?php if (empty($categories)): ?>

<div class="alert alert-info">
    No categories available yet.
</div>

<?php else: ?>

<div class="row g-3">

<?php foreach ($categories as $category): ?>

<div class="col-md-6 col-lg-4">
<div class="card h-100 shadow-sm">

<div class="card-body">

<h2 class="h5 card-title mb-2">
<a href="/category/<?= e($category['path']) ?>" class="text-decoration-none">
<?= e($category['name']) ?>
</a>
</h2>

<?php if (!empty($category['description'])): ?>

<p class="card-text text-muted small mb-3">
<?= e($category['description']) ?>
</p>

<?php endif; ?>

<div class="small text-muted">
<?= (int) ($category['child_count'] ?? 0) ?> subcategories
&middot;
<?= (int) ($category['site_count'] ?? 0) ?> sites
</div>

</div>

<div class="card-footer bg-white border-top-0 pt-0">
<a href="/category/<?= e($category['path']) ?>" class="btn btn-sm btn-outline-primary">
View Category
</a>
</div>

</div>
</div>

<?php endforeach; ?>

</div>

<?php endif; ?>