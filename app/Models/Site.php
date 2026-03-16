<?php

namespace App\Models;

use App\Core\Model;

class Site extends Model
{
    public function latest(int $limit = 8): array
    {
        return $this->db->fetchAll(
            'SELECT s.*, c.name AS category_name, c.path AS category_path
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE s.is_active = 1
             ORDER BY s.created_at DESC
             LIMIT ' . (int) $limit
        );
    }

    public function countByCategory(int $categoryId): int
    {
        return (int) $this->db->fetchValue(
            'SELECT COUNT(*) FROM sites WHERE category_id = :category_id AND is_active = 1',
            ['category_id' => $categoryId]
        );
    }

    public function forCategory(int $categoryId, int $limit, int $offset, string $sort = 'title'): array
    {
        $order = match ($sort) {
            'newest' => 's.created_at DESC, s.title ASC',
            default => 's.title ASC',
        };

        return $this->db->fetchAll(
            "SELECT s.* FROM sites s
             WHERE s.category_id = :category_id AND s.is_active = 1
             ORDER BY {$order}
             LIMIT {$limit} OFFSET {$offset}",
            ['category_id' => $categoryId]
        );
    }

    public function countSearch(string $q): int
    {
        $like = '%' . $q . '%';
        return (int) $this->db->fetchValue(
            'SELECT COUNT(*)
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE s.is_active = 1 AND (
                s.title LIKE :like OR
                s.description LIKE :like OR
                c.name LIKE :like OR
                c.path LIKE :like OR
                s.url LIKE :like
             )',
            ['like' => $like]
        );
    }

    public function search(string $q, int $limit, int $offset): array
    {
        $like = '%' . $q . '%';
        return $this->db->fetchAll(
            "SELECT s.*, c.name AS category_name, c.path AS category_path,
                    (
                        (CASE WHEN s.title LIKE :exact THEN 20 ELSE 0 END) +
                        (CASE WHEN s.title LIKE :like THEN 10 ELSE 0 END) +
                        (CASE WHEN c.path LIKE :like THEN 6 ELSE 0 END) +
                        (CASE WHEN s.description LIKE :like THEN 3 ELSE 0 END)
                    ) AS relevance
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE s.is_active = 1 AND (
                 s.title LIKE :like OR s.description LIKE :like OR c.name LIKE :like OR c.path LIKE :like OR s.url LIKE :like
             )
             ORDER BY relevance DESC, s.title ASC
             LIMIT {$limit} OFFSET {$offset}",
            [
                'like' => $like,
                'exact' => '%' . $q . '%',
            ]
        );
    }

    public function editorCount(?string $q = null): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) FROM sites s INNER JOIN categories c ON c.id = s.category_id WHERE 1=1';
        if ($q) {
            $params['q'] = '%' . $q . '%';
            $sql .= ' AND (s.title LIKE :q OR s.url LIKE :q OR s.normalized_url LIKE :q OR c.path LIKE :q)';
        }
        return (int) $this->db->fetchValue($sql, $params);
    }

    public function editorList(int $limit, int $offset, ?string $q = null): array
    {
        $params = [];
        $sql = "SELECT s.*, c.name AS category_name, c.path AS category_path
                FROM sites s
                INNER JOIN categories c ON c.id = s.category_id
                WHERE 1=1";
        if ($q) {
            $params['q'] = '%' . $q . '%';
            $sql .= ' AND (s.title LIKE :q OR s.url LIKE :q OR s.normalized_url LIKE :q OR c.path LIKE :q)';
        }
        $sql .= " ORDER BY s.updated_at DESC, s.id DESC LIMIT {$limit} OFFSET {$offset}";
        return $this->db->fetchAll($sql, $params);
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM sites WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findByNormalizedUrl(string $normalizedUrl, ?int $ignoreId = null): ?array
    {
        $sql = 'SELECT * FROM sites WHERE normalized_url = :normalized_url';
        $params = ['normalized_url' => $normalizedUrl];
        if ($ignoreId) {
            $sql .= ' AND id != :id';
            $params['id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        return $this->db->fetch($sql, $params);
    }

    public function update(int $id, array $data): void
    {
        $this->db->query(
            'UPDATE sites
             SET category_id = :category_id,
                 title = :title,
                 slug = :slug,
                 url = :url,
                 normalized_url = :normalized_url,
                 description = :description,
                 status = :status,
                 is_active = :is_active,
                 original_title = :original_title,
                 original_description = :original_description,
                 original_url = :original_url,
                 updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => $data['slug'],
                'url' => $data['url'],
                'normalized_url' => $data['normalized_url'],
                'description' => $data['description'],
                'status' => $data['status'],
                'is_active' => $data['is_active'],
                'original_title' => $data['original_title'] ?: $data['title'],
                'original_description' => $data['original_description'] ?: $data['description'],
                'original_url' => $data['original_url'] ?: $data['url'],
            ]
        );
    }

    public function createFromSubmission(array $submission): int
    {
        $slug = slugify($submission['title']);
        $normalizedUrl = normalize_url($submission['url']);

        $this->db->query(
            'INSERT INTO sites (
                category_id, title, slug, url, normalized_url, description,
                status, source_type, original_title, original_description, original_url,
                is_reviewed, approved_at, is_active, created_at, updated_at
             ) VALUES (
                :category_id, :title, :slug, :url, :normalized_url, :description,
                :status, :source_type, :original_title, :original_description, :original_url,
                1, NOW(), 1, NOW(), NOW()
             )',
            [
                'category_id' => $submission['proposed_category_id'],
                'title' => $submission['title'],
                'slug' => $slug,
                'url' => $submission['url'],
                'normalized_url' => $normalizedUrl,
                'description' => $submission['description'],
                'status' => 'active',
                'source_type' => 'manual',
                'original_title' => $submission['title'],
                'original_description' => $submission['description'],
                'original_url' => $submission['url'],
            ]
        );

        return $this->db->lastInsertId();
    }
}
