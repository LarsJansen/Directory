<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\Site;
use App\Models\Submission;

class EditorSubmissionController extends Controller
{
    public function dashboard(): void
    {
        $this->requireEditor();

        $submissionModel = new Submission($this->db);
        $siteModel = new Site($this->db);
        $categoryModel = new Category($this->db);
        $importModel = new ImportBatch($this->db);
        $auditLog = new AuditLog($this->db);

        $this->view('editor/dashboard', [
            'pageTitle' => 'Editor Dashboard',
            'pendingCount' => $submissionModel->pendingCount(),
            'siteCount' => $siteModel->editorCount(),
            'categoryCount' => count($categoryModel->allForEditor()),
            'importBatchCount' => count($importModel->all()),
            'duplicateCount' => $siteModel->duplicateGroupCount(),
            'recentAudit' => $auditLog->recent(8),
            'recentSites' => $siteModel->recentUpdated(8),
        ]);
    }

    public function index(): void
    {
        $this->requireEditor();

        $submissionModel = new Submission($this->db);
        $this->view('editor/submissions/index', [
            'pageTitle' => 'Moderate Submissions',
            'submissions' => $submissionModel->pendingList(),
        ]);
    }

    public function show(int $id): void
    {
        $this->requireEditor();

        $submissionModel = new Submission($this->db);
        $categoryModel = new Category($this->db);
        $submission = $submissionModel->findById($id);

        if (!$submission) {
            $this->notFound('Submission not found.');
            return;
        }

        $this->view('editor/submissions/show', [
            'pageTitle' => 'Review Submission',
            'submission' => $submission,
            'categories' => $categoryModel->allActive(),
        ]);
    }

    public function approve(int $id): void
    {
        $this->requireEditor();

        $submissionModel = new Submission($this->db);
        $siteModel = new Site($this->db);
        $auditLog = new AuditLog($this->db);

        $submission = $submissionModel->findById($id);
        if (!$submission || $submission['status'] !== 'pending') {
            flash('error', 'Submission could not be approved.');
            $this->redirect('/editor/submissions');
        }

        $categoryId = (int) ($_POST['category_id'] ?? $submission['proposed_category_id']);
        if ($categoryId <= 0) {
            flash('error', 'Please choose a category before approving.');
            $this->redirect('/editor/submissions/' . $id);
        }

        $submission['proposed_category_id'] = $categoryId;
        $duplicate = $siteModel->findByNormalizedUrl(normalize_url($submission['url']));
        if ($duplicate) {
            flash('error', 'A live site with this normalized URL already exists.');
            $this->redirect('/editor/submissions/' . $id);
        }

        $siteId = $siteModel->createFromSubmission($submission);
        $userId = (int) current_user()['id'];
        $submissionModel->markApproved($id, $userId, $siteId);
        $auditLog->log($userId, 'submission', $id, 'approved', ['site_id' => $siteId]);

        flash('success', 'Submission approved and published.');
        $this->redirect('/editor/submissions');
    }

    public function reject(int $id): void
    {
        $this->requireEditor();

        $submissionModel = new Submission($this->db);
        $auditLog = new AuditLog($this->db);
        $submission = $submissionModel->findById($id);

        if (!$submission || $submission['status'] !== 'pending') {
            flash('error', 'Submission could not be rejected.');
            $this->redirect('/editor/submissions');
        }

        $userId = (int) current_user()['id'];
        $submissionModel->markRejected($id, $userId);
        $auditLog->log($userId, 'submission', $id, 'rejected');

        flash('success', 'Submission rejected.');
        $this->redirect('/editor/submissions');
    }

    public function bulk(): void
    {
        $this->requireEditor();

        $ids = array_values(array_filter(array_map('intval', $_POST['submission_ids'] ?? [])));
        $action = trim((string) ($_POST['bulk_action'] ?? ''));

        if (!$ids || !in_array($action, ['approve', 'reject'], true)) {
            flash('error', 'Choose at least one submission and a valid bulk action.');
            $this->redirect('/editor/submissions');
        }

        $submissionModel = new Submission($this->db);
        $siteModel = new Site($this->db);
        $auditLog = new AuditLog($this->db);
        $userId = (int) current_user()['id'];

        $processed = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $submission = $submissionModel->findById($id);
            if (!$submission || $submission['status'] !== 'pending') {
                $skipped++;
                continue;
            }

            if ($action === 'reject') {
                $submissionModel->markRejected($id, $userId);
                $auditLog->log($userId, 'submission', $id, 'rejected');
                $processed++;
                continue;
            }

            $categoryId = (int) $submission['proposed_category_id'];
            if ($categoryId <= 0) {
                $skipped++;
                continue;
            }

            $duplicate = $siteModel->findByNormalizedUrl(normalize_url($submission['url']));
            if ($duplicate) {
                $skipped++;
                continue;
            }

            $submission['proposed_category_id'] = $categoryId;
            $siteId = $siteModel->createFromSubmission($submission);
            $submissionModel->markApproved($id, $userId, $siteId);
            $auditLog->log($userId, 'submission', $id, 'approved', ['site_id' => $siteId, 'mode' => 'bulk']);
            $processed++;
        }

        flash('success', 'Bulk action complete. Processed: ' . $processed . '. Skipped: ' . $skipped . '.');
        $this->redirect('/editor/submissions');
    }
}
