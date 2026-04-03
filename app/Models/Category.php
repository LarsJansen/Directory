<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;
use PDO;
use Throwable;

/**
 * Category data access and hierarchy helpers.
 */
class Category
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }


    protected function invalidateDirectoryCache(): void
    {
        cache_bump('directory-content');
    }

    /**
     * Fetch categories with direct child counts and direct site counts.
     *
     * This uses correlated subqueries rather than derived-table joins because
     * the query shape is simpler and more reliable across local MySQL setups.
     */
    protected function fetchCategoriesWithCounts(string $whereSql, array $params = []): array
    {
        return $this->db->fetchAll(
            "SELECT
                c.*,
                (
                    SELECT COUNT(*)
                    FROM categories cc
                    WHERE cc.parent_id = c.id
                      AND cc.is_active = 1
                ) AS child_count,
                (
                    SELECT COUNT(*)
                    FROM sites s
                    WHERE s.category_id = c.id
                      AND s.is_active = 1
                ) AS site_count
             FROM categories c
             {$whereSql}
             ORDER BY c.sort_order ASC, c.name ASC",
            $params
        );
    }

    public function topLevel(): array
    {
        return $this->fetchCategoriesWithCounts(
            'WHERE c.parent_id IS NULL AND c.is_active = 1'
        );
    }

    /**
     * Build the homepage category listing with rolled-up site totals.
     *
     * Direct counts are fetched once, then totals are accumulated in memory so
     * the homepage does not need one count query per category.
     */
    public function homeDirectoryIndex(int $featuredChildrenLimit = 5): array
    {
        $featuredChildrenLimit = max(1, $featuredChildrenLimit);

        $categories = $this->db->fetchAll(
            "SELECT
                id,
                parent_id,
                name,
                path,
                description,
                sort_order
             FROM categories
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC"
        );

        if (!$categories) {
            return [];
        }

        $directSiteCounts = $this->db->fetchAll(
            "SELECT
                category_id,
                COUNT(*) AS total_site_count
             FROM sites
             WHERE is_active = 1
             GROUP BY category_id"
        );

        $directCountMap = [];
        foreach ($directSiteCounts as $row) {
            $directCountMap[(int) $row['category_id']] = (int) $row['total_site_count'];
        }

        $nodesById = [];
        $childrenByParent = [];
        $roots = [];

        foreach ($categories as $category) {
            $id = (int) $category['id'];
            $parentId = $category['parent_id'] !== null ? (int) $category['parent_id'] : null;

            $category['id'] = $id;
            $category['parent_id'] = $parentId;
            $category['direct_site_count'] = $directCountMap[$id] ?? 0;
            $category['total_site_count'] = $category['direct_site_count'];

            $nodesById[$id] = $category;
            $childrenByParent[$parentId ?? 0][] = $id;

            if ($parentId === null) {
                $roots[] = $id;
            }
        }

        $accumulateTotals = function (int $categoryId) use (&$accumulateTotals, &$nodesById, &$childrenByParent): int {
            $total = (int) ($nodesById[$categoryId]['direct_site_count'] ?? 0);

            foreach ($childrenByParent[$categoryId] ?? [] as $childId) {
                $total += $accumulateTotals($childId);
            }

            $nodesById[$categoryId]['total_site_count'] = $total;
            return $total;
        };

        foreach ($roots as $rootId) {
            $accumulateTotals($rootId);
        }

        $result = [];

        foreach ($roots as $rootId) {
            $root = $nodesById[$rootId];
            $rootChildren = [];

            foreach ($childrenByParent[$rootId] ?? [] as $childId) {
                $rootChildren[] = [
                    'id' => $nodesById[$childId]['id'],
                    'parent_id' => $nodesById[$childId]['parent_id'],
                    'name' => $nodesById[$childId]['name'],
                    'path' => $nodesById[$childId]['path'],
                    'sort_order' => $nodesById[$childId]['sort_order'],
                    'total_site_count' => $nodesById[$childId]['total_site_count'],
                ];
            }

            usort($rootChildren, static function (array $a, array $b): int {
                $countCompare = ($b['total_site_count'] <=> $a['total_site_count']);
                if ($countCompare !== 0) {
                    return $countCompare;
                }

                $sortCompare = ((int) $a['sort_order']) <=> ((int) $b['sort_order']);
                if ($sortCompare !== 0) {
                    return $sortCompare;
                }

                return strcasecmp((string) $a['name'], (string) $b['name']);
            });

            $root['featured_children'] = array_slice($rootChildren, 0, $featuredChildrenLimit);
            $root['has_more_children'] = count($rootChildren) > $featuredChildrenLimit;
            $result[] = $root;
        }

        return $result;
    }

    public function browseIndexData(): array
    {
        $roots = $this->topLevel();

        if (!$roots) {
            return [];
        }

        $rootIds = array_map(static fn (array $row): int => (int) $row['id'], $roots);

        $children = $this->fetchCategoriesWithCounts(
            'WHERE c.parent_id IN (' . implode(',', array_fill(0, count($rootIds), '?')) . ') AND c.is_active = 1',
            $rootIds
        );

        $childrenByParent = [];
        foreach ($children as $child) {
            $childrenByParent[(int) $child['parent_id']][] = $child;
        }

        foreach ($roots as &$root) {
            $root['children'] = $childrenByParent[(int) $root['id']] ?? [];
        }
        unset($root);

        return $roots;
    }

    public function indexRoots(): array
    {
        return $this->topLevel();
    }

    public function allActive(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM categories WHERE is_active = 1 ORDER BY path ASC'
        );
    }

    public function allForEditor(): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id
             ORDER BY c.path ASC'
        );
    }

    public function searchForEditor(string $query = '', int $page = 1, int $perPage = 50): array
    {
        $query = trim($query);
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $whereSql = '';
        $params = [];

        if ($query !== '') {
            $whereSql = ' WHERE c.path LIKE ? OR c.name LIKE ? OR COALESCE(p.name, \'\') LIKE ?';
            $like = '%' . $query . '%';
            $params = [$like, $like, $like];
        }

        $total = (int) $this->db->fetchValue(
            'SELECT COUNT(*)
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id' . $whereSql,
            $params
        );

        $pagination = build_pagination($total, $page, $perPage);

        $rows = $this->db->fetchAll(
            'SELECT c.*, p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id' . $whereSql . '
             ORDER BY c.path ASC
             LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
            $params
        );

        return [
            'rows' => $rows,
            'total' => $total,
            'pagination' => $pagination,
        ];
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM categories WHERE id = ?',
            [$id]
        ) ?: null;
    }

    public function findById(int $id): ?array
    {
        return $this->find($id);
    }

    public function findByPath(string $path): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM categories WHERE path = ? AND is_active = 1',
            [trim($path, '/')]
        ) ?: null;
    }

    public function children(int $parentId): array
    {
        return $this->childrenOf($parentId, true);
    }

    public function childrenOf(int $parentId, bool $activeOnly = true): array
    {
        $whereSql = 'WHERE c.parent_id = ?';
        $params = [$parentId];

        if ($activeOnly) {
            $whereSql .= ' AND c.is_active = 1';
        }

        return $this->fetchCategoriesWithCounts($whereSql, $params);
    }

    public function breadcrumbByPath(string $path): array
    {
        return $this->breadcrumbsByPath($path);
    }

    public function breadcrumbsByPath(string $path): array
    {
        $parts = array_filter(explode('/', trim($path, '/')));
        $breadcrumbs = [];
        $running = [];

        foreach ($parts as $part) {
            $running[] = $part;
            $category = $this->findByPath(implode('/', $running));
            if ($category) {
                $breadcrumbs[] = $category;
            }
        }

        return $breadcrumbs;
    }

    public function allMoveTargetsFor(int $categoryId): array
    {
        $category = $this->find($categoryId);
        if (!$category) {
            return [];
        }

        return $this->db->fetchAll(
            'SELECT id, name, path
             FROM categories
             WHERE is_active = 1
               AND id != ?
               AND path != ?
               AND path NOT LIKE ?
             ORDER BY path ASC',
            [
                $categoryId,
                $category['path'],
                $category['path'] . '/%',
            ]
        );
    }

    public function allForParentSelect(?int $excludeId = null): array
    {
        $sql = 'SELECT id, name, path FROM categories WHERE is_active = 1';
        $params = [];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $sql .= ' ORDER BY path ASC';

        return $this->db->fetchAll($sql, $params);
    }

    public function existsByPath(string $path, ?int $ignoreId = null): bool
    {
        return !$this->isPathAvailable($path, $ignoreId);
    }

    public function buildPath(?int $parentId, string $slug): string
    {
        return $this->buildBasePath($slug, $parentId);
    }

    public function create(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Category name is required.');
        }

        $parentId = $this->normalizeParentId($data['parent_id'] ?? null);
        $slug = trim((string) ($data['slug'] ?? '')) ?: $this->slugify($name);
        $description = trim((string) ($data['description'] ?? ''));
        $sortOrder = (int) ($data['sort_order'] ?? 0);
        $isActive = isset($data['is_active']) ? (int) !!$data['is_active'] : 1;
        $path = trim((string) ($data['path'] ?? ''));
        $path = $path !== '' ? $path : $this->buildUniquePath($slug, $parentId, null);

        $this->db->query(
            'INSERT INTO categories (parent_id, slug, path, name, description, sort_order, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $parentId,
                $slug,
                $path,
                $name,
                $description !== '' ? $description : null,
                $sortOrder,
                $isActive,
            ]
        );

        $this->invalidateDirectoryCache();

        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): array
    {
        return $this->updateCategory($id, $data);
    }

    public function updateCategory(int $id, array $data): array
    {
        $existing = $this->find($id);
        if (!$existing) {
            throw new RuntimeException('Category not found.');
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Category name is required.');
        }

        $parentId = $this->normalizeParentId($data['parent_id'] ?? null);
        $description = trim((string) ($data['description'] ?? ''));
        $sortOrder = (int) ($data['sort_order'] ?? 0);
        $isActive = isset($data['is_active']) ? (int) !!$data['is_active'] : (int) $existing['is_active'];
        $slug = trim((string) ($data['slug'] ?? '')) ?: $this->slugify($name);

        if ($parentId === $id) {
            throw new RuntimeException('A category cannot be its own parent.');
        }

        if ($parentId !== null && $this->isDescendant($parentId, $id)) {
            throw new RuntimeException('Cannot move a category under one of its descendants.');
        }

        $newPath = trim((string) ($data['path'] ?? ''));
        $newPath = $newPath !== '' ? $newPath : $this->buildUniquePath($slug, $parentId, $id);
        $oldPath = (string) $existing['path'];

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE categories
                 SET parent_id = ?, slug = ?, path = ?, name = ?, description = ?, sort_order = ?, is_active = ?, updated_at = NOW()
                 WHERE id = ?'
            );

            $stmt->execute([
                $parentId,
                $slug,
                $newPath,
                $name,
                $description !== '' ? $description : null,
                $sortOrder,
                $isActive,
                $id,
            ]);

            if ($oldPath !== $newPath) {
                $this->rebuildDescendantPaths($pdo, $oldPath, $newPath);
            }

            $pdo->commit();
            $this->invalidateDirectoryCache();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return [
            'old' => $existing,
            'new' => $this->find($id),
        ];
    }

    public function branchSummary(int $id): array
    {
        $category = $this->find($id);
        if (!$category) {
            throw new RuntimeException('Category not found.');
        }

        $path = trim((string) $category['path'], '/');
        $branchPattern = $path . '/%';

        return [
            'descendant_count' => (int) $this->db->fetchValue(
                'SELECT COUNT(*)
                 FROM categories
                 WHERE path LIKE ?',
                [$branchPattern]
            ),
            'site_count_in_branch' => (int) $this->db->fetchValue(
                'SELECT COUNT(*)
                 FROM sites s
                 INNER JOIN categories c ON c.id = s.category_id
                 WHERE c.path = ? OR c.path LIKE ?',
                [$path, $branchPattern]
            ),
        ];
    }


    public function allMergeTargetsFor(int $categoryId): array
    {
        return $this->allMoveTargetsFor($categoryId);
    }

    public function mergeSummary(int $id): array
    {
        $category = $this->find($id);
        if (!$category) {
            throw new RuntimeException('Category not found.');
        }

        $path = trim((string) $category['path'], '/');
        $branchPattern = $path . '/%';

        return [
            'category' => $category,
            'direct_child_count' => (int) $this->db->fetchValue(
                'SELECT COUNT(*) FROM categories WHERE parent_id = ?',
                [$id]
            ),
            'descendant_count' => (int) $this->db->fetchValue(
                'SELECT COUNT(*) FROM categories WHERE path LIKE ?',
                [$branchPattern]
            ),
            'direct_site_count' => (int) $this->db->fetchValue(
                'SELECT COUNT(*) FROM sites WHERE category_id = ?',
                [$id]
            ),
            'site_count_in_branch' => (int) $this->db->fetchValue(
                'SELECT COUNT(*)
                 FROM sites s
                 INNER JOIN categories c ON c.id = s.category_id
                 WHERE c.path = ? OR c.path LIKE ?',
                [$path, $branchPattern]
            ),
        ];
    }

    public function previewMerge(int $sourceId, int $targetId): array
    {
        $source = $this->find($sourceId);
        $target = $this->find($targetId);

        if (!$source || !$target) {
            throw new RuntimeException('Source or target category was not found.');
        }

        if ($sourceId === $targetId) {
            throw new RuntimeException('A category cannot be merged into itself.');
        }

        if ($this->isDescendant($targetId, $sourceId)) {
            throw new RuntimeException('Cannot merge a category into one of its own descendants.');
        }

        $conflicts = [];
        $children = $this->db->fetchAll(
            'SELECT id, name, slug, path FROM categories WHERE parent_id = ? ORDER BY path ASC',
            [$sourceId]
        );

        foreach ($children as $child) {
            $targetChildPath = $this->buildBasePath((string) $child['slug'], $targetId);
            if (!$this->isPathAvailable($targetChildPath, (int) $child['id'])) {
                $conflicts[] = [
                    'child_id' => (int) $child['id'],
                    'child_path' => $child['path'],
                    'target_path' => $targetChildPath,
                ];
            }
        }

        if (!empty($conflicts)) {
            throw new RuntimeException('Merge blocked because one or more child category paths would collide in the target branch.');
        }

        return [
            'source' => $source,
            'target' => $target,
            'summary' => $this->mergeSummary($sourceId),
            'conflicts' => $conflicts,
        ];
    }

    public function mergeInto(int $sourceId, int $targetId): array
    {
        $preview = $this->previewMerge($sourceId, $targetId);
        $source = $preview['source'];
        $target = $preview['target'];
        $summary = $preview['summary'];

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $updateSites = $pdo->prepare(
                'UPDATE sites SET category_id = ?, updated_at = NOW() WHERE category_id = ?'
            );
            $updateSites->execute([$targetId, $sourceId]);

            $children = $this->db->fetchAll(
                'SELECT id, slug, path FROM categories WHERE parent_id = ? ORDER BY LENGTH(path) ASC, path ASC',
                [$sourceId]
            );

            $updateChild = $pdo->prepare(
                'UPDATE categories SET parent_id = ?, path = ?, updated_at = NOW() WHERE id = ?'
            );

            foreach ($children as $child) {
                $oldChildPath = (string) $child['path'];
                $newChildPath = $this->buildBasePath((string) $child['slug'], $targetId);

                $updateChild->execute([$targetId, $newChildPath, (int) $child['id']]);

                if ($oldChildPath !== $newChildPath) {
                    $this->rebuildDescendantPaths($pdo, $oldChildPath, $newChildPath);
                }
            }

            $deleteSource = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $deleteSource->execute([$sourceId]);

            $pdo->commit();
            $this->invalidateDirectoryCache();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return [
            'source' => $source,
            'target' => $this->find($targetId) ?? $target,
            'summary' => $summary,
        ];
    }

    public function deleteSummary(int $id): array
    {
        $category = $this->find($id);
        if (!$category) {
            throw new RuntimeException('Category not found.');
        }

        $path = trim((string) $category['path'], '/');
        $branchPattern = $path . '/%';
        $parent = !empty($category['parent_id']) ? $this->find((int) $category['parent_id']) : null;

        $directChildCount = (int) $this->db->fetchValue(
            'SELECT COUNT(*) FROM categories WHERE parent_id = ?',
            [$id]
        );

        $descendantCount = (int) $this->db->fetchValue(
            'SELECT COUNT(*) FROM categories WHERE path LIKE ?',
            [$branchPattern]
        );

        $directSiteCount = (int) $this->db->fetchValue(
            'SELECT COUNT(*) FROM sites WHERE category_id = ?',
            [$id]
        );

        $siteCountInBranch = (int) $this->db->fetchValue(
            'SELECT COUNT(*)
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE c.path = ? OR c.path LIKE ?',
            [$path, $branchPattern]
        );

        return [
            'category' => $category,
            'parent' => $parent,
            'direct_child_count' => $directChildCount,
            'descendant_count' => $descendantCount,
            'direct_site_count' => $directSiteCount,
            'site_count_in_branch' => $siteCountInBranch,
            'can_delete_empty' => $directChildCount === 0 && $directSiteCount === 0,
            'can_move_sites_to_parent' => $directChildCount === 0 && $directSiteCount > 0 && $parent !== null,
            'can_delete_branch' => true,
        ];
    }

    public function deleteCategory(int $id, string $mode): array
    {
        $category = $this->find($id);
        if (!$category) {
            throw new RuntimeException('Category not found.');
        }

        $summary = $this->deleteSummary($id);
        $mode = trim($mode);
        $allowedModes = ['empty', 'move_sites_to_parent', 'delete_branch'];
        if (!in_array($mode, $allowedModes, true)) {
            throw new RuntimeException('Invalid delete mode selected.');
        }

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $result = match ($mode) {
                'empty' => $this->performEmptyDelete($pdo, $category, $summary),
                'move_sites_to_parent' => $this->performDeleteMoveSitesToParent($pdo, $category, $summary),
                'delete_branch' => $this->performDeleteBranch($pdo, $category, $summary),
            };

            $pdo->commit();
            $this->invalidateDirectoryCache();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function previewMove(int $id, ?int $newParentId): array
    {
        $existing = $this->find($id);
        if (!$existing) {
            throw new RuntimeException('Category not found.');
        }

        if ($newParentId === $id) {
            throw new RuntimeException('A category cannot be its own parent.');
        }

        if ($newParentId !== null && $this->isDescendant($newParentId, $id)) {
            throw new RuntimeException('Cannot move a category under one of its descendants.');
        }

        $newPath = $this->buildBasePath((string) $existing['slug'], $newParentId);
        if (!$this->isPathAvailable($newPath, $id)) {
            throw new RuntimeException('Another category already uses the target path ' . $newPath . '.');
        }

        $newParent = $newParentId !== null ? $this->find($newParentId) : null;

        return [
            'old_path' => $existing['path'],
            'new_path' => $newPath,
            'new_parent' => $newParent,
        ];
    }

    public function moveBranch(int $id, ?int $newParentId): array
    {
        $existing = $this->find($id);
        if (!$existing) {
            throw new RuntimeException('Category not found.');
        }

        $summary = $this->branchSummary($id);
        $preview = $this->previewMove($id, $newParentId);
        $newPath = $preview['new_path'];
        $oldPath = (string) $existing['path'];

        if ($oldPath === $newPath && (int) ($existing['parent_id'] ?? 0) === (int) ($newParentId ?? 0)) {
            throw new RuntimeException('That branch is already in the selected location.');
        }

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE categories
                 SET parent_id = ?, path = ?, updated_at = NOW()
                 WHERE id = ?'
            );

            $stmt->execute([
                $newParentId,
                $newPath,
                $id,
            ]);

            if ($oldPath !== $newPath) {
                $this->rebuildDescendantPaths($pdo, $oldPath, $newPath);
            }

            $pdo->commit();
            $this->invalidateDirectoryCache();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return [
            'old' => $existing,
            'new' => $this->find($id),
            'summary' => $summary,
        ];
    }

    public function isDescendant(int $potentialParentId, int $categoryId): bool
    {
        $category = $this->find($categoryId);
        $potentialParent = $this->find($potentialParentId);

        if (!$category || !$potentialParent) {
            return false;
        }

        $categoryPath = trim((string) $category['path'], '/');
        $parentPath = trim((string) $potentialParent['path'], '/');

        return $parentPath === $categoryPath
            || str_starts_with($parentPath, $categoryPath . '/');
    }

    protected function performEmptyDelete(PDO $pdo, array $category, array $summary): array
    {
        if ((int) $summary['direct_child_count'] > 0) {
            throw new RuntimeException('This category has child categories. Move or delete them first, or use Delete entire branch.');
        }

        if ((int) $summary['direct_site_count'] > 0) {
            throw new RuntimeException('This category has sites. Choose Delete and move sites to parent, or use Delete entire branch.');
        }

        $this->deleteSingleCategory($pdo, (int) $category['id']);

        return [
            'mode' => 'empty',
            'category' => $category,
            'summary' => $summary,
            'moved_site_count' => 0,
            'deleted_category_count' => 1,
            'deleted_site_count' => 0,
        ];
    }

    protected function performDeleteMoveSitesToParent(PDO $pdo, array $category, array $summary): array
    {
        if ((int) $summary['direct_child_count'] > 0) {
            throw new RuntimeException('This category has child categories. Move the branch first or use Delete entire branch.');
        }

        $parentId = $category['parent_id'] !== null ? (int) $category['parent_id'] : null;
        if ($parentId === null) {
            throw new RuntimeException('Top-level categories cannot move sites to a parent because no parent exists.');
        }

        $movedSiteCount = (int) $summary['direct_site_count'];
        if ($movedSiteCount <= 0) {
            throw new RuntimeException('This category has no sites to move. Use the standard delete action instead.');
        }

        $stmt = $pdo->prepare('UPDATE sites SET category_id = ?, updated_at = NOW() WHERE category_id = ?');
        $stmt->execute([$parentId, (int) $category['id']]);

        $this->deleteSingleCategory($pdo, (int) $category['id']);

        return [
            'mode' => 'move_sites_to_parent',
            'category' => $category,
            'summary' => $summary,
            'moved_site_count' => $movedSiteCount,
            'deleted_category_count' => 1,
            'deleted_site_count' => 0,
        ];
    }

    protected function performDeleteBranch(PDO $pdo, array $category, array $summary): array
    {
        $path = trim((string) $category['path'], '/');
        $rows = $this->db->fetchAll(
            'SELECT id, path FROM categories WHERE path = ? OR path LIKE ? ORDER BY LENGTH(path) DESC, path DESC',
            [$path, $path . '/%']
        );

        $deletedCategoryCount = count($rows);
        $deletedSiteCount = (int) $summary['site_count_in_branch'];

        $deleteCategory = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        foreach ($rows as $row) {
            $deleteCategory->execute([(int) $row['id']]);
        }

        return [
            'mode' => 'delete_branch',
            'category' => $category,
            'summary' => $summary,
            'moved_site_count' => 0,
            'deleted_category_count' => $deletedCategoryCount,
            'deleted_site_count' => $deletedSiteCount,
        ];
    }

    protected function deleteSingleCategory(PDO $pdo, int $categoryId): void
    {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
    }

    protected function rebuildDescendantPaths(PDO $pdo, string $oldPath, string $newPath): void
    {
        $select = $pdo->prepare(
            'SELECT id, path FROM categories WHERE path LIKE ? ORDER BY LENGTH(path) ASC'
        );
        $select->execute([$oldPath . '/%']);
        $rows = $select->fetchAll(PDO::FETCH_ASSOC);

        $update = $pdo->prepare('UPDATE categories SET path = ?, updated_at = NOW() WHERE id = ?');

        foreach ($rows as $row) {
            $updatedPath = preg_replace(
                '~^' . preg_quote($oldPath, '~') . '~',
                $newPath,
                (string) $row['path'],
                1
            );

            if ($updatedPath === null) {
                continue;
            }

            $update->execute([$updatedPath, $row['id']]);
        }
    }

    protected function normalizeParentId($parentId): ?int
    {
        if ($parentId === '' || $parentId === null) {
            return null;
        }

        $parentId = (int) $parentId;
        if ($parentId <= 0) {
            return null;
        }

        $parent = $this->find($parentId);
        if (!$parent) {
            throw new RuntimeException('Selected parent category does not exist.');
        }

        return $parentId;
    }

    protected function buildUniquePath(string $slug, ?int $parentId, ?int $ignoreId): string
    {
        $basePath = $this->buildBasePath($slug, $parentId);
        $candidate = $basePath;
        $suffix = 2;

        while (!$this->isPathAvailable($candidate, $ignoreId)) {
            $candidate = $basePath . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function buildBasePath(string $slug, ?int $parentId): string
    {
        if ($parentId === null) {
            return $slug;
        }

        $parent = $this->find($parentId);
        if (!$parent) {
            throw new RuntimeException('Parent category not found.');
        }

        return trim((string) $parent['path'], '/') . '/' . $slug;
    }

    protected function isPathAvailable(string $path, ?int $ignoreId): bool
    {
        if ($ignoreId !== null) {
            $row = $this->db->fetch(
                'SELECT id FROM categories WHERE path = ? AND id != ? LIMIT 1',
                [$path, $ignoreId]
            );
        } else {
            $row = $this->db->fetch(
                'SELECT id FROM categories WHERE path = ? LIMIT 1',
                [$path]
            );
        }

        return $row === null;
    }

    protected function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim((string) $text, '-');
        return $text !== '' ? $text : 'category';
    }
}
