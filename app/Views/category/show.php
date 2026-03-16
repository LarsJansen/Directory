<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(base_url('/')) ?>">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= e(base_url('/category')) ?>">Categories</a></li>
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <li class="breadcrumb-item<?= $index === count($breadcrumbs) - 1 ? ' active' : '' ?>">
                <?php if ($index === count($breadcrumbs) - 1): ?>
                    <?= e($crumb['name']) ?>
                <?php else: ?>
                    <a href="<?= e(base_url('/category/' . $crumb['path'])) ?>"><?= e($crumb['name']) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e($category['name']) ?></h1>
        <div class="text-muted"><?= e($category['path']) ?></div>
        <?php if (!empty($category['description'])): ?>
            <p class="mt-2 mb-0"><?= e($category['description']) ?></p>
        <?php endif; ?>
    </div>
    <form class="d-flex gap-2" method="get">
        <input type="hidden" name="page" value="1">
        <select class="form-select" name="sort" onchange="this.form.submit()">
            <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Title A-Z</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest added</option>
        </select>
    </form>
</div>

<?php if ($children): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5">Subcategories</h2>
            <div class="row row-cols-1 row-cols-md-2 g-3 mt-1">
                <?php foreach ($children as $child): ?>
                    <div class="col">
                        <div class="border rounded p-3 h-100">
                            <div><a href="<?= e(base_url('/category/' . $child['path'])) ?>"><?= e($child['name']) ?></a></div>
                            <div class="small text-muted"><?= (int) ($child['site_count'] ?? 0) ?> site<?= $child['site_count'] == 1 ? '' : 's' ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <h2 class="h5">Listings</h2>
        <?php if (!$sites): ?>
            <p class="text-muted mb-0">No live listings in this category yet.</p>
        <?php endif; ?>

        <?php foreach ($sites as $site): ?>
            <div class="border-bottom py-3">
                <h3 class="h6 mb-1"><a href="<?= e($site['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($site['title']) ?></a></h3>
                <div class="small text-muted mb-1"><?= e($site['url']) ?></div>
                <div><?= e($site['description']) ?></div>
            </div>
        <?php endforeach; ?>

        <?php
            $path = '/category/' . $category['path'];
            $query = ['sort' => $sort];
            require __DIR__ . '/../layouts/pagination.php';
        ?>
    </div>
</div>
