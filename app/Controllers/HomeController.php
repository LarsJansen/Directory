<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Site;

class HomeController extends Controller
{
    public function index(): void
    {
        $categoryModel = new Category($this->db);
        $siteModel = new Site($this->db);

        $this->view('home/index', [
            'categories' => $categoryModel->homeDirectoryIndex(5),
            'latestSites' => $siteModel->latest(),
            'pageTitle' => 'Home',
        ]);
    }

    public function search(): void
    {
        $siteModel = new Site($this->db);
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) config('per_page', 20);

        $results = [];
        $pagination = null;

        if ($q !== '' && mb_strlen($q) >= 2) {
            $total = $siteModel->countSearch($q);
            $pagination = build_pagination($total, $page, $perPage);
            $results = $siteModel->search($q, $pagination['per_page'], $pagination['offset']);
        }

        $this->view('home/search', [
            'pageTitle' => 'Search',
            'query' => $q,
            'results' => $results,
            'pagination' => $pagination,
        ]);
    }

    public function notFound(string $message = 'Page not found'): void
    {
        parent::notFound($message);
    }
}
