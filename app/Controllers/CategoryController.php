<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Site;

class CategoryController extends Controller
{
    public function index(array $params = []): void
    {
        redirect('/');
    }

    public function show(array $params = []): void
    {
        $path = trim((string) ($params['path'] ?? ''), '/');
        $categoryModel = new Category($this->db);
        $siteModel = new Site($this->db);

        $category = $categoryModel->findByPath($path);

        if (!$category) {
            http_response_code(404);
            echo 'Category not found';
            return;
        }

        $children = $categoryModel->children((int) $category['id']);
        $sites = $siteModel->forCategory((int) $category['id']);
        $breadcrumbs = $categoryModel->breadcrumbs((int) $category['id']);

        $this->view('category/show', [
            'category' => $category,
            'children' => $children,
            'sites' => $sites,
            'breadcrumbs' => $breadcrumbs,
            'pageTitle' => $category['name'],
        ]);
    }
}
