<?php
$categories = $categories ?? [];
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-3">Browse Categories</h1>

        <?php if (empty($categories)): ?>
            <div class="alert alert-info">No categories available yet.</div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="directory-home-categories">
                        <?php foreach ($categories as $category): ?>
                            <div class="directory-home-category">
                                <div class="directory-home-category-title">
                                    <a href="<?= e(base_url('/category/' . $category['path'])) ?>">
                                        <?= e(display_name($category['name'])) ?>
                                    </a>
                                    <span class="directory-home-category-count">
                                        (<?= (int) ($category['site_count'] ?? 0) ?>)
                                    </span>
                                </div>

                                <?php $children = $category['children'] ?? []; ?>
                                <?php if (!empty($children)): ?>
                                    <div class="directory-home-category-children">
                                        <?php foreach ($children as $index => $child): ?>
                                            <?php if ($index > 0): ?>, <?php endif; ?>
                                            <a href="<?= e(base_url('/category/' . $child['path'])) ?>">
                                                <?= e(display_name($child['name'])) ?>
                                            </a>
                                            <span class="directory-home-category-count">
                                                (<?= (int) ($child['site_count'] ?? 0) ?>)
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
