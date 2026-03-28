<?php

namespace App\Controllers;

use App\Core\Controller;

class PagesController extends Controller
{
    public function show(string $slug): void
    {
        $pages = $this->pages();

        if (!isset($pages[$slug])) {
            $this->notFound('Page not found.');
            return;
        }

        $page = $pages[$slug];

        $this->view('pages/show', [
            'pageTitle' => $page['title'],
            'page' => $page,
        ]);
    }

    protected function pages(): array
    {
        return [
            'history-of-the-internet' => [
                'title' => 'The History of the Internet',
                'lead' => 'From DARPA and packet switching to the Web, platforms, and the modern networked world.',
                'view' => 'history-of-the-internet',
            ],
            'about' => [
                'title' => 'About the Internet History Directory',
                'lead' => 'Why this directory exists, what it covers, and how it is curated.',
                'view' => 'about',
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'lead' => 'A simple statement about privacy, cookies, and submitted information.',
                'view' => 'privacy-policy',
            ],
            'terms' => [
                'title' => 'Terms of Use',
                'lead' => 'Basic terms covering site use, editorial control, and external links.',
                'view' => 'terms',
            ],
        ];
    }
}
