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
            'description' => sanitize_plain_text((string) ($_POST['description'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'active'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'original_title' => trim((string) ($_POST['original_title'] ?? '')),
            'original_description' => sanitize_plain_text((string) ($_POST['original_description'] ?? '')),
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

    public function destroy(int $id): void
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

        $siteModel->delete($id);

        $auditLog->log((int) current_user()['id'], 'site', $id, 'deleted', [
            'title' => $site['title'],
            'url' => $site['url'],
            'normalized_url' => $site['normalized_url'],
            'category_id' => $site['category_id'],
            'status' => $site['status'],
            'is_active' => $site['is_active'],
        ]);

        flash('success', 'Site deleted successfully.');
        $this->redirect('/editor/sites');
    }

    public function bulk(): void
    {
        $this->requireEditor();
        $this->verifyCsrf();

        $siteModel = new Site($this->db);
        $auditLog = new AuditLog($this->db);

        $action = trim((string) ($_POST['bulk_action'] ?? ''));
        $ids = $_POST['site_ids'] ?? [];
        $returnTo = trim((string) ($_POST['return_to'] ?? '/editor/sites'));
        $returnTo = str_starts_with($returnTo, '/') ? $returnTo : '/editor/sites';

        $allowedActions = ['delete', 'deactivate', 'reactivate', 'deactivate_flagged_filtered'];
        if (!in_array($action, $allowedActions, true)) {
            flash('error', 'Please choose a valid bulk action.');
            $this->redirect($returnTo);
        }

        $selectedIds = array_values(array_unique(array_filter(array_map('intval', is_array($ids) ? $ids : []), fn ($id) => $id > 0)));

        if ($action !== 'deactivate_flagged_filtered' && empty($selectedIds)) {
            flash('error', 'Select at least one site first.');
            $this->redirect($returnTo);
        }

        if ($action === 'delete') {
            foreach ($selectedIds as $id) {
                $site = $siteModel->findById($id);
                if (!$site) {
                    continue;
                }

                $siteModel->delete($id);
                $auditLog->log((int) current_user()['id'], 'site', $id, 'deleted', [
                    'title' => $site['title'],
                    'url' => $site['url'],
                    'normalized_url' => $site['normalized_url'],
                    'category_id' => $site['category_id'],
                    'status' => $site['status'],
                    'is_active' => $site['is_active'],
                    'bulk' => true,
                ]);
            }

            flash('success', 'Selected sites deleted.');
            $this->redirect($returnTo);
        }

        if ($action === 'deactivate' || $action === 'reactivate') {
            $isActive = $action === 'reactivate' ? 1 : 0;
            $newStatus = $action === 'reactivate' ? 'active' : null;
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));

            if ($newStatus !== null) {
                $this->db->query(
                    "UPDATE sites SET is_active = ?, status = ?, updated_at = NOW() WHERE id IN ($placeholders)",
                    array_merge([$isActive, $newStatus], $selectedIds)
                );
            } else {
                $this->db->query(
                    "UPDATE sites SET is_active = ?, updated_at = NOW() WHERE id IN ($placeholders)",
                    array_merge([$isActive], $selectedIds)
                );
            }

            foreach ($selectedIds as $id) {
                $auditLog->log((int) current_user()['id'], 'site', $id, $action === 'reactivate' ? 'reactivated' : 'deactivated', [
                    'bulk' => true,
                ]);
            }

            flash('success', $action === 'reactivate' ? 'Selected sites reactivated.' : 'Selected sites deactivated.');
            $this->redirect($returnTo);
        }

        if ($action === 'deactivate_flagged_filtered') {
            $q = trim((string) ($_POST['filter_q'] ?? ''));
            $status = trim((string) ($_POST['filter_status'] ?? ''));
            $check = trim((string) ($_POST['filter_check'] ?? ''));
            $categoryId = max(0, (int) ($_POST['filter_category_id'] ?? 0));

            $rows = $siteModel->editorList(
                1000000,
                0,
                $q !== '' ? $q : null,
                $status !== '' ? $status : null,
                $categoryId > 0 ? $categoryId : null,
                $check !== '' ? $check : null
            );

            $flaggedIds = [];
            foreach ($rows as $row) {
                if (($row['status'] ?? '') === 'flagged') {
                    $flaggedIds[] = (int) $row['id'];
                }
            }
            $flaggedIds = array_values(array_unique(array_filter($flaggedIds, fn ($id) => $id > 0)));

            if (empty($flaggedIds)) {
                flash('error', 'No flagged sites matched the current filters.');
                $this->redirect($returnTo);
            }

            $placeholders = implode(',', array_fill(0, count($flaggedIds), '?'));
            $this->db->query(
                "UPDATE sites SET is_active = 0, updated_at = NOW() WHERE id IN ($placeholders)",
                $flaggedIds
            );

            foreach ($flaggedIds as $id) {
                $auditLog->log((int) current_user()['id'], 'site', $id, 'deactivated', [
                    'bulk' => true,
                    'source' => 'deactivate_flagged_filtered',
                ]);
            }

            flash('success', count($flaggedIds) . ' flagged site(s) deactivated.');
            $this->redirect($returnTo);
        }

        flash('error', 'Bulk action could not be completed.');
        $this->redirect($returnTo);
    }

    public function deleteAllDead(): void
    {
        $this->requireEditor();
        $this->verifyCsrf();

        $siteModel = new Site($this->db);
        $auditLog = new AuditLog($this->db);
        $q = trim((string) ($_POST['q'] ?? ''));
        $deadIds = $siteModel->deadIds($q !== '' ? $q : null);

        if (empty($deadIds)) {
            flash('error', 'No dead or failing sites matched the current queue.');
            $this->redirect('/editor/sites/dead');
        }

        foreach ($deadIds as $id) {
            $site = $siteModel->findById($id);
            if (!$site) {
                continue;
            }

            $siteModel->delete($id);
            $auditLog->log((int) current_user()['id'], 'site', $id, 'deleted', [
                'title' => $site['title'],
                'url' => $site['url'],
                'normalized_url' => $site['normalized_url'],
                'category_id' => $site['category_id'],
                'status' => $site['status'],
                'is_active' => $site['is_active'],
                'bulk' => true,
                'scope' => 'dead_queue',
            ]);
        }

        flash('success', count($deadIds) . ' dead/failing site(s) deleted.');
        $this->redirect('/editor/sites/dead');
    }

    public function deactivateAllDead(): void
    {
        $this->requireEditor();
        $this->verifyCsrf();

        $siteModel = new Site($this->db);
        $q = trim((string) ($_POST['q'] ?? ''));
        $ids = $siteModel->deadIds($q !== '' ? $q : null);

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $this->db->query(
                "UPDATE sites SET status = ?, is_active = 0, updated_at = NOW() WHERE id IN ($placeholders)",
                array_merge(['hidden'], $ids)
            );

            $auditLog = new AuditLog($this->db);
            foreach ($ids as $id) {
                $auditLog->log((int) current_user()['id'], 'site', $id, 'deactivated', [
                    'bulk' => true,
                    'source' => 'deactivate_dead_queue',
                ]);
            }
        }

        flash('success', count($ids) . ' dead/failing site(s) deactivated.');
        $this->redirect('/editor/sites/dead');
    }

}
