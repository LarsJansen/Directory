<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h3 mb-3">Editor login</h1>
                <form method="post" action="<?= e(base_url('/editor/login')) ?>" class="row g-3">
                    <?= csrf_input() ?>
                    <div class="col-12">
                        <label class="form-label">Username</label>
                        <input class="form-control" type="text" name="username" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn-primary" type="submit">Log in</button>
                    </div>
                </form>
                <div class="small text-muted mt-3">Default dev login: admin / password123</div>
            </div>
        </div>
    </div>
</div>
