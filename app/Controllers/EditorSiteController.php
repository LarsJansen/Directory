<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Site;

class EditorSiteController extends Controller
{
    public function index(array $params = []): void
    {
        $this->requireEditor();
        $siteModel = new Site($this->db);

        $this->view('editor/sites/index', [
            'pageTitle' => 'Manage Sites',
            'sites' => $siteModel->allForEditor(),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->requireEditor();
        $siteModel = new Site($this->db);
        $categoryModel = new Category($this->db);
        $site = $siteModel->find((int) ($params['id'] ?? 0));

        if (!$site) {
            http_response_code(404);
            echo 'Site not found';
            return;
        }

        $this->view('editor/sites/edit', [
            'pageTitle' => 'Edit Site',
            'site' => $site,
            'parentOptions' => $categoryModel->treeOptions(),
            'duplicates' => $siteModel->duplicatesForUrl($site['url'], (int) $site['id']),
        ]);
    }

    public function update(array $params = []): void
    {
        $this->requireEditor();
        $id = (int) ($params['id'] ?? 0);
        $siteModel = new Site($this->db);
        $site = $siteModel->find($id);

        if (!$site) {
            http_response_code(404);
            echo 'Site not found';
            return;
        }

        $data = [
            'category_id' => $_POST['category_id'] ?? '',
            'title' => trim($_POST['title'] ?? ''),
            'url' => trim($_POST['url'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'review_notes' => trim($_POST['review_notes'] ?? ''),
        ];

        $errors = [];
        if ($data['category_id'] === '') {
            $errors['category_id'] = 'Please select a category.';
        }
        if ($data['title'] === '') {
            $errors['title'] = 'Please provide a title.';
        }
        if ($data['url'] === '' || !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $errors['url'] = 'Please provide a valid URL.';
        }
        if ($data['description'] === '') {
            $errors['description'] = 'Please provide a description.';
        }
        if (!in_array($data['status'], ['active', 'dead', 'flagged'], true)) {
            $errors['status'] = 'Invalid status.';
        }

        $duplicates = [];
        if (!isset($errors['url'])) {
            $duplicates = $siteModel->duplicatesForUrl($data['url'], $id);
            if ($duplicates) {
                $errors['url'] = 'Another listing already uses this normalized URL.';
            }
        }

        if ($errors) {
            $_SESSION['_errors'] = $errors;
            $_SESSION['_old'] = $data;
            redirect('/editor/sites/' . $id . '/edit');
        }

        $siteModel->updateSite($id, $data);
        flash('success', 'Site updated.');
        redirect('/editor/sites');
    }
}
