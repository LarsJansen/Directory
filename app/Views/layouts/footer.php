</div>
<footer class="bg-white border-top mt-5 py-4">
    <div class="container small text-muted">
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="fw-semibold text-dark"><?= e(config('name')) ?></div>
                <div>Curated web directory experiment inspired by the early web.</div>
            </div>
            <div class="col-md-6 text-md-end">
                <div>&copy; <?= date('Y') ?> <?= e(config('name')) ?>.</div>
                <div class="mt-1">
                    <a class="text-decoration-none me-3" href="<?= e(base_url('/')) ?>">Home</a>
                    <a class="text-decoration-none me-3" href="<?= e(base_url('/category')) ?>">Browse</a>
                    <a class="text-decoration-none me-3" href="<?= e(base_url('/submit')) ?>">Submit</a>
                    <a class="text-decoration-none" href="<?= e(base_url('/editor')) ?>">Editor</a>
                </div>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
