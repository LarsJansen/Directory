<?php

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    public function log(?int $userId, string $entityType, int $entityId, string $action, ?array $details = null): void
    {
        $this->db->query(
            'INSERT INTO audit_log (user_id, entity_type, entity_id, action, details, created_at)
             VALUES (:user_id, :entity_type, :entity_id, :action, :details, NOW())',
            [
                'user_id' => $userId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'details' => $details ? json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            ]
        );
    }

    public function countAll(?string $entityType = null, ?string $action = null): int
    {
        $sql = 'SELECT COUNT(*) FROM audit_log WHERE 1=1';
        $params = [];

        if ($entityType !== null && $entityType !== '') {
            $sql .= ' AND entity_type = :entity_type';
            $params['entity_type'] = $entityType;
        }

        if ($action !== null && $action !== '') {
            $sql .= ' AND action = :action';
            $params['action'] = $action;
        }

        return (int) $this->db->fetchValue($sql, $params);
    }

    public function paginatedList(int $limit, int $offset, ?string $entityType = null, ?string $action = null): array
    {
        $sql = 'SELECT a.*, u.username
                FROM audit_log a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE 1=1';
        $params = [];

        if ($entityType !== null && $entityType !== '') {
            $sql .= ' AND a.entity_type = :entity_type';
            $params['entity_type'] = $entityType;
        }

        if ($action !== null && $action !== '') {
            $sql .= ' AND a.action = :action';
            $params['action'] = $action;
        }

        $sql .= " ORDER BY a.created_at DESC, a.id DESC LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    public function recent(int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT a.*, u.username
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT ' . (int) $limit
        );
    }
}
