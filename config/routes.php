<?php

use App\Controllers\HomeController;
use App\Controllers\CategoryController;
use App\Controllers\SubmissionController;
use App\Controllers\EditorAuthController;
use App\Controllers\EditorSubmissionController;
use App\Controllers\EditorCategoryController;
use App\Controllers\EditorSiteController;
use App\Controllers\EditorImportController;
use App\Controllers\EditorAuditController;

return static function ($router) {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/search', [HomeController::class, 'search']);

    $router->get('/category', [CategoryController::class, 'index']);
    $router->get('/category/{path:.+}', [CategoryController::class, 'show']);

    $router->get('/submit', [SubmissionController::class, 'create']);
    $router->post('/submit', [SubmissionController::class, 'store']);

    $router->get('/editor/login', [EditorAuthController::class, 'showLogin']);
    $router->post('/editor/login', [EditorAuthController::class, 'login']);
    $router->post('/editor/logout', [EditorAuthController::class, 'logout']);

    $router->get('/editor', [EditorSubmissionController::class, 'dashboard']);
    $router->get('/editor/submissions', [EditorSubmissionController::class, 'index']);
    $router->post('/editor/submissions/bulk', [EditorSubmissionController::class, 'bulk']);
    $router->get('/editor/submissions/{id}', [EditorSubmissionController::class, 'show']);
    $router->post('/editor/submissions/{id}/approve', [EditorSubmissionController::class, 'approve']);
    $router->post('/editor/submissions/{id}/reject', [EditorSubmissionController::class, 'reject']);

    $router->get('/editor/categories', [EditorCategoryController::class, 'index']);
    $router->get('/editor/categories/create', [EditorCategoryController::class, 'create']);
    $router->post('/editor/categories', [EditorCategoryController::class, 'store']);
    $router->get('/editor/categories/{id}/edit', [EditorCategoryController::class, 'edit']);
    $router->post('/editor/categories/{id}/update', [EditorCategoryController::class, 'update']);
    $router->get('/editor/categories/{id}/move', [EditorCategoryController::class, 'move']);
    $router->post('/editor/categories/{id}/move', [EditorCategoryController::class, 'moveUpdate']);

    $router->get('/editor/sites', [EditorSiteController::class, 'index']);
    $router->get('/editor/sites/dead', [EditorSiteController::class, 'dead']);
    $router->get('/editor/sites/checks', [EditorSiteController::class, 'checks']);
    $router->get('/editor/sites/duplicates', [EditorSiteController::class, 'duplicates']);
    $router->get('/editor/sites/{id}/edit', [EditorSiteController::class, 'edit']);
    $router->post('/editor/sites/{id}/update', [EditorSiteController::class, 'update']);

    $router->get('/editor/audit', [EditorAuditController::class, 'index']);

    $router->get('/editor/imports', [EditorImportController::class, 'index']);
    $router->get('/editor/imports/create', [EditorImportController::class, 'create']);
    $router->post('/editor/imports', [EditorImportController::class, 'store']);
    $router->get('/editor/imports/{id}', [EditorImportController::class, 'show']);
};
