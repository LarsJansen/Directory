<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;

class EditorCategoryController extends Controller
{
    public function index(array $params = []): void
    {
        $this->requireEditor();
        $categoryModel = new Category($this->db);

        $this->view('editor/categories/index', [
            'pageTitle' => 'Manage Categories',
            'categories' => $categoryModel->all(),
        ]);
    }

    public function create(array $params = []): void
    {
        $this->requireEditor();
        $categoryModel = new Category($this->db);

        $this->view('editor/categories/create', [
            'pageTitle' => 'Create Category',
            'category' => null,
            'parentOptions' => $categoryModel->treeOptions(),
        ]);
    }

    public function store(array $params = []): void
    {
        $this->requireEditor();
        $data = $this->validatedPayload();
        if ($data === null) {
            redirect('/editor/categories/create');
        }

        $categoryModel = new Category($this->db);
        $categoryModel->create($data);

        flash('success', 'Category created.');
        redirect('/editor/categories');
    }

    public function edit(array $params = []): void
    {
        $this->requireEditor();
        $categoryModel = new Category($this->db);
        $category = $categoryModel->find((int) ($params['id'] ?? 0));

        if (!$category) {
            http_response_code(404);
            echo 'Category not found';
            return;
        }

        $this->view('editor/categories/edit', [
            'pageTitle' => 'Edit Category',
            'category' => $category,
            'parentOptions' => $categoryModel->treeOptions((int) $category['id']),
        ]);
    }

    public function update(array $params = []): void
    {
        $this->requireEditor();
        $id = (int) ($params['id'] ?? 0);
        $categoryModel = new Category($this->db);
        $category = $categoryModel->find($id);

        if (!$category) {
            http_response_code(404);
            echo 'Category not found';
            return;
        }

        $data = $this->validatedPayload($id);
        if ($data === null) {
            redirect('/editor/categories/' . $id . '/edit');
        }

        $categoryModel->updateCategory($id, $data);
        flash('success', 'Category updated. Descendant paths were refreshed automatically.');
        redirect('/editor/categories');
    }

    private function validatedPayload(?int $categoryId = null): ?array
    {
        $data = [
            'parent_id' => $_POST['parent_id'] ?? '',
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order' => $_POST['sort_order'] ?? '0',
            'is_active' => $_POST['is_active'] ?? '1',
        ];

        $errors = [];
        if ($data['name'] === '') {
            $errors['name'] = 'Please enter a category name.';
        }
        if ($data['sort_order'] !== '' && !is_numeric($data['sort_order'])) {
            $errors['sort_order'] = 'Sort order must be numeric.';
        }
        if ($categoryId !== null && (string) $data['parent_id'] === (string) $categoryId) {
            $errors['parent_id'] = 'A category cannot be its own parent.';
        }

        if ($errors) {
            $_SESSION['_errors'] = $errors;
            $_SESSION['_old'] = $data;
            return null;
        }

        return $data;
    }
}
