<?php

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    public function log(?int $userId, string $entityType, int $entityId, string $action, ?array $details = null): void
    {
        $this->db->query(
            'INSERT INTO audit_log (user_id, entity_type, entity_id, action, details) VALUES (:user_id, :entity_type, :entity_id, :action, :details)',
            [
                'user_id' => $userId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'details' => $details ? json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            ]
        );
    }
}
