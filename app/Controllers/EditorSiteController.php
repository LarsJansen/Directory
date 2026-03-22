<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Site;
use App\Models\SiteCheck;

class EditorSiteController extends Controller
{
    public function index(): void
    {
        $this->requireEditor();

        $siteModel = new Site($this->db);
        $categoryModel = new Category($this->db);
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $check = trim((string) ($_GET['check'] ?? ''));
        $categoryId = max(0, (int) ($_GET['category_id'] ?? 0));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) config('per_page', 20);
        $total = $siteModel->editorCount(
            $q !== '' ? $q : null,
            $status !== '' ? $status : null,
            $categoryId > 0 ? $categoryId : null,
            $check !== '' ? $check : null
        );
        $pagination = build_pagination($total, $page, $perPage);

        $this->view('editor/sites/index', [
            'pageTitle' => 'Manage Sites',
            'sites' => $siteModel->editorList(
                $pagination['per_page'],
                $pagination['offset'],
                $q !== '' ? $q : null,
                $status !== '' ? $status : null,
                $categoryId > 0 ? $categoryId : null,
                $check !== '' ? $check : null
            ),
            'pagination' => $pagination,
            'query' => $q,
            'status' => $status,
            'check' => $check,
            'categoryId' => $categoryId,
            'categories' => $categoryModel->allActive(),
        ]);
    }

    public function dead(): void
    {
        $this->requireEditor();

        $siteModel = new Site($this->db);
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) config('per_page', 20);
        $total = $siteModel->deadCount($q !== '' ? $q : null);
        $pagination = build_pagination($total, $page, $perPage);

        $this->view('editor/sites/dead', [
            'pageTitle' => 'Dead Site Queue',
            'sites' => $siteModel->deadList($pagination['per_page'], $pagination['offset'], $q !== '' ? $q : null),
            'pagination' => $pagination,
            'query' => $q,
        ]);
    }

    public function checks(): void
    {
        $this->requireEditor();

        $siteCheckModel = new SiteCheck($this->db);
        $q = trim((string) ($_GET['q'] ?? ''));
        $result = trim((string) ($_GET['result'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) config('per_page', 20);
        $total = $siteCheckModel->countRecentHttpChecks($result !== '' ? $result : null, $q !== '' ? $q : null);
        $pagination = build_pagination($total, $page, $perPage);

        $this->view('editor/sites/checks', [
            'pageTitle' => 'Site Checks',
            'rows' => $siteCheckModel->recentHttpChecks($pagination['per_page'], $pagination['offset'], $result !== '' ? $result : null, $q !== '' ? $q : null),
            'pagination' => $pagination,
            'query' => $q,
            'result' => $result,
        ]);
    }

    public function duplicates(): void
    {
        $this->requireEditor();

        $siteModel = new Site($this->db);
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) config('per_page', 20);
        $total = $siteModel->duplicateGroupCount($q !== '' ? $q : null);
        $pagination = build_pagination($total, $page, $perPage);

        $this->view('editor/sites/duplicates', [
            'pageTitle' => 'Duplicate URLs',
            'rows' => $siteModel->duplicateGroups($pagination['per_page'], $pagination['offset'], $q !== '' ? $q : null),
            'pagination' => $pagination,
            'query' => $q,
        ]);
    }

    public function edit(int $id): void
    {
        $this->requireEditor();

        $siteModel = new Site($this->db);
        $siteCheckModel = new SiteCheck($this->db);
        $categoryModel = new Category($this->db);
        $site = $siteModel->findByIdWithLatestCheck($id);

        if (!$site) {
            $this->notFound('Site not found.');
            return;
        }

        $this->view('editor/sites/edit', [
            'pageTitle' => 'Edit Site',
            'site' => $site,
            'latestCheck' => $siteCheckModel->latestForSite($id),
            'categories' => $categoryModel->allActive(),
        ]);
    }

    public function update(int $id): void
    {
        $this->requireEditor();
        $this->verifyCsrf();

        $siteModel = new Site($this->db);
        $auditLog = new AuditLog($this->db);
        $site = $siteModel->findById($id);

        if (!$site) {
            $this->notFound('Site not found.');
            return;
        }

        $data = [
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'slug' => slugify((string) ($_POST['slug'] ?? $_POST['title'] ?? '')),
            'url' => trim((string) ($_POST['url'] ?? '')),
            'normalized_url' => normalize_url((string) ($_POST['url'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'active'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'original_title' => trim((string) ($_POST['original_title'] ?? '')),
            'original_description' => trim((string) ($_POST['original_description'] ?? '')),
            'original_url' => trim((string) ($_POST['original_url'] ?? '')),
        ];

        if ($data['category_id'] <= 0 || $data['title'] === '' || $data['url'] === '' || $data['description'] === '') {
            flash('error', 'Category, title, URL, and description are required.');
            $this->redirect('/editor/sites/' . $id . '/edit');
        }

        $duplicate = $siteModel->findByNormalizedUrl($data['normalized_url'], $id);
        if ($duplicate) {
            flash('error', 'Another site already uses that normalized URL.');
            $this->redirect('/editor/sites/' . $id . '/edit');
        }

        $siteModel->update($id, $data);

        $auditLog->log((int) current_user()['id'], 'site', $id, 'updated', [
            'old_category_id' => (int) $site['category_id'],
            'new_category_id' => (int) $data['category_id'],
            'old_url' => $site['url'],
            'new_url' => $data['url'],
        ]);

        flash('success', 'Site updated.');
        $this->redirect('/editor/sites');
    }
}
