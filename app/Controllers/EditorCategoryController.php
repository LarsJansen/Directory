<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use RuntimeException;

class EditorCategoryController extends Controller
{
    public function index(): void
    {
        $this->requireEditor();
        $categoryModel = new Category($this->db);

        $query = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;

        $result = $categoryModel->searchForEditor($query, $page, $perPage);

        $this->view('editor/categories/index', [
            'pageTitle' => 'Manage Categories',
            'categories' => $result['rows'],
            'pagination' => $result['pagination'],
            'query' => $query,
            'total' => $result['total'],
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

    public function move(int $id): void
    {
        $this->requireEditor();
        $categoryModel = new Category($this->db);
        $category = $categoryModel->findById($id);

        if (!$category) {
            $this->notFound('Category not found.');
            return;
        }

        $previewParentId = null;
        if (array_key_exists('parent_id', $_GET)) {
            $previewParentId = (int) ($_GET['parent_id'] ?? 0) ?: null;
        } elseif ($category['parent_id'] !== null) {
            $previewParentId = (int) $category['parent_id'];
        }

        $preview = null;
        if ($previewParentId !== null || array_key_exists('parent_id', $_GET)) {
            try {
                $preview = $categoryModel->previewMove($id, $previewParentId);
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
            }
        }

        $this->view('editor/categories/move', [
            'pageTitle' => 'Move Category Branch',
            'category' => $category,
            'targets' => $categoryModel->allMoveTargetsFor($id),
            'branchSummary' => $categoryModel->branchSummary($id),
            'selectedParentId' => $previewParentId,
            'preview' => $preview,
        ]);
    }

    public function moveUpdate(int $id): void
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

        $newParentId = (int) ($_POST['parent_id'] ?? 0) ?: null;

        try {
            $result = $categoryModel->moveBranch($id, $newParentId);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $redirectPath = '/editor/categories/' . $id . '/move';
            if ($newParentId !== null) {
                $redirectPath .= '?parent_id=' . $newParentId;
            }
            $this->redirect($redirectPath);
            return;
        }

        $auditLog->log((int) current_user()['id'], 'category', $id, 'moved_branch', [
            'old_parent_id' => $result['old']['parent_id'],
            'new_parent_id' => $result['new']['parent_id'],
            'old_path' => $result['old']['path'],
            'new_path' => $result['new']['path'],
            'descendant_count' => $result['summary']['descendant_count'],
            'site_count_in_branch' => $result['summary']['site_count_in_branch'],
        ]);

        flash('success', 'Category branch moved successfully.');
        $this->redirect('/editor/categories');
    }


    public function merge(int $id): void
    {
        $this->requireEditor();
        $categoryModel = new Category($this->db);
        $category = $categoryModel->findById($id);

        if (!$category) {
            $this->notFound('Category not found.');
            return;
        }

        $selectedTargetId = (int) ($_GET['target_id'] ?? 0) ?: null;
        $preview = null;

        if ($selectedTargetId !== null) {
            try {
                $preview = $categoryModel->previewMerge($id, $selectedTargetId);
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
            }
        }

        $this->view('editor/categories/merge', [
            'pageTitle' => 'Merge Category',
            'category' => $category,
            'targets' => $categoryModel->allMergeTargetsFor($id),
            'summary' => $categoryModel->mergeSummary($id),
            'selectedTargetId' => $selectedTargetId,
            'preview' => $preview,
        ]);
    }

    public function mergeUpdate(int $id): void
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

        $targetId = (int) ($_POST['target_id'] ?? 0);
        if ($targetId <= 0) {
            flash('error', 'Please select a valid target category.');
            $this->redirect('/editor/categories/' . $id . '/merge');
            return;
        }

        try {
            $result = $categoryModel->mergeInto($id, $targetId);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('/editor/categories/' . $id . '/merge?target_id=' . $targetId);
            return;
        }

        $auditLog->log((int) current_user()['id'], 'category', $id, 'merged_into', [
            'source_path' => $result['source']['path'],
            'target_id' => $result['target']['id'],
            'target_path' => $result['target']['path'],
            'moved_direct_site_count' => $result['summary']['direct_site_count'],
            'moved_direct_child_count' => $result['summary']['direct_child_count'],
            'descendant_count' => $result['summary']['descendant_count'],
            'site_count_in_branch' => $result['summary']['site_count_in_branch'],
        ]);

        flash('success', 'Category merged successfully.');
        $this->redirect('/editor/categories');
    }

    public function delete(int $id): void
    {
        $this->requireEditor();
        $categoryModel = new Category($this->db);
        $category = $categoryModel->findById($id);

        if (!$category) {
            $this->notFound('Category not found.');
            return;
        }

        $this->view('editor/categories/delete', [
            'pageTitle' => 'Delete Category',
            'category' => $category,
            'summary' => $categoryModel->deleteSummary($id),
        ]);
    }

    public function destroy(int $id): void
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

        $mode = (string) ($_POST['mode'] ?? 'empty');
        $confirmBranchDelete = isset($_POST['confirm_branch_delete']);

        if ($mode === 'delete_branch' && !$confirmBranchDelete) {
            flash('error', 'Please confirm that you want to delete the entire category branch.');
            $this->redirect('/editor/categories/' . $id . '/delete');
            return;
        }

        try {
            $result = $categoryModel->deleteCategory($id, $mode);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('/editor/categories/' . $id . '/delete');
            return;
        }

        $auditLog->log((int) current_user()['id'], 'category', $id, 'deleted', [
            'mode' => $result['mode'],
            'path' => $result['category']['path'],
            'parent_id' => $result['category']['parent_id'],
            'direct_child_count' => $result['summary']['direct_child_count'],
            'descendant_count' => $result['summary']['descendant_count'],
            'direct_site_count' => $result['summary']['direct_site_count'],
            'site_count_in_branch' => $result['summary']['site_count_in_branch'],
            'moved_site_count' => $result['moved_site_count'] ?? 0,
            'deleted_category_count' => $result['deleted_category_count'] ?? 1,
            'deleted_site_count' => $result['deleted_site_count'] ?? 0,
        ]);

        $message = match ($result['mode']) {
            'move_sites_to_parent' => 'Category deleted and sites moved to parent.',
            'delete_branch' => 'Category branch deleted successfully.',
            default => 'Category deleted successfully.',
        };

        flash('success', $message);
        $this->redirect('/editor/categories');
    }
}
