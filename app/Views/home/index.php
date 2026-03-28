<?php
$categories = $categories ?? [];
$latestSites = $latestSites ?? [];
?>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
            <div>
                <h1 class="h2 mb-2">Welcome to the Internet History Directory</h1>
                <p class="text-muted mb-3">The story of the Internet, one link at a time.</p>
            </div>
            <div class="text-lg-end">
                <div class="small text-muted">Human-curated. Historically focused. Built link by link.</div>
            </div>
        </div>

        <p class="mb-3">
            The Internet did not arrive fully formed. It grew out of experiments, communities, protocols, and ideas—many of which still exist, quietly, beneath the surface of today’s web.
        </p>
        <p class="mb-3">
            The <strong>Internet History Directory</strong> is a curated guide to that world: the history, the technologies, the people, and the sites that shaped it. From ARPANET and early Usenet discussions to the first web browsers, search engines, and personal homepages, each entry is selected for its relevance, context, and contribution to the story of the Internet.
        </p>
        <p class="mb-4">
            Whether you are researching, reminiscing, or simply exploring, this directory is a place to rediscover how the web came to be—and how much of it is still out there.
        </p>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-dark btn-sm" href="<?= e(base_url('/category')) ?>">Browse Categories</a>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/pages/history-of-the-internet')) ?>">Read the History of the Internet</a>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/submit')) ?>">Suggest a Site</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 mb-0">Browse by Category</h2>
                    <a class="small text-decoration-none" href="<?= e(base_url('/category')) ?>">View full category tree</a>
                </div>

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

                                <?php if (!empty($category['description'])): ?>
                                    <div class="small text-muted mt-1">
                                        <?= e($category['description']) ?>
                                    </div>
                                <?php endif; ?>

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

        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h4 mb-3">About This Directory</h2>
                <p class="mb-2">
                    This project focuses on the foundations of the networked world: early infrastructure, pioneering people, landmark services, standards bodies, preservation projects, and the culture that grew around them.
                </p>
                <p class="mb-0 text-muted">
                    It is not intended to be an exhaustive index of the modern web. It is a selective, human-edited directory centred on significance, context, and long-term historical value.
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
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
                                    <a class="text-decoration-none" href="<?= e(base_url('/category/' . $site['category_path'])) ?>">
                                        <?= e(display_name($site['category_name'])) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Start Here</h2>
                <ul class="mb-0 ps-3">
                    <li class="mb-2"><a href="<?= e(base_url('/category/history')) ?>">History</a></li>
                    <li class="mb-2"><a href="<?= e(base_url('/category/people')) ?>">People</a></li>
                    <li class="mb-2"><a href="<?= e(base_url('/category/browsers')) ?>">Browsers</a></li>
                    <li class="mb-2"><a href="<?= e(base_url('/category/search-engines')) ?>">Search Engines</a></li>
                    <li class="mb-2"><a href="<?= e(base_url('/category/archives-and-preservation')) ?>">Archives and Preservation</a></li>
                    <li><a href="<?= e(base_url('/category/culture')) ?>">Culture</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
