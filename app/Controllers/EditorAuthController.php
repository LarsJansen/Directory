<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class EditorAuthController extends Controller
{
    public function showLogin(): void
    {
        if (is_editor_logged_in()) {
            $this->redirect('/editor');
        }

        $this->view('editor/auth/login', [
            'pageTitle' => 'Editor Login',
        ]);
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $userModel = new User($this->db);
        $user = $userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Invalid username or password.');
            $this->redirect('/editor/login');
        }

        $_SESSION['editor_user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];

        flash('success', 'Welcome back, ' . $user['username'] . '.');
        $this->redirect('/editor');
    }

    public function logout(): void
    {
        $this->verifyCsrf();

        unset($_SESSION['editor_user']);
        flash('success', 'You have been logged out.');
        $this->redirect('/editor/login');
    }
}
