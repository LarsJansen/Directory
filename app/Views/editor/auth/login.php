<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-3">Editor Login</h1>
                <form action="/editor/login" method="post">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Log in</button>
                </form>
            </div>
        </div>
    </div>
</div>
