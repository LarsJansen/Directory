<?php

namespace App\Models;

use App\Core\Model;

class ImportBatch extends Model
{
    public function all(): array
    {
        return $this->db->fetchAll(
            'SELECT ib.*, u.username AS imported_by
             FROM import_batches ib
             LEFT JOIN users u ON u.id = ib.imported_by_user_id
             ORDER BY ib.started_at DESC, ib.id DESC'
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT ib.*, u.username AS imported_by
             FROM import_batches ib
             LEFT JOIN users u ON u.id = ib.imported_by_user_id
             WHERE ib.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        $this->db->query(
            'INSERT INTO import_batches (source_name, source_version, batch_label, notes, imported_by_user_id, status, total_categories, total_sites)
             VALUES (:source_name, :source_version, :batch_label, :notes, :imported_by_user_id, :status, 0, 0)',
            [
                'source_name' => $data['source_name'],
                'source_version' => $data['source_version'] ?: null,
                'batch_label' => $data['batch_label'],
                'notes' => $data['notes'] ?: null,
                'imported_by_user_id' => $data['imported_by_user_id'] ?: null,
                'status' => $data['status'] ?? 'running',
            ]
        );
        return $this->db->lastInsertId();
    }

    public function stagingRows(int $batchId): array
    {
        return $this->db->fetchAll(
            'SELECT s.*, c.path AS mapped_category_path, live.id AS duplicate_site_id
             FROM imported_site_staging s
             LEFT JOIN categories c ON c.id = s.mapped_category_id
             LEFT JOIN sites live ON live.normalized_url = s.normalized_url
             WHERE s.import_batch_id = :batch_id
             ORDER BY s.id ASC',
            ['batch_id' => $batchId]
        );
    }
}
