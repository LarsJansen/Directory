<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller shared by both public and editor routes.
 */
class Controller
{
    public function __construct(
        protected Database $db,
        protected array $appConfig
    ) {
    }

    /**
     * Render a view inside the shared site layout.
     */
    protected function view(string $view, array $data = []): void
    {
        $data['headerSearchQuery'] = $data['headerSearchQuery'] ?? trim((string) ($_GET['q'] ?? ''));
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
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

    protected function verifyCsrf(): void
    {
        verify_csrf();
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
