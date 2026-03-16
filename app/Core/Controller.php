<?php

namespace App\Core;

class Controller
{
    public function __construct(
        protected Database $db,
        protected array $appConfig
    ) {
    }

    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';
        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    protected function requireEditor(): void
    {
        if (!is_editor_logged_in()) {
            flash('error', 'Please log in to access the editor area.');
            redirect('/editor/login');
        }
    }
}
