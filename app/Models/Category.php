<?php

namespace App\Models;

use App\Core\Database;

class Category
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Top level categories (used by HomeController)
     */
    public function topLevel(): array
    {
        return $this->db->fetchAll(
            "SELECT *
             FROM categories
             WHERE parent_id IS NULL
               AND is_active = 1
             ORDER BY sort_order ASC, name ASC"
        );
    }

    /**
     * Find category by ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM categories WHERE id = ?",
            [$id]
        ) ?: null;
    }

    /**
     * Find category by path
     */
    public function findByPath(string $path): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM categories WHERE path = ? AND is_active = 1",
            [$path]
        ) ?: null;
    }

    /**
     * Children of category
     */
    public function children(int $parentId): array
    {
        return $this->db->fetchAll(
            "SELECT *
             FROM categories
             WHERE parent_id = ?
               AND is_active = 1
             ORDER BY sort_order ASC, name ASC",
            [$parentId]
        );
    }

    /**
     * Get all categories for parent select dropdown
     */
    public function allForParentSelect(?int $excludeId = null): array
    {
        $sql = "SELECT id, name, path FROM categories WHERE is_active = 1";
        $params = [];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $sql .= " ORDER BY path ASC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Create category
     */
    public function create(array $data): int
    {
        $parentPath = null;

        if (!empty($data['parent_id'])) {
            $parent = $this->find($data['parent_id']);
            $parentPath = $parent['path'];
        }

        $slug = $this->slugify($data['name']);

        $path = $parentPath
            ? $parentPath . '/' . $slug
            : $slug;

        $this->db->execute(
            "INSERT INTO categories (name, parent_id, path, description, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, 1)",
            [
                $data['name'],
                $data['parent_id'] ?: null,
                $path,
                $data['description'] ?? null,
                $data['sort_order'] ?? 0
            ]
        );

        return $this->db->lastInsertId();
    }

    /**
     * Update category
     */
    public function updateCategory(int $id, array $data): void
    {
        $category = $this->find($id);

        if (!$category) {
            throw new \Exception("Category not found");
        }

        $newParentId = $data['parent_id'] ?? null;

        if ($newParentId == $id) {
            throw new \Exception("Category cannot be its own parent");
        }

        if ($this->isDescendant($newParentId, $id)) {
            throw new \Exception("Cannot move category under its own descendant");
        }

        $parentPath = null;

        if ($newParentId) {
            $parent = $this->find($newParentId);
            $parentPath = $parent['path'];
        }

        $slug = $this->slugify($data['name']);

        $newPath = $parentPath
            ? $parentPath . '/' . $slug
            : $slug;

        $oldPath = $category['path'];

        $this->db->execute(
            "UPDATE categories
             SET name = ?, parent_id = ?, path = ?, description = ?, sort_order = ?
             WHERE id = ?",
            [
                $data['name'],
                $newParentId,
                $newPath,
                $data['description'] ?? null,
                $data['sort_order'] ?? 0,
                $id
            ]
        );

        if ($oldPath !== $newPath) {
            $this->rebuildDescendantPaths($oldPath, $newPath);
        }
    }

    /**
     * Check if category is descendant of another
     */
    protected function isDescendant(?int $childId, int $parentId): bool
    {
        if (!$childId) {
            return false;
        }

        while ($childId) {
            $row = $this->find($childId);

            if (!$row) {
                return false;
            }

            if ($row['parent_id'] == $parentId) {
                return true;
            }

            $childId = $row['parent_id'];
        }

        return false;
    }

    /**
     * Rebuild descendant paths after move/rename
     */
    protected function rebuildDescendantPaths(string $oldPath, string $newPath): void
    {
        $children = $this->db->fetchAll(
            "SELECT id, path FROM categories WHERE path LIKE ?",
            [$oldPath . '/%']
        );

        foreach ($children as $child) {

            $updatedPath = preg_replace(
                '#^' . preg_quote($oldPath, '#') . '#',
                $newPath,
                $child['path']
            );

            $this->db->execute(
                "UPDATE categories SET path = ? WHERE id = ?",
                [$updatedPath, $child['id']]
            );
        }
    }

    /**
     * Convert name to URL slug
     */
    protected function slugify(string $text): string
    {
        $text = strtolower($text);

        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        $text = trim($text, '-');

        return $text ?: 'category';
    }
}