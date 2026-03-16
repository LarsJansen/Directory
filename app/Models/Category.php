<?php

namespace App\Models;

use App\Core\Database;
use RuntimeException;
use PDO;
use Throwable;

class Category
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Top-level categories for home page / category index.
     * Includes child_count and site_count for category index view.
     */
    public function topLevel(): array
    {
        return $this->db->fetchAll(
            "
            SELECT
                c.*,
                (
                    SELECT COUNT(*)
                    FROM categories c2
                    WHERE c2.parent_id = c.id
                      AND c2.is_active = 1
                ) AS child_count,
                (
                    SELECT COUNT(*)
                    FROM sites s
                    WHERE s.category_id = c.id
                      AND s.is_active = 1
                ) AS site_count
            FROM categories c
            WHERE c.parent_id IS NULL
              AND c.is_active = 1
            ORDER BY c.sort_order ASC, c.name ASC
            "
        );
    }

    /**
     * Alias for compatibility.
     */
    public function indexRoots(): array
    {
        return $this->topLevel();
    }

    /**
     * Find category by ID.
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT *
             FROM categories
             WHERE id = ?",
            [$id]
        ) ?: null;
    }

    /**
     * Find active category by path.
     */
    public function findByPath(string $path): ?array
    {
        return $this->db->fetch(
            "SELECT *
             FROM categories
             WHERE path = ?
               AND is_active = 1",
            [trim($path, '/')]
        ) ?: null;
    }

    /**
     * Active child categories.
     */
    public function children(int $parentId): array
    {
        return $this->childrenOf($parentId, true);
    }

    /**
     * Child categories, optionally including inactive.
     * Includes site_count and child_count for category listing views.
     */
    public function childrenOf(int $parentId, bool $activeOnly = true): array
    {
        $sql = "
            SELECT
                c.*,
                (
                    SELECT COUNT(*)
                    FROM categories c2
                    WHERE c2.parent_id = c.id
                      AND c2.is_active = 1
                ) AS child_count,
                (
                    SELECT COUNT(*)
                    FROM sites s
                    WHERE s.category_id = c.id
                      AND s.is_active = 1
                ) AS site_count
            FROM categories c
            WHERE c.parent_id = ?
        ";

        $params = [$parentId];

        if ($activeOnly) {
            $sql .= " AND c.is_active = 1";
        }

        $sql .= " ORDER BY c.sort_order ASC, c.name ASC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Breadcrumb builder used by CategoryController.
     */
    public function breadcrumbByPath(string $path): array
    {
        return $this->breadcrumbsByPath($path);
    }

    /**
     * Breadcrumb builder.
     */
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

    /**
     * Categories for parent dropdown.
     * Excludes the current category if provided.
     */
    public function allForParentSelect(?int $excludeId = null): array
    {
        $sql = "SELECT id, name, path
                FROM categories
                WHERE is_active = 1";
        $params = [];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $sql .= " ORDER BY path ASC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Create a category.
     */
    public function create(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Category name is required.');
        }

        $parentId = $this->normalizeParentId($data['parent_id'] ?? null);
        $description = trim((string) ($data['description'] ?? ''));
        $sortOrder = (int) ($data['sort_order'] ?? 0);
        $isActive = isset($data['is_active']) ? (int) !!$data['is_active'] : 1;

        $slug = $this->slugify($name);
        $path = $this->buildUniquePath($slug, $parentId, null);

        $this->db->query(
            "INSERT INTO categories (name, parent_id, path, description, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $name,
                $parentId,
                $path,
                $description !== '' ? $description : null,
                $sortOrder,
                $isActive,
            ]
        );

        return $this->db->lastInsertId();
    }

    /**
     * Update category safely, rebuilding descendant paths if needed.
     */
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

        if ($parentId === $id) {
            throw new RuntimeException('A category cannot be its own parent.');
        }

        if ($parentId !== null && $this->isDescendant($parentId, $id)) {
            throw new RuntimeException('Cannot move a category under one of its descendants.');
        }

        $slug = $this->slugify($name);
        $newPath = $this->buildUniquePath($slug, $parentId, $id);
        $oldPath = (string) $existing['path'];

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "UPDATE categories
                 SET name = ?, parent_id = ?, path = ?, description = ?, sort_order = ?, is_active = ?
                 WHERE id = ?"
            );

            $stmt->execute([
                $name,
                $parentId,
                $newPath,
                $description !== '' ? $description : null,
                $sortOrder,
                $isActive,
                $id,
            ]);

            if ($oldPath !== $newPath) {
                $this->rebuildDescendantPaths($pdo, $oldPath, $newPath);
            }

            $pdo->commit();
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

    /**
     * Check whether a proposed parent is inside this category's own subtree.
     */
    protected function isDescendant(int $potentialParentId, int $categoryId): bool
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

    /**
     * Rebuild all descendant paths after a move/rename.
     */
    protected function rebuildDescendantPaths(PDO $pdo, string $oldPath, string $newPath): void
    {
        $select = $pdo->prepare(
            "SELECT id, path
             FROM categories
             WHERE path LIKE ?
             ORDER BY LENGTH(path) ASC"
        );
        $select->execute([$oldPath . '/%']);
        $rows = $select->fetchAll(PDO::FETCH_ASSOC);

        $update = $pdo->prepare(
            "UPDATE categories
             SET path = ?
             WHERE id = ?"
        );

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

    /**
     * Validate/normalize parent ID.
     */
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

    /**
     * Build a unique path under a parent.
     */
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

    /**
     * Build non-unique base path.
     */
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

    /**
     * Check path uniqueness.
     */
    protected function isPathAvailable(string $path, ?int $ignoreId): bool
    {
        if ($ignoreId !== null) {
            $row = $this->db->fetch(
                "SELECT id
                 FROM categories
                 WHERE path = ?
                   AND id != ?
                 LIMIT 1",
                [$path, $ignoreId]
            );
        } else {
            $row = $this->db->fetch(
                "SELECT id
                 FROM categories
                 WHERE path = ?
                 LIMIT 1",
                [$path]
            );
        }

        return $row === null;
    }

    /**
     * Convert category name to path slug.
     */
    protected function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim((string) $text, '-');

        return $text !== '' ? $text : 'category';
    }
}