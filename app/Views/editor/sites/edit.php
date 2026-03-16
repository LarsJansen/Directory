<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Edit Site</h1>
        <p class="text-muted mb-0">Adjust the category, listing copy, and status flags for this record.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/editor/sites">Back to sites</a>
</div>

<?php if ($duplicates): ?>
    <div class="alert alert-warning">
        <div class="fw-semibold mb-2">Duplicate normalized URL detected</div>
        <?php foreach ($duplicates as $duplicate): ?>
            <div>#<?= (int) $duplicate['id'] ?> <?= e($duplicate['title']) ?> <span class="text-muted">in <?= e($duplicate['category_path']) ?></span></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="/editor/sites/<?= (int) $site['id'] ?>/update" method="post" novalidate>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select class="form-select <?= errors('category_id') ? 'is-invalid' : '' ?>" name="category_id">
                    <option value="">Choose a category</option>
                    <?php foreach ($parentOptions as $option): ?>
                        <option value="<?= (int) $option['id'] ?>" <?= selected_if(old('category_id', $site['category_id']), $option['id']) ?>><?= e($option['path']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (errors('category_id')): ?><div class="invalid-feedback"><?= e(errors('category_id')) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Title</label>
                <input class="form-control <?= errors('title') ? 'is-invalid' : '' ?>" name="title" value="<?= e(old('title', $site['title'])) ?>">
                <?php if (errors('title')): ?><div class="invalid-feedback"><?= e(errors('title')) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">URL</label>
                <input class="form-control <?= errors('url') ? 'is-invalid' : '' ?>" name="url" value="<?= e(old('url', $site['url'])) ?>">
                <?php if (errors('url')): ?><div class="invalid-feedback"><?= e(errors('url')) ?></div><?php endif; ?>
                <div class="form-text">Normalized as: <?= e((new \App\Models\Site($this->db))->normalizeUrl(old('url', $site['url']))) ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control <?= errors('description') ? 'is-invalid' : '' ?>" name="description" rows="5"><?= e(old('description', $site['description'])) ?></textarea>
                <?php if (errors('description')): ?><div class="invalid-feedback"><?= e(errors('description')) ?></div><?php endif; ?>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select <?= errors('status') ? 'is-invalid' : '' ?>" name="status">
                        <option value="active" <?= selected_if(old('status', $site['status']), 'active') ?>>Active</option>
                        <option value="dead" <?= selected_if(old('status', $site['status']), 'dead') ?>>Dead</option>
                        <option value="flagged" <?= selected_if(old('status', $site['status']), 'flagged') ?>>Flagged</option>
                    </select>
                    <?php if (errors('status')): ?><div class="invalid-feedback"><?= e(errors('status')) ?></div><?php endif; ?>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Review notes</label>
                    <input class="form-control" name="review_notes" value="<?= e(old('review_notes', $site['review_notes'] ?? '')) ?>">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2 flex-wrap">
                <button class="btn btn-primary">Save changes</button>
                <a class="btn btn-outline-secondary" href="/editor/sites">Cancel</a>
            </div>
        </form>
    </div>
</div>
