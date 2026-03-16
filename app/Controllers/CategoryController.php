<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Site;

class CategoryController extends Controller
{
    public function index(): void
    {
        $categoryModel = new Category($this->db);

        $this->view('category/index', [
            'pageTitle' => 'Browse Categories',
            'categories' => $categoryModel->topLevel(),
        ]);
    }

    public function show(string $path): void
    {
        $categoryModel = new Category($this->db);
        $siteModel = new Site($this->db);

        $category = $categoryModel->findByPath($path);
        if (!$category) {
            $this->notFound('Category not found.');
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $sort = (string) ($_GET['sort'] ?? 'title');
        if (!in_array($sort, ['title', 'newest'], true)) {
            $sort = 'title';
        }

        $perPage = (int) config('per_page', 20);
        $total = $siteModel->countByCategory((int) $category['id']);
        $pagination = build_pagination($total, $page, $perPage);

        $this->view('category/show', [
            'pageTitle' => $category['name'],
            'category' => $category,
            'breadcrumbs' => $categoryModel->breadcrumbByPath($category['path']),
            'children' => $categoryModel->childrenOf((int) $category['id']),
            'sites' => $siteModel->forCategory((int) $category['id'], $pagination['per_page'], $pagination['offset'], $sort),
            'pagination' => $pagination,
            'sort' => $sort,
        ]);
    }
}
