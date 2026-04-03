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
            'metaDescription' => meta_description($page['meta_description'] ?? ($page['lead'] ?? ''), null, 160),
        ]);
    }

    protected function pages(): array
    {
        return [
            'history-of-the-internet' => [
                'title' => 'The History of the Internet',
                'lead' => 'From DARPA and packet switching to the Web, platforms, and the modern networked world.',
                'meta_description' => 'A long-form guide to the history of the Internet, from ARPANET and packet switching to the Web, browsers, platforms, and modern online life.',
                'view' => 'history-of-the-internet',
            ],
            'bulletin-board-systems' => [
                'title' => 'Bulletin Board Systems (BBS)',
                'lead' => 'Dial-up communities, sysops, message boards, shareware, ANSI art, and the culture that shaped the pre-web Internet.',
                'meta_description' => 'An in-depth look at bulletin board systems, covering dial-up culture, sysops, message boards, shareware, ANSI art, and life online before the web.',
                'view' => 'bulletin-board-systems',
            ],
            'ftp-archives-and-early-file-sharing' => [
                'title' => 'The Rise of FTP Archives and Early File Sharing',
                'lead' => 'Before cloud storage and app stores, software, documents, and updates often lived in public FTP directories, mirrors, and rough but reliable archives.',
                'meta_description' => 'Explore the history of FTP archives and early file sharing, from anonymous FTP servers and mirrors to the software archives that shaped early Internet culture.',
                'view' => 'ftp-archives-and-early-file-sharing',
            ],
            'web-directories-vs-search-engines' => [
                'title' => 'Web Directories vs Search Engines',
                'lead' => 'How the web moved from curated browsing to algorithmic search, what was gained, what was lost, and why directories still matter.',
                'meta_description' => 'A comparison of web directories and search engines, looking at how curated browsing gave way to algorithmic search and what was lost in the process.',
                'view' => 'web-directories-vs-search-engines',
            ],
            'about' => [
                'title' => 'About the Internet History Directory',
                'lead' => 'Why this directory exists, what it covers, and how it is curated.',
                'meta_description' => 'Learn what the Internet History Directory covers, why it exists, and how its curated listings focus on long-term historical value rather than web-scale coverage.',
                'view' => 'about',
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'lead' => 'A simple statement about privacy, cookies, and submitted information.',
                'meta_description' => 'Read the Internet History Directory privacy policy covering basic site use, cookies, and how submitted information is handled.',
                'view' => 'privacy-policy',
            ],
            'terms' => [
                'title' => 'Terms of Use',
                'lead' => 'Basic terms covering site use, editorial control, and external links.',
                'meta_description' => 'Read the Internet History Directory terms of use, including editorial control, external links, and general conditions for using the site.',
                'view' => 'terms',
            ],
        ];
    }
}
