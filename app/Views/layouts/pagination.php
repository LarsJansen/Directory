<?php
if (empty($pagination) || ($pagination['total_pages'] ?? 1) <= 1) {
    return;
}

$path = isset($path) && is_string($path) && $path !== '' ? $path : request_path();

if (isset($paginationQuery) && is_array($paginationQuery)) {
    $query = $paginationQuery;
} elseif (!isset($query) || !is_array($query)) {
    $query = [];
}

$currentPage = (int) ($pagination['page'] ?? 1);
$totalPages = (int) ($pagination['total_pages'] ?? 1);

$pages = [];

if ($totalPages <= 7) {
    for ($i = 1; $i <= $totalPages; $i++) {
        $pages[] = $i;
    }
} else {
    $pages = [1, 2];

    $start = max(3, $currentPage - 1);
    $end = min($totalPages - 2, $currentPage + 1);

    for ($i = $start; $i <= $end; $i++) {
        $pages[] = $i;
    }

    $pages[] = $totalPages - 1;
    $pages[] = $totalPages;

    $pages = array_values(array_unique(array_filter($pages, static fn ($page) => $page >= 1 && $page <= $totalPages)));
    sort($pages);
}
?>
<nav aria-label="Pagination" class="mt-4">
    <ul class="pagination flex-wrap">
        <li class="page-item<?= !($pagination['has_prev'] ?? false) ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e(page_url($path, array_merge($query, ['page' => max(1, $currentPage - 1)]))) ?>">Previous</a>
        </li>

        <?php $previousRenderedPage = null; ?>
        <?php foreach ($pages as $pageNumber): ?>
            <?php if ($previousRenderedPage !== null && $pageNumber > $previousRenderedPage + 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>

            <li class="page-item<?= $pageNumber === $currentPage ? ' active' : '' ?>">
                <a class="page-link" href="<?= e(page_url($path, array_merge($query, ['page' => $pageNumber]))) ?>"><?= $pageNumber ?></a>
            </li>

            <?php $previousRenderedPage = $pageNumber; ?>
        <?php endforeach; ?>

        <li class="page-item<?= !($pagination['has_next'] ?? false) ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e(page_url($path, array_merge($query, ['page' => min($totalPages, $currentPage + 1)]))) ?>">Next</a>
        </li>
    </ul>
</nav>
