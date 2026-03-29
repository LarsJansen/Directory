<div class="row">
    <div class="col-lg-9">
        <h1 class="h3 mb-3">Search the directory</h1>
        <form class="card card-body mb-4" method="get" action="<?= e(base_url('/search')) ?>">
            <div class="row g-2">
                <div class="col-md-10">
                    <input class="form-control" type="search" name="q" value="<?= e($query) ?>" placeholder="Search by title, description, URL, or category path">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </div>
        </form>

        <?php if ($query !== '' && mb_strlen($query) < 2): ?>
            <div class="alert alert-warning">Please enter at least 2 characters.</div>
        <?php endif; ?>

        <?php if ($query !== '' && $pagination): ?>
            <p class="text-muted">Found <?= (int) $pagination['total'] ?> result<?= $pagination['total'] === 1 ? '' : 's' ?> for <strong><?= e($query) ?></strong>.</p>
        <?php endif; ?>

        <?php foreach ($results as $result): ?>
            <div class="card site-card mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-1"><a href="<?= e(is_text_entry($result) ? entry_url($result) : $result['url']) ?>"<?= is_text_entry($result) ? "" : " target=\"_blank\" rel=\"noopener noreferrer\"" ?>><?= e($result['title']) ?></a></h2>
                    <div class="small text-muted mb-2">
                        <a href="<?= e(base_url('/category/' . $result['category_path'])) ?>"><?= e($result['category_path']) ?></a>
                        <?php if (is_text_entry($result)): ?>
                            &middot; Text archive
                        <?php else: ?>
                            &middot; <?= e($result['url']) ?>
                        <?php endif; ?>
                    </div>
                    <p class="mb-0"><?= e($result['description']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>

        <?php
            $path = '/search';
            $query = ['q' => $query];
            require __DIR__ . '/../layouts/pagination.php';
        ?>
    </div>
</div>
