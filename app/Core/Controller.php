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
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view);
            return;
        }

        require dirname(__DIR__) . '/Views/layouts/header.php';
        require $viewFile;
        require dirname(__DIR__) . '/Views/layouts/footer.php';
    }

    protected function redirect(string $path): void
    {
        redirect_to($path);
    }

    protected function requireEditor(): void
    {
        if (!is_editor_logged_in()) {
            flash('error', 'Please log in to continue.');
            $this->redirect('/editor/login');
        }
    }

    protected function notFound(string $message = 'Page not found'): void
    {
        http_response_code(404);
        $this->view('home/not_found', ['message' => $message]);
    }
}
