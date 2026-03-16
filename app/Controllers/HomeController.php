<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Site;

class HomeController extends Controller
{
    public function index(array $params = []): void
    {
        $categoryModel = new Category($this->db);
        $siteModel = new Site($this->db);

        $this->view('home/index', [
            'pageTitle' => 'Home',
            'categories' => $categoryModel->topLevel(),
            'latestSites' => $siteModel->latestApproved(10),
        ]);
    }

    public function search(array $params = []): void
    {
        $term = trim($_GET['q'] ?? '');
        $siteModel = new Site($this->db);

        $this->view('home/search', [
            'pageTitle' => 'Search',
            'term' => $term,
            'results' => $term !== '' ? $siteModel->search($term) : [],
        ]);
    }
}
