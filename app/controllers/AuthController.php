<?php
/**
 * Controller: AuthController
 * File: app/controllers/AuthController.php
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Auth.php';

class AuthController
{
    public function login(): void
    {
        Auth::start();
        $error = null;
        $success = isset($_GET['registered']) ? 'Akun berhasil dibuat! Silakan masuk.' : null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'Email dan kata sandi wajib diisi.';
            } else {
                $userModel = new User();
                $user = $userModel->getByEmail($email);

                if ($user && $userModel->verifyPassword($password, $user['password'])) {
                    Auth::login($user);
                    if ($user['role'] === 'admin') {
                        header('Location: index.php?url=dashboard/admin');
                    } elseif ($user['role'] === 'staff') {
                        header('Location: index.php?url=dashboard/staff');
                    } else {
                        header('Location: index.php?url=restaurants');
                    }
                    exit;
                } else {
                    $error = 'Email atau kata sandi tidak cocok.';
                }
            }
        }

        include __DIR__ . '/../views/auth/login.php';
    }

    public function register(): void
    {
        Auth::start();
        $error = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $name     = trim($_POST['name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if (empty($name) || empty($email) || empty($password)) {
                $error = 'Nama, email, dan kata sandi wajib diisi.';
            } elseif ($password !== $confirm) {
                $error = 'Konfirmasi kata sandi tidak cocok.';
            } elseif (strlen($password) < 6) {
                $error = 'Kata sandi minimal 6 karakter.';
            } else {
                try {
                    $userModel = new User();
                    $newId = $userModel->create([
                        'name'     => $name,
                        'email'    => $email,
                        'password' => $password,
                        'role'     => 'customer'
                    ]);
                    $newUser = $userModel->getById($newId);
                    Auth::login($newUser);
                    header('Location: index.php?url=restaurants');
                    exit;
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        include __DIR__ . '/../views/auth/register.php';
    }

    public function quickLogin(): void
    {
        Auth::start();
        $role = $_GET['role'] ?? 'customer';
        $userModel = new User();
        $users = $userModel->getAll($role);

        if (!empty($users)) {
            Auth::login($users[0]);
            if ($role === 'admin') header('Location: index.php?url=dashboard/admin');
            elseif ($role === 'staff') header('Location: index.php?url=dashboard/staff');
            else header('Location: index.php?url=restaurants');
            exit;
        }

        header('Location: index.php?url=auth/login');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: index.php?url=home');
        exit;
    }
}

