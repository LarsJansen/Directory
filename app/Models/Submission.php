<?php

namespace App\Models;

use App\Core\Model;

class Submission extends Model
{
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO submissions (
                proposed_category_id, submitter_name, submitter_email, title, url, description, notes, status, created_at
             ) VALUES (
                :proposed_category_id, :submitter_name, :submitter_email, :title, :url, :description, :notes, "pending", NOW()
             )',
            [
                'proposed_category_id' => $data['proposed_category_id'] ?: null,
                'submitter_name' => $data['submitter_name'] ?: null,
                'submitter_email' => $data['submitter_email'] ?: null,
                'title' => trim($data['title']),
                'url' => trim($data['url']),
                'description' => trim($data['description']),
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function pending(): array
    {
        return $this->db->query(
            'SELECT s.*, c.name AS category_name, c.path AS category_path
             FROM submissions s
             LEFT JOIN categories c ON c.id = s.proposed_category_id
             WHERE s.status = "pending"
             ORDER BY s.created_at ASC'
        );
    }

    public function counts(): array
    {
        $rows = $this->db->query('SELECT status, COUNT(*) AS total FROM submissions GROUP BY status');
        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'duplicate' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    public function find(int $id): ?array
    {
        return $this->db->first(
            'SELECT s.*, c.name AS category_name, c.path AS category_path
             FROM submissions s
             LEFT JOIN categories c ON c.id = s.proposed_category_id
             WHERE s.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function markApproved(int $id, int $editorId, int $siteId): void
    {
        $this->db->execute(
            'UPDATE submissions
             SET status = "approved", reviewed_by_user_id = :editor_id, reviewed_at = NOW(), created_site_id = :site_id
             WHERE id = :id',
            ['id' => $id, 'editor_id' => $editorId, 'site_id' => $siteId]
        );
    }

    public function markRejected(int $id, int $editorId): void
    {
        $this->db->execute(
            'UPDATE submissions
             SET status = "rejected", reviewed_by_user_id = :editor_id, reviewed_at = NOW()
             WHERE id = :id',
            ['id' => $id, 'editor_id' => $editorId]
        );
    }
}
