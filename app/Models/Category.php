<?php

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    public function topLevel(): array
    {
        return $this->db->query(
            'SELECT id, name, slug, path, description FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order, name'
        );
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT c.*, p.name AS parent_name FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id
             ORDER BY c.path'
        );
    }

    public function treeOptions(?int $excludeId = null): array
    {
        $categories = $this->db->query(
            'SELECT id, parent_id, name, path FROM categories WHERE is_active = 1 ORDER BY path'
        );

        if ($excludeId === null) {
            return $categories;
        }

        $excludeIds = [$excludeId];
        $changed = true;

        while ($changed) {
            $changed = false;
            foreach ($categories as $category) {
                if (in_array((int) $category['parent_id'], $excludeIds, true) && !in_array((int) $category['id'], $excludeIds, true)) {
                    $excludeIds[] = (int) $category['id'];
                    $changed = true;
                }
            }
        }

        return array_values(array_filter($categories, fn ($category) => !in_array((int) $category['id'], $excludeIds, true)));
    }

    public function findByPath(string $path): ?array
    {
        return $this->db->first(
            'SELECT * FROM categories WHERE path = :path AND is_active = 1 LIMIT 1',
            ['path' => $path]
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->first('SELECT * FROM categories WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function children(int $parentId): array
    {
        return $this->db->query(
            'SELECT * FROM categories WHERE parent_id = :parent_id AND is_active = 1 ORDER BY sort_order, name',
            ['parent_id' => $parentId]
        );
    }

    public function breadcrumbs(int $categoryId): array
    {
        $trail = [];
        $current = $this->find($categoryId);

        while ($current) {
            array_unshift($trail, $current);
            $current = $current['parent_id'] ? $this->find((int) $current['parent_id']) : null;
        }

        return $trail;
    }

    public function create(array $data): int
    {
        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $slug = $this->slugify($data['name']);
        $path = $this->buildPath($parentId, $slug);
        $path = $this->ensureUniquePath($path);
        $slug = basename(str_replace('/', DIRECTORY_SEPARATOR, $path));

        $this->db->execute(
            'INSERT INTO categories (parent_id, slug, path, name, description, sort_order, is_active, source_type, is_reviewed, created_at, updated_at)
             VALUES (:parent_id, :slug, :path, :name, :description, :sort_order, :is_active, "manual", 1, NOW(), NOW())',
            [
                'parent_id' => $parentId,
                'slug' => $slug,
                'path' => $path,
                'name' => trim($data['name']),
                'description' => trim((string) ($data['description'] ?? '')),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => !empty($data['is_active']) ? 1 : 0,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateCategory(int $id, array $data): void
    {
        $existing = $this->find($id);
        if (!$existing) {
            return;
        }

        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $slug = $this->slugify($data['name']);
        $path = $this->buildPath($parentId, $slug);
        $path = $this->ensureUniquePath($path, $id);
        $slug = basename(str_replace('/', DIRECTORY_SEPARATOR, $path));

        $this->db->execute(
            'UPDATE categories
             SET parent_id = :parent_id, slug = :slug, path = :path, name = :name, description = :description,
                 sort_order = :sort_order, is_active = :is_active, updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'parent_id' => $parentId,
                'slug' => $slug,
                'path' => $path,
                'name' => trim($data['name']),
                'description' => trim((string) ($data['description'] ?? '')),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => !empty($data['is_active']) ? 1 : 0,
            ]
        );

        $this->refreshDescendantPaths($id);
    }

    private function buildPath(?int $parentId, string $slug): string
    {
        if (!$parentId) {
            return $slug;
        }

        $parent = $this->find($parentId);
        return $parent ? $parent['path'] . '/' . $slug : $slug;
    }

    private function ensureUniquePath(string $path, ?int $ignoreId = null): string
    {
        $base = $path;
        $counter = 2;

        while (true) {
            $params = ['path' => $path];
            $sql = 'SELECT id FROM categories WHERE path = :path';
            if ($ignoreId !== null) {
                $sql .= ' AND id != :ignore_id';
                $params['ignore_id'] = $ignoreId;
            }
            $existing = $this->db->first($sql . ' LIMIT 1', $params);
            if (!$existing) {
                return $path;
            }
            $path = $base . '-' . $counter;
            $counter++;
        }
    }

    private function refreshDescendantPaths(int $parentId): void
    {
        $children = $this->db->query('SELECT * FROM categories WHERE parent_id = :parent_id', ['parent_id' => $parentId]);
        $parent = $this->find($parentId);

        foreach ($children as $child) {
            $newPath = $parent['path'] . '/' . $child['slug'];
            $newPath = $this->ensureUniquePath($newPath, (int) $child['id']);
            $newSlug = basename(str_replace('/', DIRECTORY_SEPARATOR, $newPath));

            $this->db->execute(
                'UPDATE categories SET slug = :slug, path = :path, updated_at = NOW() WHERE id = :id',
                [
                    'id' => $child['id'],
                    'slug' => $newSlug,
                    'path' => $newPath,
                ]
            );

            $this->refreshDescendantPaths((int) $child['id']);
        }
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'category';
        return trim($value, '-');
    }
}
