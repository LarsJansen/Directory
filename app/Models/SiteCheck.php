<?php

namespace App\Models;

use App\Core\Model;

class SiteCheck extends Model
{
    public function recordHttpStatus(int $siteId, string $resultStatus, array $data): int
    {
        $this->db->query(
            'INSERT INTO site_checks (site_id, check_type, result_status, result_data, checked_at)
             VALUES (:site_id, :check_type, :result_status, :result_data, NOW())',
            [
                'site_id' => $siteId,
                'check_type' => 'http_status',
                'result_status' => $resultStatus,
                'result_data' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]
        );

        return $this->db->lastInsertId();
    }

    public function latestForSite(int $siteId): ?array
    {
        return $this->db->fetch(
            "SELECT sc.*,
                    JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.http_status')) AS http_status,
                    JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.final_url')) AS final_url,
                    JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.redirect_url')) AS redirect_url,
                    JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.error_message')) AS error_message,
                    JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.response_time_ms')) AS response_time_ms
             FROM site_checks sc
             WHERE sc.site_id = :site_id
               AND sc.check_type = 'http_status'
             ORDER BY sc.id DESC
             LIMIT 1",
            ['site_id' => $siteId]
        );
    }

    public function countRecentHttpChecks(?string $resultStatus = null, ?string $q = null): int
    {
        $sql = "SELECT COUNT(*)
                FROM site_checks sc
                INNER JOIN sites s ON s.id = sc.site_id
                INNER JOIN categories c ON c.id = s.category_id
                WHERE sc.check_type = 'http_status'";
        $params = [];

        if ($resultStatus !== null && $resultStatus !== '') {
            $sql .= ' AND sc.result_status = :result_status';
            $params['result_status'] = $resultStatus;
        }

        if ($q !== null && $q !== '') {
            $sql .= ' AND (s.title LIKE :q OR s.url LIKE :q OR c.path LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        return (int) $this->db->fetchValue($sql, $params);
    }

    public function recentHttpChecks(int $limit, int $offset, ?string $resultStatus = null, ?string $q = null): array
    {
        $sql = "SELECT sc.*,
                       s.title AS site_title,
                       s.url AS site_url,
                       s.status AS site_status,
                       c.path AS category_path,
                       JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.http_status')) AS http_status,
                       JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.final_url')) AS final_url,
                       JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.redirect_url')) AS redirect_url,
                       JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.error_message')) AS error_message,
                       JSON_UNQUOTE(JSON_EXTRACT(sc.result_data, '$.response_time_ms')) AS response_time_ms
                FROM site_checks sc
                INNER JOIN sites s ON s.id = sc.site_id
                INNER JOIN categories c ON c.id = s.category_id
                WHERE sc.check_type = 'http_status'";
        $params = [];

        if ($resultStatus !== null && $resultStatus !== '') {
            $sql .= ' AND sc.result_status = :result_status';
            $params['result_status'] = $resultStatus;
        }

        if ($q !== null && $q !== '') {
            $sql .= ' AND (s.title LIKE :q OR s.url LIKE :q OR c.path LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $sql .= " ORDER BY sc.checked_at DESC, sc.id DESC
                  LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }
}
