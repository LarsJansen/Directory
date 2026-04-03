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

        $cacheKey = 'home-index-v3-' . directory_cache_token();

        $homeData = cache_remember($cacheKey, 86400, static function () use ($categoryModel, $siteModel): array {
            return [
                'categories' => $categoryModel->homeDirectoryIndex(5),
                'featuredSites' => $siteModel->featured(),
                'latestSites' => $siteModel->latest(),
            ];
        });

        $this->view('home/index', [
            'categories' => $homeData['categories'] ?? [],
            'featuredSites' => $homeData['featuredSites'] ?? [],
            'latestSites' => $homeData['latestSites'] ?? [],
            'pageTitle' => 'Home',
            'metaDescription' => 'Browse a human-curated directory of Internet history, early web culture, BBS archives, old sites, protocols, and preserved digital artefacts.',
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
            'metaDescription' => $q !== ''
                ? meta_description('Search results for ' . $q . ' in the Internet History Directory.', null, 160)
                : 'Search the Internet History Directory for historic websites, text archives, protocols, and early online culture.',
        ]);
    }

    public function notFound(string $message = 'Page not found'): void
    {
        parent::notFound($message);
    }
}
