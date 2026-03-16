<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-3">Suggest a Site</h1>
                <p class="text-muted">Submissions go into an editor review queue before they appear in the public directory. Exact duplicate URLs are blocked automatically.</p>

                <form action="/submit" method="post" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select <?= errors('proposed_category_id') ? 'is-invalid' : '' ?>" name="proposed_category_id">
                            <option value="">Choose a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= selected_if(old('proposed_category_id'), $category['id']) ?>>
                                    <?= e($category['path']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (errors('proposed_category_id')): ?><div class="invalid-feedback"><?= e(errors('proposed_category_id')) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Your name</label>
                        <input class="form-control" name="submitter_name" value="<?= e(old('submitter_name')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Your email</label>
                        <input class="form-control" type="email" name="submitter_email" value="<?= e(old('submitter_email')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Site title</label>
                        <input class="form-control <?= errors('title') ? 'is-invalid' : '' ?>" name="title" value="<?= e(old('title')) ?>">
                        <?php if (errors('title')): ?><div class="invalid-feedback"><?= e(errors('title')) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input class="form-control <?= errors('url') ? 'is-invalid' : '' ?>" name="url" value="<?= e(old('url')) ?>" placeholder="https://example.com">
                        <?php if (errors('url')): ?><div class="invalid-feedback"><?= e(errors('url')) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control <?= errors('description') ? 'is-invalid' : '' ?>" name="description" rows="5"><?= e(old('description')) ?></textarea>
                        <?php if (errors('description')): ?><div class="invalid-feedback"><?= e(errors('description')) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes for editor</label>
                        <textarea class="form-control" name="notes" rows="3"><?= e(old('notes')) ?></textarea>
                    </div>

                    <button class="btn btn-primary" type="submit">Submit for review</button>
                </form>
            </div>
        </div>
    </div>
</div>
