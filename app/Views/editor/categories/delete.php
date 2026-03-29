<?php require __DIR__ . '/../../layouts/editor_nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Delete category</h1>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/edit')) ?>">Back to edit</a>
        </div>

        <div class="card mb-3 border-danger-subtle">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small text-uppercase mb-1">Category path</div>
                        <code><?= e($category['path']) ?></code>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small text-uppercase mb-1">Parent</div>
                        <div><?= !empty($summary['parent']) ? e($summary['parent']['path']) : 'Top level' ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase mb-1">Direct children</div>
                        <div><?= (int) ($summary['direct_child_count'] ?? 0) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase mb-1">Descendants</div>
                        <div><?= (int) ($summary['descendant_count'] ?? 0) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase mb-1">Direct sites</div>
                        <div><?= (int) ($summary['direct_site_count'] ?? 0) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase mb-1">Sites in branch</div>
                        <div><?= (int) ($summary['site_count_in_branch'] ?? 0) ?></div>
                    </div>
                </div>
                <p class="text-muted small mt-3 mb-0">Deletion is intentionally conservative. Empty categories can be deleted directly. Categories with sites can optionally move those sites to the parent if there are no child categories. Entire branch deletion is available as an explicit destructive action.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="post" action="<?= e(base_url('/editor/categories/' . $category['id'] . '/delete')) ?>" class="row g-3">
                    <?= csrf_input() ?>

                    <div class="col-12">
                        <label class="form-label">Delete mode</label>
                        <div class="vstack gap-3">
                            <div class="form-check border rounded p-3">
                                <input class="form-check-input" type="radio" name="mode" id="mode_empty" value="empty" checked>
                                <label class="form-check-label w-100" for="mode_empty">
                                    <span class="fw-semibold d-block">Delete empty category</span>
                                    <span class="text-muted small d-block">Only works when the category has no child categories and no directly attached sites.</span>
                                </label>
                            </div>

                            <div class="form-check border rounded p-3 <?= !($summary['can_move_sites_to_parent'] ?? false) ? 'bg-light' : '' ?>">
                                <input class="form-check-input" type="radio" name="mode" id="mode_move_sites" value="move_sites_to_parent" <?= !($summary['can_move_sites_to_parent'] ?? false) ? 'disabled' : '' ?>>
                                <label class="form-check-label w-100" for="mode_move_sites">
                                    <span class="fw-semibold d-block">Delete category and move sites to parent</span>
                                    <span class="text-muted small d-block">
                                        <?php if (!empty($summary['parent'])): ?>
                                            Moves directly attached sites to <code><?= e($summary['parent']['path']) ?></code> before deleting this category. Only available when there are no child categories.
                                        <?php else: ?>
                                            Unavailable because this is a top-level category and there is no parent category to receive the sites.
                                        <?php endif; ?>
                                    </span>
                                </label>
                            </div>

                            <div class="border rounded p-3 border-danger-subtle">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="mode" id="mode_branch" value="delete_branch">
                                    <label class="form-check-label w-100" for="mode_branch">
                                        <span class="fw-semibold d-block text-danger">Delete entire branch</span>
                                        <span class="text-muted small d-block">Deletes this category, all descendant categories, and all sites attached anywhere in the branch.</span>
                                    </label>
                                </div>
                                <div class="form-check ms-4">
                                    <input class="form-check-input" type="checkbox" name="confirm_branch_delete" id="confirm_branch_delete" value="1">
                                    <label class="form-check-label" for="confirm_branch_delete">I understand this will permanently delete the full category branch and its sites.</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button class="btn btn-danger" type="submit">Delete category</button>
                        <a class="btn btn-outline-secondary" href="<?= e(base_url('/editor/categories/' . $category['id'] . '/edit')) ?>">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
