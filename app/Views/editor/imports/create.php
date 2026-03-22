<div class="row g-4">
    <div class="col-lg-3"><?php require __DIR__ . '/../../layouts/editor_nav.php'; ?></div>
    <div class="col-lg-9">
        <h1 class="h3 mb-3">Create import batch</h1>
        <div class="card"><div class="card-body">
            <form method="post" action="<?= e(base_url('/editor/imports')) ?>" class="row g-3">
                <?= csrf_input() ?>
                <div class="col-md-6"><label class="form-label">Source name</label><input class="form-control" type="text" name="source_name" value="DMOZ Sample"></div>
                <div class="col-md-6"><label class="form-label">Source version</label><input class="form-control" type="text" name="source_version" placeholder="optional"></div>
                <div class="col-12"><label class="form-label">Batch label</label><input class="form-control" type="text" name="batch_label" required placeholder="Example: DMOZ Computers sample set"></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="5" placeholder="Describe what this batch contains and how it should be reviewed."></textarea></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Create batch</button></div>
            </form>
        </div></div>
    </div>
</div>
