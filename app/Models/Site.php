<?php

namespace App\Models;

use App\Core\Model;

class Site extends Model
{
    public function latestApproved(int $limit = 10): array
    {
        return $this->db->query(
            'SELECT s.*, c.name AS category_name, c.path AS category_path
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE s.status = "active"
             ORDER BY s.created_at DESC
             LIMIT ' . (int) $limit
        );
    }

    public function allForEditor(): array
    {
        return $this->db->query(
            'SELECT s.*, c.name AS category_name, c.path AS category_path
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             ORDER BY s.created_at DESC, s.id DESC'
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->first('SELECT * FROM sites WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function duplicatesForUrl(string $url, ?int $excludeId = null): array
    {
        $normalized = $this->normalizeUrl($url);
        $sql = 'SELECT s.*, c.name AS category_name, c.path AS category_path
                FROM sites s
                INNER JOIN categories c ON c.id = s.category_id
                WHERE s.normalized_url = :normalized_url';
        $params = ['normalized_url' => $normalized];

        if ($excludeId !== null) {
            $sql .= ' AND s.id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' ORDER BY s.id DESC';
        return $this->db->query($sql, $params);
    }

    public function forCategory(int $categoryId): array
    {
        return $this->db->query(
            'SELECT * FROM sites WHERE category_id = :category_id AND status = "active" ORDER BY title',
            ['category_id' => $categoryId]
        );
    }

    public function search(string $term): array
    {
        $like = '%' . $term . '%';
        return $this->db->query(
            'SELECT s.*, c.name AS category_name, c.path AS category_path
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE s.status = "active"
               AND (s.title LIKE :like OR s.description LIKE :like)
             ORDER BY s.title',
            ['like' => $like]
        );
    }

    public function createFromSubmission(array $submission, int $approvedByUserId): int
    {
        $slug = $this->slugify($submission['title']);

        $this->db->execute(
            'INSERT INTO sites (
                category_id, title, slug, url, normalized_url, description,
                status, source_type, source_key, original_title, original_description,
                original_url, is_reviewed, review_notes, approved_by_user_id,
                approved_at, created_at, updated_at
             ) VALUES (
                :category_id, :title, :slug, :url, :normalized_url, :description,
                "active", "submission_approved", NULL, :original_title, :original_description,
                :original_url, 1, NULL, :approved_by_user_id,
                NOW(), NOW(), NOW()
             )',
            [
                'category_id' => $submission['proposed_category_id'],
                'title' => $submission['title'],
                'slug' => $slug,
                'url' => $submission['url'],
                'normalized_url' => $this->normalizeUrl($submission['url']),
                'description' => $submission['description'],
                'original_title' => $submission['title'],
                'original_description' => $submission['description'],
                'original_url' => $submission['url'],
                'approved_by_user_id' => $approvedByUserId,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateSite(int $id, array $data): void
    {
        $existing = $this->find($id);
        if (!$existing) {
            return;
        }

        $slug = $this->slugify($data['title']);
        $normalizedUrl = $this->normalizeUrl($data['url']);

        $this->db->execute(
            'UPDATE sites
             SET category_id = :category_id, title = :title, slug = :slug, url = :url,
                 normalized_url = :normalized_url, description = :description, status = :status,
                 review_notes = :review_notes, updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'category_id' => (int) $data['category_id'],
                'title' => trim($data['title']),
                'slug' => $slug,
                'url' => trim($data['url']),
                'normalized_url' => $normalizedUrl,
                'description' => trim($data['description']),
                'status' => $data['status'],
                'review_notes' => trim((string) ($data['review_notes'] ?? '')),
            ]
        );
    }

    public function normalizeUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);

        if (!$parts || empty($parts['host'])) {
            return rtrim(strtolower($url), '/');
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host']);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $path = $parts['path'] ?? '';
        $path = $path === '/' ? '' : rtrim($path, '/');
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        return $scheme . '://' . $host . $path . $query;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'site';
        return trim($value, '-');
    }
}
