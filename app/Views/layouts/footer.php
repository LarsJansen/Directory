</main>

<footer class="border-top bg-white mt-auto py-4">
    <div class="container d-md-flex justify-content-between align-items-start gap-4">
        <div>
            <h2 class="h6 mb-2"><?= e(config('name')) ?></h2>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
