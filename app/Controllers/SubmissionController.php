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
        $this->view('submit/create', [
            'pageTitle' => 'Submit a Site',
            'categories' => $categoryModel->allActive(),
        ]);
    }

    public function store(): void
    {
        $data = [
            'proposed_category_id' => (int) ($_POST['proposed_category_id'] ?? 0),
            'submitter_name' => trim((string) ($_POST['submitter_name'] ?? '')),
            'submitter_email' => trim((string) ($_POST['submitter_email'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'url' => trim((string) ($_POST['url'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        if ($data['title'] === '' || $data['url'] === '' || $data['description'] === '') {
            flash('error', 'Title, URL, and description are required.');
            $this->redirect('/submit');
        }

        $submissionModel = new Submission($this->db);
        $submissionModel->create($data);

        flash('success', 'Thanks. Your submission has been queued for editorial review.');
        $this->redirect('/submit');
    }
}
