<?php

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    public function topLevel(): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, 
                    (SELECT COUNT(*) FROM categories cc WHERE cc.parent_id = c.id AND cc.is_active = 1) AS child_count,
                    (SELECT COUNT(*) FROM sites s WHERE s.category_id = c.id AND s.is_active = 1) AS site_count
             FROM categories c
             WHERE c.parent_id IS NULL AND c.is_active = 1
             ORDER BY c.sort_order ASC, c.name ASC'
        );
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
            'SELECT c.*, parent.name AS parent_name
             FROM categories c
             LEFT JOIN categories parent ON parent.id = c.parent_id
             ORDER BY c.path ASC'
        );
    }

    public function findByPath(string $path): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM categories WHERE path = :path AND is_active = 1 LIMIT 1',
            ['path' => $path]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM categories WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function childrenOf(int $parentId): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, 
                    (SELECT COUNT(*) FROM sites s WHERE s.category_id = c.id AND s.is_active = 1) AS site_count
             FROM categories c
             WHERE c.parent_id = :parent_id AND c.is_active = 1
             ORDER BY c.sort_order ASC, c.name ASC',
            ['parent_id' => $parentId]
        );
    }

    public function breadcrumbByPath(string $path): array
    {
        $parts = array_filter(explode('/', $path));
        $crumbs = [];
        $current = '';

        foreach ($parts as $part) {
            $current = $current === '' ? $part : $current . '/' . $part;
            $row = $this->db->fetch('SELECT id, name, path FROM categories WHERE path = :path LIMIT 1', ['path' => $current]);
            if ($row) {
                $crumbs[] = $row;
            }
        }

        return $crumbs;
    }

    public function existsByPath(string $path, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE path = :path';
        $params = ['path' => $path];
        if ($ignoreId) {
            $sql .= ' AND id != :id';
            $params['id'] = $ignoreId;
        }
        return (int) $this->db->fetchValue($sql, $params) > 0;
    }

    public function create(array $data): int
    {
        $this->db->query(
            'INSERT INTO categories (parent_id, slug, path, name, description, sort_order, is_active, source_type)
             VALUES (:parent_id, :slug, :path, :name, :description, :sort_order, :is_active, :source_type)',
            [
                'parent_id' => $data['parent_id'] ?: null,
                'slug' => $data['slug'],
                'path' => $data['path'],
                'name' => $data['name'],
                'description' => $data['description'] ?: null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? 1,
                'source_type' => $data['source_type'] ?? 'manual',
            ]
        );

        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->query(
            'UPDATE categories
             SET parent_id = :parent_id,
                 slug = :slug,
                 path = :path,
                 name = :name,
                 description = :description,
                 sort_order = :sort_order,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'parent_id' => $data['parent_id'] ?: null,
                'slug' => $data['slug'],
                'path' => $data['path'],
                'name' => $data['name'],
                'description' => $data['description'] ?: null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? 1,
            ]
        );
    }

    public function buildPath(?int $parentId, string $slug): string
    {
        if (!$parentId) {
            return $slug;
        }

        $parent = $this->findById($parentId);
        return $parent ? $parent['path'] . '/' . $slug : $slug;
    }

    public function isDescendant(int $candidateParentId, int $categoryId): bool
    {
        $category = $this->findById($candidateParentId);
        $currentParentId = $category['parent_id'] ?? null;

        while ($currentParentId) {
            if ((int) $currentParentId === $categoryId) {
                return true;
            }
            $row = $this->findById((int) $currentParentId);
            $currentParentId = $row['parent_id'] ?? null;
        }

        return false;
    }
}
