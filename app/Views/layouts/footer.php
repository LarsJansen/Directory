</main>

<footer class="border-top bg-white mt-auto py-4">
    <div class="container d-md-flex justify-content-between align-items-start gap-4">
        <div>
            <h6 class="mb-2"><?= e(config('name')) ?></h6>
            <p class="text-muted mb-0 small">Human-curated. Historically focused. Built link by link.</p>
        </div>
        <div class="footer-links small text-muted">
            <div class="mb-1">&copy; <?= date('Y') ?> <?= e(config('name')) ?></div>
            <div class="d-flex gap-3 flex-wrap">
                <a href="<?= e(base_url('/')) ?>">Home</a>
                <a href="<?= e(base_url('/category')) ?>">Browse</a>
                <a href="<?= e(base_url('/submit')) ?>">Submit</a>
                <a href="<?= e(base_url('/editor')) ?>">Editor</a>
            </div>
        </div>
    </div>
</footer>

<script src="<?= e(base_url('/assets/js/bootstrap.bundle.min.js')) ?>" defer></script>
</body>
</html>
