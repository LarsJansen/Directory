<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Submission;
use App\Models\Site;

class EditorSubmissionController extends Controller
{
    public function dashboard(array $params = []): void
    {
        $this->requireEditor();
        $submissionModel = new Submission($this->db);
        $siteModel = new Site($this->db);

        $this->view('editor/submissions/dashboard', [
            'pageTitle' => 'Editor Dashboard',
            'counts' => $submissionModel->counts(),
            'siteCount' => count($siteModel->allForEditor()),
        ]);
    }

    public function index(array $params = []): void
    {
        $this->requireEditor();
        $submissionModel = new Submission($this->db);

        $this->view('editor/submissions/index', [
            'pageTitle' => 'Pending Submissions',
            'submissions' => $submissionModel->pending(),
        ]);
    }

    public function show(array $params = []): void
    {
        $this->requireEditor();
        $submissionModel = new Submission($this->db);
        $siteModel = new Site($this->db);
        $submission = $submissionModel->find((int) ($params['id'] ?? 0));

        if (!$submission) {
            http_response_code(404);
            echo 'Submission not found';
            return;
        }

        $duplicates = $submission['url'] ? $siteModel->duplicatesForUrl($submission['url']) : [];

        $this->view('editor/submissions/show', [
            'pageTitle' => 'Review Submission',
            'submission' => $submission,
            'duplicates' => $duplicates,
        ]);
    }

    public function approve(array $params = []): void
    {
        $this->requireEditor();
        $submissionModel = new Submission($this->db);
        $siteModel = new Site($this->db);
        $submission = $submissionModel->find((int) ($params['id'] ?? 0));

        if (!$submission || $submission['status'] !== 'pending') {
            flash('error', 'Submission could not be approved.');
            redirect('/editor/submissions');
        }

        $duplicates = $siteModel->duplicatesForUrl($submission['url']);
        if ($duplicates) {
            flash('error', 'A live site with the same normalized URL already exists. Edit the existing listing instead.');
            redirect('/editor/submissions/' . (int) $submission['id']);
        }

        $editor = current_editor();
        $siteId = $siteModel->createFromSubmission($submission, (int) $editor['id']);
        $submissionModel->markApproved((int) $submission['id'], (int) $editor['id'], $siteId);

        flash('success', 'Submission approved and published.');
        redirect('/editor/submissions');
    }

    public function reject(array $params = []): void
    {
        $this->requireEditor();
        $submissionModel = new Submission($this->db);
        $submission = $submissionModel->find((int) ($params['id'] ?? 0));

        if (!$submission || $submission['status'] !== 'pending') {
            flash('error', 'Submission could not be rejected.');
            redirect('/editor/submissions');
        }

        $editor = current_editor();
        $submissionModel->markRejected((int) $submission['id'], (int) $editor['id']);

        flash('success', 'Submission rejected.');
        redirect('/editor/submissions');
    }
}
