<div class="mb-4">
    <h1 class="h3 mb-2">Search</h1>
    <?php if ($term !== ''): ?>
        <p class="text-muted mb-0">Results for <strong><?= e($term) ?></strong></p>
    <?php else: ?>
        <p class="text-muted mb-0">Enter a search term above.</p>
    <?php endif; ?>
</div>

<?php if ($term !== '' && empty($results)): ?>
    <div class="alert alert-warning">No listings matched your search.</div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($results as $site): ?>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-3 flex-wrap">
                        <div>
                            <h2 class="h5 mb-1"><?= e($site['title']) ?></h2>
                            <div class="mb-2"><a href="<?= e($site['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($site['url']) ?></a></div>
                            <p class="mb-0 text-muted"><?= e($site['description']) ?></p>
                        </div>
                        <div>
                            <a class="badge text-bg-secondary text-decoration-none" href="/category/<?= e($site['category_path']) ?>"><?= e($site['category_name']) ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
