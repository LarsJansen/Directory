<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Editor Dashboard</h1>
        <p class="text-muted mb-0">Moderation overview for the current directory MVP.</p>
    </div>
    <form action="/editor/logout" method="post">
        <button class="btn btn-outline-secondary">Log out</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Pending</div><div class="display-6"><?= (int) $counts['pending'] ?></div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Approved</div><div class="display-6"><?= (int) $counts['approved'] ?></div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Rejected</div><div class="display-6"><?= (int) $counts['rejected'] ?></div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Live listings</div><div class="display-6"><?= (int) $siteCount ?></div></div></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5">Quick actions</h2>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-primary" href="/editor/submissions">Review pending submissions</a>
            <a class="btn btn-outline-secondary" href="/editor/categories">Manage categories</a>
            <a class="btn btn-outline-secondary" href="/editor/sites">Manage sites</a>
        </div>
    </div>
</div>
