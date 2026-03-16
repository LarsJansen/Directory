<?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
<nav aria-label="Pagination" class="mt-4">
    <ul class="pagination">
        <?php $query = $query ?? []; ?>
        <li class="page-item<?= !$pagination['has_prev'] ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e(page_url($path, array_merge($query, ['page' => max(1, $pagination['page'] - 1)]))) ?>">Previous</a>
        </li>
        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
            <li class="page-item<?= $i === $pagination['page'] ? ' active' : '' ?>">
                <a class="page-link" href="<?= e(page_url($path, array_merge($query, ['page' => $i]))) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item<?= !$pagination['has_next'] ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e(page_url($path, array_merge($query, ['page' => min($pagination['total_pages'], $pagination['page'] + 1)]))) ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
