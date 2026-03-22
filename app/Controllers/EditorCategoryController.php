<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Category;

class EditorCategoryController extends Controller
{
    public function index(): void
    {
        $this->requireEditor();
        $categoryModel = new Category($this->db);

        $this->view('editor/categories/index', [
            'pageTitle' => 'Manage Categories',
            'categories' => $categoryModel->allForEditor(),
        ]);
    }

    public function create(): void
    {
        $this->requireEditor();
        $categoryModel = new Category($this->db);

        $this->view('editor/categories/create', [
            'pageTitle' => 'Create Category',
            'categories' => $categoryModel->allActive(),
        ]);
    }

    public function store(): void
    {
        $this->requireEditor();
        $this->verifyCsrf();
        $categoryModel = new Category($this->db);
        $auditLog = new AuditLog($this->db);

        $parentId = (int) ($_POST['parent_id'] ?? 0) ?: null;
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = slugify((string) ($_POST['slug'] ?? $name));
        $path = $categoryModel->buildPath($parentId, $slug);
        $description = trim((string) ($_POST['description'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $slug === '') {
            flash('error', 'Name and slug are required.');
            $this->redirect('/editor/categories/create');
        }

        if ($categoryModel->existsByPath($path)) {
            flash('error', 'That category path already exists.');
            $this->redirect('/editor/categories/create');
        }

        $id = $categoryModel->create([
            'parent_id' => $parentId,
            'slug' => $slug,
            'path' => $path,
            'name' => $name,
            'description' => $description,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ]);

        $auditLog->log((int) current_user()['id'], 'category', $id, 'created', ['path' => $path]);
        flash('success', 'Category created.');
        $this->redirect('/editor/categories');
    }

    public function edit(int $id): void
    {
        $this->requireEditor();
        $categoryModel = new Category($this->db);
        $category = $categoryModel->findById($id);

        if (!$category) {
            $this->notFound('Category not found.');
            return;
        }

        $this->view('editor/categories/edit', [
            'pageTitle' => 'Edit Category',
            'category' => $category,
            'categories' => $categoryModel->allActive(),
        ]);
    }

    public function update(int $id): void
    {
        $this->requireEditor();
        $this->verifyCsrf();
        $categoryModel = new Category($this->db);
        $auditLog = new AuditLog($this->db);

        $category = $categoryModel->findById($id);
        if (!$category) {
            $this->notFound('Category not found.');
            return;
        }

        $parentId = (int) ($_POST['parent_id'] ?? 0) ?: null;
        if ($parentId === $id || ($parentId && $categoryModel->isDescendant($parentId, $id))) {
            flash('error', 'Invalid parent category selection.');
            $this->redirect('/editor/categories/' . $id . '/edit');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = slugify((string) ($_POST['slug'] ?? $name));
        $path = $categoryModel->buildPath($parentId, $slug);
        $description = trim((string) ($_POST['description'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $slug === '') {
            flash('error', 'Name and slug are required.');
            $this->redirect('/editor/categories/' . $id . '/edit');
        }

        if ($categoryModel->existsByPath($path, $id)) {
            flash('error', 'Another category already uses that path.');
            $this->redirect('/editor/categories/' . $id . '/edit');
        }

        $categoryModel->update($id, [
            'parent_id' => $parentId,
            'slug' => $slug,
            'path' => $path,
            'name' => $name,
            'description' => $description,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ]);

        $auditLog->log((int) current_user()['id'], 'category', $id, 'updated', [
            'old_path' => $category['path'],
            'new_path' => $path,
        ]);

        flash('success', 'Category updated.');
        $this->redirect('/editor/categories');
    }
}
