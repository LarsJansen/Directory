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

        $cacheKey = 'browse-index-v2-' . directory_cache_token();
        $categories = cache_remember($cacheKey, 86400, static function () use ($categoryModel): array {
            return $categoryModel->browseIndexData();
        });

        $this->view('category/index', [
            'pageTitle' => 'Browse Categories',
            'categories' => $categories,
            'metaDescription' => 'Browse the Internet History Directory by category, from early networks and BBS culture to preserved text archives, protocols, and classic web resources.',
        ]);
    }

    public function show(string $path): void
    {
        $categoryModel = new Category($this->db);
        $siteModel = new Site($this->db);

        $category = $categoryModel->findByPath($path);
        if ($category) {
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $sort = (string) ($_GET['sort'] ?? 'title');
            if (!in_array($sort, ['title', 'newest'], true)) {
                $sort = 'title';
            }

            $perPage = (int) config('per_page', 20);
            $total = $siteModel->countByCategory((int) $category['id']);
            $pagination = build_pagination($total, $page, $perPage);
            $children = $categoryModel->childrenOf((int) $category['id']);

            $this->view('category/show', [
                'pageTitle' => $category['name'],
                'category' => $category,
                'breadcrumbs' => $categoryModel->breadcrumbByPath($category['path']),
                'children' => $children,
                'sites' => $siteModel->forCategory((int) $category['id'], $pagination['per_page'], $pagination['offset'], $sort),
                'pagination' => $pagination,
                'sort' => $sort,
                'metaDescription' => build_category_meta_description($category, count($children), (int) $total),
            ]);
            return;
        }

        $lastSlash = strrpos($path, '/');
        if ($lastSlash === false) {
            $this->notFound('Category not found.');
            return;
        }

        $categoryPath = substr($path, 0, $lastSlash);
        $slug = substr($path, $lastSlash + 1);
        $category = $categoryModel->findByPath($categoryPath);

        if (!$category || $slug === '') {
            $this->notFound('Resource not found.');
            return;
        }

        $site = $siteModel->findByCategoryAndSlug((int) $category['id'], $slug);
        if (!$site) {
            $this->notFound('Resource not found.');
            return;
        }

        $this->view('category/site', [
            'pageTitle' => $site['title'],
            'category' => $category,
            'site' => $site,
            'breadcrumbs' => $categoryModel->breadcrumbByPath($category['path']),
            'metaDescription' => build_site_meta_description($site, $category),
        ]);
    }
}
