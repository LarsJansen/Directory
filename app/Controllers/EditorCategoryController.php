<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use Throwable;

class EditorCategoryController extends Controller
{
    public function index(): void
    {
        $this->requireEditor();

        $categoryModel = new Category($this->db);

        $this->view('editor/categories/index', [
            'pageTitle' => 'Manage Categories',
            'categories' => $categoryModel->allWithCounts(),
        ]);
    }

    public function create(): void
    {
        $this->requireEditor();

        $categoryModel = new Category($this->db);

        $this->view('editor/categories/create', [
            'pageTitle' => 'Create Category',
            'parentOptions' => $categoryModel->allForParentSelect(),
        ]);
    }

    public function store(): void
    {
        $this->requireEditor();

        $categoryModel = new Category($this->db);

        try {
            $categoryId = $categoryModel->create($_POST);
            $this->writeAudit('category', $categoryId, 'created', [
                'name' => trim((string) ($_POST['name'] ?? '')),
                'parent_id' => $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null,
            ]);

            flash('success', 'Category created successfully.');
            redirect('/editor/categories');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/editor/categories/create');
        }
    }

    public function edit(array $params): void
    {
        $this->requireEditor();

        $categoryModel = new Category($this->db);
        $category = $categoryModel->find((int) ($params['id'] ?? 0));

        if (!$category) {
            flash('error', 'Category not found.');
            redirect('/editor/categories');
        }

        $this->view('editor/categories/edit', [
            'pageTitle' => 'Edit Category',
            'category' => $category,
            'breadcrumbs' => $categoryModel->breadcrumbsByPath((string) $category['path']),
            'parentOptions' => $categoryModel->allForParentSelect((int) $category['id']),
        ]);
    }

    public function update(array $params): void
    {
        $this->requireEditor();

        $categoryId = (int) ($params['id'] ?? 0);
        $categoryModel = new Category($this->db);

        try {
            $result = $categoryModel->updateCategory($categoryId, $_POST);

            $old = $result['old'] ?? [];
            $new = $result['new'] ?? [];

            $this->writeAudit('category', $categoryId, 'updated', [
                'old_name' => $old['name'] ?? null,
                'new_name' => $new['name'] ?? null,
                'old_parent_id' => $old['parent_id'] ?? null,
                'new_parent_id' => $new['parent_id'] ?? null,
                'old_path' => $old['path'] ?? null,
                'new_path' => $new['path'] ?? null,
            ]);

            flash('success', 'Category updated successfully.');
            redirect('/editor/categories/' . $categoryId . '/edit');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/editor/categories/' . $categoryId . '/edit');
        }
    }

    private function writeAudit(string $entityType, int $entityId, string $action, array $details = []): void
    {
        $user = current_user();

        $this->db->execute(
            'INSERT INTO audit_log (user_id, entity_type, entity_id, action, details) VALUES (:user_id, :entity_type, :entity_id, :action, :details)',
            [
                'user_id' => $user['id'] ?? null,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'details' => !empty($details) ? json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            ]
        );
    }
}
