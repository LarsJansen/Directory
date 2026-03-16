<?php

namespace App\Models;

use App\Core\Model;

class Submission extends Model
{
    public function create(array $data): int
    {
        $this->db->query(
            'INSERT INTO submissions (proposed_category_id, submitter_name, submitter_email, title, url, description, notes, status, created_at, updated_at)
             VALUES (:proposed_category_id, :submitter_name, :submitter_email, :title, :url, :description, :notes, :status, NOW(), NOW())',
            [
                'proposed_category_id' => $data['proposed_category_id'] ?: null,
                'submitter_name' => $data['submitter_name'] ?: null,
                'submitter_email' => $data['submitter_email'] ?: null,
                'title' => $data['title'],
                'url' => $data['url'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?: null,
                'status' => 'pending',
            ]
        );

        return $this->db->lastInsertId();
    }

    public function pendingCount(): int
    {
        return (int) $this->db->fetchValue('SELECT COUNT(*) FROM submissions WHERE status = "pending"');
    }

    public function pendingList(): array
    {
        return $this->db->fetchAll(
            'SELECT s.*, c.name AS category_name, c.path AS category_path
             FROM submissions s
             LEFT JOIN categories c ON c.id = s.proposed_category_id
             ORDER BY s.created_at ASC'
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT s.*, c.name AS category_name, c.path AS category_path
             FROM submissions s
             LEFT JOIN categories c ON c.id = s.proposed_category_id
             WHERE s.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function markApproved(int $id, int $reviewedByUserId, int $siteId): void
    {
        $this->db->query(
            'UPDATE submissions
             SET status = "approved", reviewed_by_user_id = :reviewed_by_user_id,
                 reviewed_at = NOW(), created_site_id = :created_site_id, updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'reviewed_by_user_id' => $reviewedByUserId,
                'created_site_id' => $siteId,
            ]
        );
    }

    public function markRejected(int $id, int $reviewedByUserId): void
    {
        $this->db->query(
            'UPDATE submissions
             SET status = "rejected", reviewed_by_user_id = :reviewed_by_user_id,
                 reviewed_at = NOW(), updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'reviewed_by_user_id' => $reviewedByUserId,
            ]
        );
    }
}
