<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Submission;
use App\Models\Site;

class SubmissionController extends Controller
{
    public function create(array $params = []): void
    {
        $categoryModel = new Category($this->db);

        $this->view('submit/create', [
            'pageTitle' => 'Suggest a Site',
            'categories' => $categoryModel->treeOptions(),
        ]);
    }

    public function store(array $params = []): void
    {
        $data = [
            'proposed_category_id' => $_POST['proposed_category_id'] ?? '',
            'submitter_name' => trim($_POST['submitter_name'] ?? ''),
            'submitter_email' => trim($_POST['submitter_email'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'url' => trim($_POST['url'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        $errors = [];

        if ($data['proposed_category_id'] === '') {
            $errors['proposed_category_id'] = 'Please choose a category.';
        }
        if ($data['title'] === '') {
            $errors['title'] = 'Please provide a site title.';
        }
        if ($data['url'] === '' || !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $errors['url'] = 'Please provide a valid URL.';
        }
        if ($data['description'] === '') {
            $errors['description'] = 'Please provide a short description.';
        }

        $siteModel = new Site($this->db);
        $duplicates = [];
        if ($data['url'] !== '' && filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $duplicates = $siteModel->duplicatesForUrl($data['url']);
            if ($duplicates) {
                $errors['url'] = 'That site already appears to be listed in the directory.';
            }
        }

        if ($errors) {
            $_SESSION['_errors'] = $errors;
            $_SESSION['_old'] = $data;
            redirect('/submit');
        }

        $submissionModel = new Submission($this->db);
        $submissionModel->create($data);

        flash('success', 'Thank you. Your site has been submitted for editor review.');
        redirect('/submit');
    }
}
