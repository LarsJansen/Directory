<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class EditorAuthController extends Controller
{
    public function showLogin(array $params = []): void
    {
        $this->view('editor/auth/login', [
            'pageTitle' => 'Editor Login',
        ]);
    }

    public function login(array $params = []): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $userModel = new User($this->db);
        $user = $userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Invalid username or password.');
            redirect('/editor/login');
        }

        $_SESSION['editor'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];

        flash('success', 'Welcome back, ' . $user['username'] . '.');
        redirect('/editor');
    }

    public function logout(array $params = []): void
    {
        unset($_SESSION['editor']);
        flash('success', 'You have been logged out.');
        redirect('/editor/login');
    }
}
