<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;

class EditorAuditController extends Controller
{
    public function index(): void
    {
        $this->requireEditor();

        $auditLog = new AuditLog($this->db);
        $entityType = trim((string) ($_GET['entity_type'] ?? ''));
        $action = trim((string) ($_GET['action'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) config('per_page', 20);

        $total = $auditLog->countAll($entityType !== '' ? $entityType : null, $action !== '' ? $action : null);
        $pagination = build_pagination($total, $page, $perPage);

        $this->view('editor/audit/index', [
            'pageTitle' => 'Audit Log',
            'rows' => $auditLog->paginatedList($pagination['per_page'], $pagination['offset'], $entityType !== '' ? $entityType : null, $action !== '' ? $action : null),
            'pagination' => $pagination,
            'entityType' => $entityType,
            'action' => $action,
        ]);
    }
}
