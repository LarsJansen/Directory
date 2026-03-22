<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h3 mb-3">Submit a site</h1>
                <p class="text-muted">Submissions are reviewed by an editor before appearing in the live directory.</p>

                <form method="post" action="<?= e(base_url('/submit')) ?>" class="row g-3 mt-1">
                    <?= csrf_input() ?>
                    <div class="col-md-6">
                        <label class="form-label">Your name</label>
                        <input class="form-control" type="text" name="submitter_name" value="<?= e(old('submitter_name')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Your email</label>
                        <input class="form-control" type="email" name="submitter_email" value="<?= e(old('submitter_email')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Suggested category</label>
                        <select class="form-select" name="proposed_category_id">
                            <option value="">Choose a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>"><?= e($category['path']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Site title</label>
                        <input class="form-control" type="text" name="title" required value="<?= e(old('title')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">URL</label>
                        <input class="form-control" type="text" name="url" required placeholder="https://example.com" value="<?= e(old('url')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="5" required><?= e(old('description')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes for editor</label>
                        <textarea class="form-control" name="notes" rows="3"><?= e(old('notes')) ?></textarea>
                    </div>
                    <div class="col-12 d-grid d-md-flex justify-content-md-end">
                        <button class="btn btn-primary" type="submit">Submit for review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
