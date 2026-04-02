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

    public function featured(int $limit = 6): array
    {
        return $this->db->fetchAll(
            'SELECT s.*, c.name AS category_name, c.path AS category_path
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE s.is_active = 1
               AND s.is_featured = 1
             ORDER BY s.updated_at DESC, s.id DESC
             LIMIT ' . (int) $limit
        );
    }

    public function recentUpdated(int $limit = 8): array
    {
        return $this->db->fetchAll(
            'SELECT s.*, c.name AS category_name, c.path AS category_path
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             ORDER BY s.updated_at DESC, s.id DESC
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
            "SELECT s.*, c.path AS category_path, c.name AS category_name
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE s.category_id = :category_id
               AND s.is_active = 1
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
             WHERE s.is_active = 1
               AND (
                    s.title LIKE :like OR
                    s.description LIKE :like OR
                    c.name LIKE :like OR
                    c.path LIKE :like OR
                    s.url LIKE :like OR
                    s.body_text LIKE :like
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
             WHERE s.is_active = 1
               AND (
                    s.title LIKE :like OR
                    s.description LIKE :like OR
                    c.name LIKE :like OR
                    c.path LIKE :like OR
                    s.url LIKE :like OR
                    s.body_text LIKE :like
               )
             ORDER BY relevance DESC, s.title ASC
             LIMIT {$limit} OFFSET {$offset}",
            [
                'like' => $like,
                'exact' => '%' . $q . '%',
            ]
        );
    }

    private function latestCheckJoin(): string
    {
        return "
            LEFT JOIN (
                SELECT
                    sc.site_id,
                    sc.id AS latest_check_id,
                    sc.result_status AS latest_check_status,
                    sc.checked_at AS latest_checked_at,
                    JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.http_status')) AS latest_http_status,
                    JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.final_url')) AS latest_final_url,
                    JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.redirect_url')) AS latest_redirect_url,
                    JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.error_message')) AS latest_check_error,
                    JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.response_time_ms')) AS latest_response_time_ms
                FROM site_checks sc
                INNER JOIN (
                    SELECT site_id, MAX(id) AS max_id
                    FROM site_checks
                    WHERE check_type = 'http_status'
                    GROUP BY site_id
                ) latest_sc ON latest_sc.max_id = sc.id
            ) hc ON hc.site_id = s.id
        ";
    }

    private function applyEditorFilters(string &$sql, array &$params, ?string $q, ?string $status, ?int $categoryId, ?string $checkFilter, ?string $contentType = null): void
    {
        if ($q) {
            $params['q'] = '%' . $q . '%';
            $sql .= ' AND (s.title LIKE :q OR s.url LIKE :q OR s.normalized_url LIKE :q OR c.path LIKE :q)';
        }

        if ($status !== null && $status !== '') {
            if ($status === 'inactive' || $status === 'inactive_only') {
                $sql .= ' AND s.is_active = 0';
            } elseif ($status === 'active_only') {
                $sql .= ' AND s.is_active = 1';
            } elseif (in_array($status, ['active', 'flagged', 'dead'], true)) {
                $params['status'] = $status;
                $sql .= ' AND s.status = :status';
            }
        }

        if ($categoryId !== null && $categoryId > 0) {
            $params['category_id'] = $categoryId;
            $sql .= ' AND s.category_id = :category_id';
        }

        if ($contentType !== null && $contentType !== '' && in_array($contentType, ['link', 'text'], true)) {
            $params['content_type'] = $contentType;
            $sql .= ' AND s.content_type = :content_type';
        }

        if ($checkFilter !== null && $checkFilter !== '') {
            if ($checkFilter === 'unchecked') {
                $sql .= ' AND hc.latest_check_id IS NULL';
            } elseif (in_array($checkFilter, ['ok', 'warn', 'fail'], true)) {
                $params['check_filter'] = $checkFilter;
                $sql .= ' AND hc.latest_check_status = :check_filter';
            }
        }
    }

    public function editorCount(?string $q = null, ?string $status = null, ?int $categoryId = null, ?string $checkFilter = null, ?string $contentType = null, string $sort = 'recent_checks'): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*)
                FROM sites s
                INNER JOIN categories c ON c.id = s.category_id
                ' . $this->latestCheckJoin() . '
                WHERE 1=1';

        $this->applyEditorFilters($sql, $params, $q, $status, $categoryId, $checkFilter, $contentType);

        if ($sort === 'featured_only') {
            $sql .= ' AND s.is_featured = 1';
        }

        return (int) $this->db->fetchValue($sql, $params);
    }

    public function editorList(int $limit, int $offset, ?string $q = null, ?string $status = null, ?int $categoryId = null, ?string $checkFilter = null, ?string $contentType = null, string $sort = 'recent_checks'): array
    {
        $params = [];
        $sql = "SELECT
                    s.*,
                    s.is_active AS is_active,
                    c.name AS category_name,
                    c.path AS category_path,
                    hc.latest_check_id,
                    hc.latest_check_status,
                    hc.latest_check_status AS latest_status,
                    hc.latest_checked_at,
                    hc.latest_checked_at AS checked_at,
                    hc.latest_http_status,
                    hc.latest_final_url,
                    hc.latest_redirect_url,
                    hc.latest_check_error,
                    hc.latest_response_time_ms
                FROM sites s
                INNER JOIN categories c ON c.id = s.category_id
                " . $this->latestCheckJoin() . "
                WHERE 1=1";

        $this->applyEditorFilters($sql, $params, $q, $status, $categoryId, $checkFilter, $contentType);

        $orderBy = match ($sort) {
            'featured_first' => "s.is_featured DESC,
                                 CASE WHEN hc.latest_check_id IS NULL THEN 0 ELSE 1 END ASC,
                                 hc.latest_checked_at ASC,
                                 s.updated_at DESC,
                                 s.id DESC",
            'featured_only' => "s.is_featured DESC,
                                s.updated_at DESC,
                                s.id DESC",
            'title_asc' => "s.title ASC, s.id DESC",
            'updated_desc' => "s.updated_at DESC, s.id DESC",
            default => "CASE WHEN hc.latest_check_id IS NULL THEN 0 ELSE 1 END ASC,
                        hc.latest_checked_at ASC,
                        s.updated_at DESC,
                        s.id DESC",
        };

        if ($sort === 'featured_only') {
            $sql .= ' AND s.is_featured = 1';
        }

        $sql .= " ORDER BY {$orderBy}
                  LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    public function duplicateGroupCount(?string $q = null): int
    {
        $params = [];
        $where = 'WHERE s.normalized_url IS NOT NULL AND s.normalized_url != ""';

        if ($q) {
            $params['q'] = '%' . $q . '%';
            $where .= ' AND (s.normalized_url LIKE :q OR s.url LIKE :q OR s.title LIKE :q)';
        }

        return (int) $this->db->fetchValue(
            "SELECT COUNT(*)
             FROM (
                SELECT s.normalized_url
                FROM sites s
                {$where}
                GROUP BY s.normalized_url
                HAVING COUNT(*) > 1
             ) duplicate_groups",
            $params
        );
    }

    public function duplicateGroups(int $limit, int $offset, ?string $q = null): array
    {
        $params = [];
        $where = 'WHERE s.normalized_url IS NOT NULL AND s.normalized_url != ""';

        if ($q) {
            $params['q'] = '%' . $q . '%';
            $where .= ' AND (s.normalized_url LIKE :q OR s.url LIKE :q OR s.title LIKE :q)';
        }

        $sql = "SELECT
                    s.normalized_url,
                    COUNT(*) AS duplicate_count,
                    GROUP_CONCAT(CAST(s.id AS CHAR) ORDER BY s.id SEPARATOR ',') AS site_ids,
                    GROUP_CONCAT(s.title ORDER BY s.id SEPARATOR ' || ') AS site_titles,
                    GROUP_CONCAT(c.path ORDER BY s.id SEPARATOR ' || ') AS category_paths
                FROM sites s
                INNER JOIN categories c ON c.id = s.category_id
                {$where}
                GROUP BY s.normalized_url
                HAVING COUNT(*) > 1
                ORDER BY duplicate_count DESC, s.normalized_url ASC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    public function deadCount(?string $q = null): int
    {
        $params = [
            'dead_status' => 'dead',
            'fail_status' => 'fail',
        ];

        $sql = 'SELECT COUNT(*)
                FROM sites s
                INNER JOIN categories c ON c.id = s.category_id
                ' . $this->latestCheckJoin() . '
                WHERE (s.status = :dead_status OR hc.latest_check_status = :fail_status)';

        if ($q) {
            $params['q'] = '%' . $q . '%';
            $sql .= ' AND (s.title LIKE :q OR s.url LIKE :q OR c.path LIKE :q)';
        }

        return (int) $this->db->fetchValue($sql, $params);
    }

    public function deadList(int $limit, int $offset, ?string $q = null): array
    {
        $params = [
            'dead_status' => 'dead',
            'fail_status' => 'fail',
        ];

        $sql = "SELECT
                    s.*,
                    s.is_active AS is_active,
                    c.name AS category_name,
                    c.path AS category_path,
                    hc.latest_check_id,
                    hc.latest_check_status,
                    hc.latest_check_status AS latest_status,
                    hc.latest_checked_at,
                    hc.latest_checked_at AS checked_at,
                    hc.latest_http_status,
                    hc.latest_final_url,
                    hc.latest_redirect_url,
                    hc.latest_check_error,
                    hc.latest_response_time_ms
                FROM sites s
                INNER JOIN categories c ON c.id = s.category_id
                " . $this->latestCheckJoin() . "
                WHERE (s.status = :dead_status OR hc.latest_check_status = :fail_status)";

        if ($q) {
            $params['q'] = '%' . $q . '%';
            $sql .= ' AND (s.title LIKE :q OR s.url LIKE :q OR c.path LIKE :q)';
        }

        $sql .= " ORDER BY hc.latest_checked_at DESC, s.updated_at DESC, s.id DESC
                  LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }


    public function deadIds(?string $q = null): array
    {
        $params = [
            'dead_status' => 'dead',
            'fail_status' => 'fail',
        ];

        $sql = "SELECT DISTINCT s.id
                FROM sites s
                INNER JOIN categories c ON c.id = s.category_id
                " . $this->latestCheckJoin() . "
                WHERE (s.status = :dead_status OR hc.latest_check_status = :fail_status)";

        if ($q) {
            $params['q'] = '%' . $q . '%';
            $sql .= ' AND (s.title LIKE :q OR s.url LIKE :q OR c.path LIKE :q)';
        }

        $sql .= " ORDER BY s.id ASC";

        return array_map(
            static fn (array $row): int => (int) $row['id'],
            $this->db->fetchAll($sql, $params)
        );
    }

    public function deadCountByLatestCheck(): int
    {
        return (int) $this->db->fetchValue(
            'SELECT COUNT(*)
             FROM sites s
             ' . $this->latestCheckJoin() . '
             WHERE hc.latest_check_status = :status',
            ['status' => 'fail']
        );
    }

    public function checkedWithinHours(int $hours = 24): int
    {
        $hours = max(1, $hours);

        return (int) $this->db->fetchValue(
            "SELECT COUNT(DISTINCT site_id)
             FROM site_checks
             WHERE check_type = 'http_status'
               AND checked_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)"
        );
    }

    public function sitesDueForHttpCheck(int $limit = 50, ?int $siteId = null, bool $includeInactive = false, int $staleHours = 168, ?string $statusFilter = null): array
    {
        $limit = max(1, $limit);
        $staleHours = max(1, $staleHours);

        $params = [];
        $sql = "SELECT
                    s.*,
                    hc.latest_checked_at
                FROM sites s
                " . $this->latestCheckJoin() . "
                WHERE 1=1";

        if (!$includeInactive) {
            $sql .= ' AND s.is_active = 1';
        }

        if ($siteId !== null && $siteId > 0) {
            $params['site_id'] = $siteId;
            $sql .= ' AND s.id = :site_id';
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $statusValues = array_values(array_filter(array_map('trim', explode(',', $statusFilter))));
            $allowedStatuses = ['active', 'dead', 'flagged', 'hidden'];
            $statusValues = array_values(array_intersect($statusValues, $allowedStatuses));

            if ($statusValues === []) {
                return [];
            }

            $statusPlaceholders = [];
            foreach ($statusValues as $index => $statusValue) {
                $paramKey = 'status_' . $index;
                $statusPlaceholders[] = ':' . $paramKey;
                $params[$paramKey] = $statusValue;
            }

            $sql .= ' AND s.status IN (' . implode(', ', $statusPlaceholders) . ')';
        }

        $sql .= " AND (
                    hc.latest_check_id IS NULL
                    OR hc.latest_checked_at < DATE_SUB(NOW(), INTERVAL {$staleHours} HOUR)
                  )
                  ORDER BY
                    CASE WHEN hc.latest_check_id IS NULL THEN 0 ELSE 1 END ASC,
                    hc.latest_checked_at ASC,
                    s.id ASC
                  LIMIT {$limit}";

        return $this->db->fetchAll($sql, $params);
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM sites WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function findByIdWithLatestCheck(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT
                s.*,
                s.is_active AS is_active,
                c.name AS category_name,
                c.path AS category_path,
                hc.latest_check_id,
                hc.latest_check_status,
                hc.latest_check_status AS latest_status,
                hc.latest_checked_at,
                hc.latest_checked_at AS checked_at,
                hc.latest_http_status,
                hc.latest_final_url,
                hc.latest_redirect_url,
                hc.latest_check_error,
                hc.latest_response_time_ms
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             " . $this->latestCheckJoin() . "
             WHERE s.id = :id
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function findByCategoryAndSlug(int $categoryId, string $slug): ?array
    {
        return $this->db->fetch(
            'SELECT s.*, c.path AS category_path, c.name AS category_name
             FROM sites s
             INNER JOIN categories c ON c.id = s.category_id
             WHERE s.category_id = :category_id
               AND s.slug = :slug
               AND s.is_active = 1
             LIMIT 1',
            [
                'category_id' => $categoryId,
                'slug' => $slug,
            ]
        );
    }

    public function findAnyByCategoryAndSlug(int $categoryId, string $slug, ?int $ignoreId = null): ?array
    {
        $sql = 'SELECT * FROM sites WHERE category_id = :category_id AND slug = :slug';
        $params = [
            'category_id' => $categoryId,
            'slug' => $slug,
        ];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $ignoreId;
        }

        $sql .= ' LIMIT 1';

        return $this->db->fetch($sql, $params);
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

    public function create(array $data): int
    {
        $this->db->query(
            'INSERT INTO sites (
                category_id, title, slug, url, normalized_url, description, content_type,
                body_text, text_source_note, text_author, status, source_type,
                original_title, original_description, original_url, is_reviewed,
                approved_at, is_active, is_featured, created_at, updated_at
             ) VALUES (
                :category_id, :title, :slug, :url, :normalized_url, :description, :content_type,
                :body_text, :text_source_note, :text_author, :status, :source_type,
                :original_title, :original_description, :original_url, 1,
                NOW(), :is_active, :is_featured, NOW(), NOW()
             )',
            [
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => $data['slug'],
                'url' => $data['url'] !== '' ? $data['url'] : null,
                'normalized_url' => $data['normalized_url'],
                'description' => $data['description'],
                'content_type' => $data['content_type'] ?? 'link',
                'body_text' => $data['body_text'] !== '' ? $data['body_text'] : null,
                'text_source_note' => $data['text_source_note'] !== '' ? $data['text_source_note'] : null,
                'text_author' => $data['text_author'] !== '' ? $data['text_author'] : null,
                'status' => $data['status'],
                'source_type' => $data['source_type'] ?? 'manual',
                'original_title' => $data['original_title'] ?: $data['title'],
                'original_description' => $data['original_description'] ?: $data['description'],
                'original_url' => $data['original_url'] ?: ($data['url'] ?: null),
                'is_active' => $data['is_active'],
                'is_featured' => $data['is_featured'] ?? 0,
            ]
        );

        return (int) $this->db->lastInsertId();
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
                 content_type = :content_type,
                 body_text = :body_text,
                 text_source_note = :text_source_note,
                 text_author = :text_author,
                 status = :status,
                 is_active = :is_active,
                 is_featured = :is_featured,
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
                'content_type' => $data['content_type'] ?? 'link',
                'body_text' => $data['body_text'] !== '' ? $data['body_text'] : null,
                'text_source_note' => $data['text_source_note'] !== '' ? $data['text_source_note'] : null,
                'text_author' => $data['text_author'] !== '' ? $data['text_author'] : null,
                'status' => $data['status'],
                'is_active' => $data['is_active'],
                'is_featured' => $data['is_featured'] ?? 0,
                'original_title' => $data['original_title'] ?: $data['title'],
                'original_description' => $data['original_description'] ?: $data['description'],
                'original_url' => $data['original_url'] ?: ($data['url'] ?: null),
            ]
        );
    }


    public function delete(int $id): void
    {
        $this->db->beginTransaction();

        try {
            $this->db->query(
                'DELETE FROM sites WHERE id = :id',
                ['id' => $id]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function syncStatusFromCheck(int $id, string $resultStatus): void
    {
        $status = match ($resultStatus) {
            'ok' => 'active',
            'warn' => 'flagged',
            default => 'dead',
        };

        $this->db->query(
            'UPDATE sites
             SET status = :status, updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'status' => $status,
            ]
        );
    }

    public function createFromSubmission(array $submission): int
    {
        $slug = slugify($submission['title']);
        $normalizedUrl = normalize_url($submission['url']);

        $this->db->query(
            'INSERT INTO sites (
                category_id, title, slug, url, normalized_url, description, content_type,
                status, source_type, original_title, original_description, original_url,
                is_reviewed, approved_at, is_active, created_at, updated_at
             ) VALUES (
                :category_id, :title, :slug, :url, :normalized_url, :description, :content_type,
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
                'content_type' => 'link',
                'status' => 'active',
                'source_type' => 'submission',
                'original_title' => $submission['title'],
                'original_description' => $submission['description'],
                'original_url' => $submission['url'],
            ]
        );

        return $this->db->lastInsertId();
    }
}