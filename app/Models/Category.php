<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class Category extends Model
{
    public function allWithCounts(): array
    {
        $sql = <<<SQL
        SELECT
            c.*,
            parent.name AS parent_name,
            (
                SELECT COUNT(*)
                FROM categories children
                WHERE children.parent_id = c.id
            ) AS child_count,
            (
                SELECT COUNT(*)
                FROM sites s
                WHERE s.category_id = c.id
            ) AS site_count
        FROM categories c
        LEFT JOIN categories parent ON parent.id = c.parent_id
        ORDER BY c.path ASC
        SQL;

        return $this->db->query($sql);
    }

    public function allForParentSelect(?int $excludeId = null): array
    {
        $sql = 'SELECT id, name, path FROM categories';
        $params = [];

        if ($excludeId !== null) {
            $sql .= ' WHERE id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' ORDER BY path ASC';

        return $this->db->query($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->db->first('SELECT * FROM categories WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findByPath(string $path): ?array
    {
        return $this->db->first('SELECT * FROM categories WHERE path = :path LIMIT 1', ['path' => trim($path, '/')]);
    }

    public function create(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Category name is required.');
        }

        $parentId = $this->normalizeParentId($data['parent_id'] ?? null);
        $description = trim((string) ($data['description'] ?? ''));
        $sortOrder = (int) ($data['sort_order'] ?? 0);
        $isActive = !empty($data['is_active']) ? 1 : 0;

        $slug = $this->makeUniqueSlug(slugify($name), $parentId, null);
        $path = $this->buildPath($slug, $parentId);

        $this->db->execute(
            'INSERT INTO categories (parent_id, slug, path, name, description, sort_order, is_active) VALUES (:parent_id, :slug, :path, :name, :description, :sort_order, :is_active)',
            [
                'parent_id' => $parentId,
                'slug' => $slug,
                'path' => $path,
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
            ]
        );

        return (int) $this->db->lastInsertId();
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
        $isActive = !empty($data['is_active']) ? 1 : 0;

        if ($parentId === $id) {
            throw new RuntimeException('A category cannot be its own parent.');
        }

        if ($parentId !== null && $this->isDescendant($parentId, $id)) {
            throw new RuntimeException('You cannot move a category underneath one of its descendants.');
        }

        $slug = $this->makeUniqueSlug(slugify($name), $parentId, $id);
        $newPath = $this->buildPath($slug, $parentId);
        $oldPath = (string) $existing['path'];

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                'UPDATE categories SET parent_id = :parent_id, slug = :slug, path = :path, name = :name, description = :description, sort_order = :sort_order, is_active = :is_active WHERE id = :id'
            );
            $statement->execute([
                'parent_id' => $parentId,
                'slug' => $slug,
                'path' => $newPath,
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
                'id' => $id,
            ]);

            if ($oldPath !== $newPath) {
                $this->rebuildDescendantPaths($pdo, $oldPath, $newPath, $id);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return [
            'old' => $existing,
            'new' => $this->find($id),
        ];
    }

    public function breadcrumbsByPath(string $path): array
    {
        $parts = array_filter(explode('/', trim($path, '/')));
        $crumbs = [];
        $running = [];

        foreach ($parts as $part) {
            $running[] = $part;
            $category = $this->findByPath(implode('/', $running));
            if ($category) {
                $crumbs[] = $category;
            }
        }

        return $crumbs;
    }

    private function normalizeParentId($parentId): ?int
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

    private function buildPath(string $slug, ?int $parentId): string
    {
        if ($parentId === null) {
            return $slug;
        }

        $parent = $this->find($parentId);
        if (!$parent) {
            throw new RuntimeException('Parent category not found while building path.');
        }

        return trim($parent['path'], '/') . '/' . $slug;
    }

    private function makeUniqueSlug(string $slug, ?int $parentId, ?int $ignoreId): string
    {
        $baseSlug = $slug !== '' ? $slug : 'item';
        $candidate = $baseSlug;
        $suffix = 2;

        while (!$this->isSlugAvailable($candidate, $parentId, $ignoreId)) {
            $candidate = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function isSlugAvailable(string $slug, ?int $parentId, ?int $ignoreId): bool
    {
        $sql = 'SELECT id FROM categories WHERE slug = :slug AND ';
        $params = ['slug' => $slug];

        if ($parentId === null) {
            $sql .= 'parent_id IS NULL';
        } else {
            $sql .= 'parent_id = :parent_id';
            $params['parent_id'] = $parentId;
        }

        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        return $this->db->first($sql . ' LIMIT 1', $params) === null;
    }

    private function isDescendant(int $potentialParentId, int $categoryId): bool
    {
        $category = $this->find($categoryId);
        $potentialParent = $this->find($potentialParentId);

        if (!$category || !$potentialParent) {
            return false;
        }

        $categoryPath = trim((string) $category['path'], '/');
        $parentPath = trim((string) $potentialParent['path'], '/');

        return $parentPath === $categoryPath || str_starts_with($parentPath, $categoryPath . '/');
    }

    private function rebuildDescendantPaths(\PDO $pdo, string $oldPath, string $newPath, int $categoryId): void
    {
        $like = $oldPath . '/%';
        $select = $pdo->prepare('SELECT id, path FROM categories WHERE path LIKE :like ORDER BY LENGTH(path) ASC');
        $select->execute(['like' => $like]);
        $rows = $select->fetchAll(\PDO::FETCH_ASSOC);

        $update = $pdo->prepare('UPDATE categories SET path = :path WHERE id = :id');

        foreach ($rows as $row) {
            $updatedPath = preg_replace('~^' . preg_quote($oldPath, '~') . '~', $newPath, (string) $row['path'], 1);
            if ($updatedPath === null) {
                continue;
            }
            $update->execute([
                'path' => $updatedPath,
                'id' => $row['id'],
            ]);
        }
    }
}
