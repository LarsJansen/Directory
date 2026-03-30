<?php
$breadcrumbs = $breadcrumbs ?? [];
$site = $site ?? [];
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= e(base_url('/')) ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= e(base_url('/category')) ?>">Browse</a></li>
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <li class="breadcrumb-item"><a href="<?= e(base_url('/category/' . $crumb['path'])) ?>"><?= e(display_name($crumb['name'])) ?></a></li>
                <?php endforeach; ?>
                <li class="breadcrumb-item active" aria-current="page"><?= e($site['title'] ?? 'Resource') ?></li>
            </ol>
        </nav>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php if (is_text_entry($site)): ?>
                                <span class="badge bg-secondary">Text Archive</span>
                            <?php else: ?>
                                <span class="badge bg-primary">External Link</span>
                            <?php endif; ?>
                            <?php if ((int) ($site['is_featured'] ?? 0) === 1): ?>
                                <span class="badge bg-warning text-dark">Featured</span>
                            <?php endif; ?>
                        </div>
                        <h1 class="h3 mb-2"><?= e($site['title'] ?? 'Resource') ?></h1>
                        <?php if (!empty($site['description'])): ?>
                            <p class="text-muted mb-0"><?= e($site['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!is_text_entry($site) && !empty($site['url'])): ?>
                        <div>
                            <a class="btn btn-primary btn-sm" href="<?= e($site['url']) ?>" target="_blank" rel="noopener noreferrer">Visit resource</a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($site['url'])): ?>
                    <div class="mb-3 small">
                        <strong><?= is_text_entry($site) ? 'Reference URL:' : 'URL:' ?></strong>
                        <a href="<?= e($site['url']) ?>"<?= is_text_entry($site) ? '' : ' target="_blank" rel="noopener noreferrer"' ?>><?= e($site['url']) ?></a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($site['text_author']) || !empty($site['text_source_note']) || !empty($site['original_url'])): ?>
                    <div class="row g-3 mb-4">
                        <?php if (!empty($site['text_author'])): ?>
                            <div class="col-md-4">
                                <div class="small text-muted">Author</div>
                                <div><?= e($site['text_author']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($site['text_source_note'])): ?>
                            <div class="col-md-4">
                                <div class="small text-muted">Source note</div>
                                <div><?= e($site['text_source_note']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($site['original_url'])): ?>
                            <div class="col-md-4">
                                <div class="small text-muted">Original URL</div>
                                <div><a href="<?= e($site['original_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($site['original_url']) ?></a></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (is_text_entry($site)): ?>
                    <div class="text-archive-shell">
                        <div class="text-archive-toolbar small text-muted">
                            Preserved plain text archive
                        </div>
                        <div class="text-archive-content">
                            <pre class="mb-0 text-archive-pre"><?= e((string) ($site['body_text'] ?? '')) ?></pre>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="mb-0">This resource is an external site entry in the directory. Use the button above to visit it.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
