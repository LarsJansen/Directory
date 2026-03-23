<?php
$categories = $categories ?? [];
$latestSites = $latestSites ?? [];
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h1 class="h4 mb-3">All Categories</h1>

                <?php if (empty($categories)): ?>
                    <p class="text-muted mb-0">No categories available yet.</p>
                <?php else: ?>
                    <div class="directory-home-categories">
                        <?php foreach ($categories as $category): ?>
                            <div class="directory-home-category">
                                <div class="directory-home-category-title">
                                    <a href="<?= e(base_url('/category/' . $category['path'])) ?>">
                                        <?= e(display_name($category['name'])) ?>
                                    </a>
                                    <span class="directory-home-category-count">(<?= (int) ($category['total_site_count'] ?? 0) ?>)</span>
                                </div>

                                <?php if (!empty($category['featured_children'])): ?>
                                    <div class="directory-home-category-children">
                                        <?php foreach ($category['featured_children'] as $index => $child): ?>
                                            <?php if ($index > 0): ?>, <?php endif; ?>
                                            <a href="<?= e(base_url('/category/' . $child['path'])) ?>">
                                                <?= e(display_name($child['name'])) ?>
                                            </a>
                                        <?php endforeach; ?>
                                        <?php if (!empty($category['has_more_children'])): ?> ...<?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Latest Listings</h2>

                <?php if (empty($latestSites)): ?>
                    <p class="text-muted mb-0">No listings available yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($latestSites as $site): ?>
                            <div class="list-group-item px-0">
                                <div class="fw-semibold">
                                    <a href="<?= e($site['url']) ?>" target="_blank" rel="noopener noreferrer">
                                        <?= e($site['title']) ?>
                                    </a>
                                </div>
                                <div class="small text-muted">
                                    <a href="<?= e(base_url('/category/' . $site['category_path'])) ?>">
                                        <?= e(display_name($site['category_name'])) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
