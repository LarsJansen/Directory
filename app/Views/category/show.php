<?php
$breadcrumbs = $breadcrumbs ?? [];
$children = $children ?? [];
$sites = $sites ?? [];
$sort = $sort ?? 'title';
$isTextArchiveSection = isset($category['path']) && (string) $category['path'] !== '' && (((string) $category['path']) === 'text-archives' || str_starts_with((string) $category['path'], 'text-archives/'));
$itemLabelSingular = $isTextArchiveSection ? 'file' : 'site';
$itemLabelPlural = $isTextArchiveSection ? 'files' : 'sites';
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= e(base_url('/')) ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= e(base_url('/category')) ?>">Browse</a></li>
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php $isLast = $index === array_key_last($breadcrumbs); ?>
                    <li class="breadcrumb-item<?= $isLast ? ' active' : '' ?>"<?= $isLast ? ' aria-current="page"' : '' ?>>
                        <?php if ($isLast): ?>
                            <?= e(display_name($crumb['name'])) ?>
                        <?php else: ?>
                            <a href="<?= e(base_url('/category/' . $crumb['path'])) ?>"><?= e(display_name($crumb['name'])) ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h1 class="h3 mb-1"><?= e(display_name($category['name'])) ?></h1>
                <?php if (!empty($category['description'])): ?>
                    <p class="text-muted mb-0"><?= e($category['description']) ?></p>
                <?php endif; ?>
            </div>

            <form method="get" action="<?= e(base_url('/category/' . $category['path'])) ?>" class="d-flex align-items-center gap-2">
                <label for="sort" class="form-label mb-0 small text-muted">Sort</label>
                <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="title"<?= $sort === 'title' ? ' selected' : '' ?>>Title</option>
                    <option value="newest"<?= $sort === 'newest' ? ' selected' : '' ?>>Newest</option>
                </select>
            </form>
        </div>

        <?php if (!empty($children)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Subcategories</h2>
                    <div class="row g-2">
                        <?php foreach ($children as $child): ?>
                            <div class="col-md-6 col-lg-4">
                                <a class="text-decoration-none" href="<?= e(base_url('/category/' . $child['path'])) ?>">
                                    <?= e(display_name($child['name'])) ?>
                                </a>
                                <span class="text-muted small">
                                    <?php if ((int) ($child['child_count'] ?? 0) > 0): ?>
                                        · <?= (int) $child['child_count'] ?> subcat<?= ((int) $child['child_count'] === 1) ? '' : 's' ?>
                                    <?php endif; ?>
                                    <?php if ((int) ($child['site_count'] ?? 0) > 0): ?>
                                        · <?= (int) $child['site_count'] ?> <?= (int) $child['site_count'] === 1 ? $itemLabelSingular : $itemLabelPlural ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= e(ucfirst($itemLabelPlural)) ?></h2>

                <?php if (empty($sites)): ?>
                    <p class="text-muted mb-0">No <?= e($itemLabelPlural) ?> listed in this category yet.</p>
                <?php else: ?>
                    <?php foreach ($sites as $site): ?>
                        <div class="site-card mb-3 pb-3 border-bottom">
                            <h3 class="h5 mb-1">
                                <a href="<?= e(entry_url($site, $category['path'])) ?>"<?= is_text_entry($site) ? "" : " target=\"_blank\" rel=\"noopener noreferrer\"" ?>>
                                    <?= e($site['title']) ?>
                                </a>
                            </h3>
                            <?php if (is_text_entry($site)): ?>
                                <div class="small text-muted mb-2">Text archive · <?= e(base_url('/category/' . $category['path'] . '/' . $site['slug'])) ?></div>
                            <?php elseif (!empty($site['url'])): ?>
                                <div class="small text-muted mb-2"><?= e($site['url']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($site['description'])): ?>
                                <?php if (!empty($site['description'])): ?>
                                <p class="mb-0"><?= e($site['description']) ?></p>
                            <?php endif; ?>
                            <?php if (is_text_entry($site)): ?>
                                <div class="small mt-2"><a href="<?= e(entry_url($site, $category['path'])) ?>">Read text archive</a></div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php
                    $path = '/category/' . $category['path'];
                    $paginationQuery = ['sort' => $sort];
                    require __DIR__ . '/../layouts/pagination.php';
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
