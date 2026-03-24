<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Submission;

class SubmissionController extends Controller
{
    public function create(): void
    {
        $categoryModel = new Category($this->db);
        $_SESSION['submission_form_started_at'] = time();

        $this->view('submit/create', [
            'pageTitle' => 'Submit a Site',
            'categories' => $categoryModel->allActive(),
            'formStartedAt' => $_SESSION['submission_form_started_at'],
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $honeypot = trim((string) ($_POST['website'] ?? ''));
        if ($honeypot !== '') {
            flash('error', 'Submission could not be accepted. Please try again.');
            $this->redirect('/submit');
        }

        $formStartedAt = (int) ($_POST['form_started_at'] ?? 0);
        $sessionStartedAt = (int) ($_SESSION['submission_form_started_at'] ?? 0);
        $effectiveStartedAt = max($formStartedAt, $sessionStartedAt);

        if ($effectiveStartedAt <= 0 || (time() - $effectiveStartedAt) < 4) {
            flash('error', 'Please take a moment to review your submission before sending it.');
            $this->redirect('/submit');
        }

        $lastSubmissionAt = (int) ($_SESSION['last_submission_at'] ?? 0);
        if ($lastSubmissionAt > 0 && (time() - $lastSubmissionAt) < 30) {
            flash('error', 'Please wait a little before submitting another site.');
            $this->redirect('/submit');
        }

        $data = [
            'proposed_category_id' => (int) ($_POST['proposed_category_id'] ?? 0),
            'submitter_name' => trim((string) ($_POST['submitter_name'] ?? '')),
            'submitter_email' => trim((string) ($_POST['submitter_email'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'url' => trim((string) ($_POST['url'] ?? '')),
            'description' => sanitize_plain_text((string) ($_POST['description'] ?? '')),
            'notes' => sanitize_plain_text((string) ($_POST['notes'] ?? '')),
        ];

        if ($data['title'] === '' || $data['url'] === '' || $data['description'] === '') {
            flash('error', 'Title, URL, and description are required.');
            $this->redirect('/submit');
        }

        $submissionModel = new Submission($this->db);
        $submissionModel->create($data);

        $_SESSION['last_submission_at'] = time();
        unset($_SESSION['submission_form_started_at']);

        flash('success', 'Thanks. Your submission has been queued for editorial review.');
        $this->redirect('/submit');
    }
}
