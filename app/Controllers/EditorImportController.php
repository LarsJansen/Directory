<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\ImportBatch;

class EditorImportController extends Controller
{
    public function index(): void
    {
        $this->requireEditor();
        $importModel = new ImportBatch($this->db);

        $this->view('editor/imports/index', [
            'pageTitle' => 'Import Batches',
            'batches' => $importModel->all(),
        ]);
    }

    public function create(): void
    {
        $this->requireEditor();
        $this->view('editor/imports/create', [
            'pageTitle' => 'Create Import Batch',
        ]);
    }

    public function store(): void
    {
        $this->requireEditor();
        $importModel = new ImportBatch($this->db);
        $auditLog = new AuditLog($this->db);

        $sourceName = trim((string) ($_POST['source_name'] ?? 'DMOZ Sample'));
        $sourceVersion = trim((string) ($_POST['source_version'] ?? ''));
        $batchLabel = trim((string) ($_POST['batch_label'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($batchLabel === '') {
            flash('error', 'Batch label is required.');
            $this->redirect('/editor/imports/create');
        }

        $id = $importModel->create([
            'source_name' => $sourceName,
            'source_version' => $sourceVersion,
            'batch_label' => $batchLabel,
            'notes' => $notes,
            'imported_by_user_id' => (int) current_user()['id'],
            'status' => 'running',
        ]);

        $auditLog->log((int) current_user()['id'], 'import_batch', $id, 'created', ['batch_label' => $batchLabel]);
        flash('success', 'Import batch created. You can now load staging rows into it.');
        $this->redirect('/editor/imports/' . $id);
    }

    public function show(int $id): void
    {
        $this->requireEditor();
        $importModel = new ImportBatch($this->db);
        $batch = $importModel->findById($id);

        if (!$batch) {
            $this->notFound('Import batch not found.');
            return;
        }

        $this->view('editor/imports/show', [
            'pageTitle' => 'Import Batch',
            'batch' => $batch,
            'rows' => $importModel->stagingRows($id),
        ]);
    }
}
