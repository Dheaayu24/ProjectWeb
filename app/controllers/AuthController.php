<?php
/**
 * ============================================================================
 * QueueBites – Auth Controller
 * File: app/controllers/AuthController.php
 * Deskripsi: Mengelola proses registrasi, login, logout, verifikasi kata sandi,
 *            dan manajemen sesi dengan standar keamanan (CSRF, password_hash,
 *            password_verify, dan session fixation prevention).
 * ============================================================================
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Csrf.php';

class AuthController extends BaseController
{
    protected User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Menampilkan form login (GET) dan memproses autentikasi pengguna (POST).
     */
    public function login(): void
    {
        Auth::start();

        // Jika sudah login, langsung alihkan sesuai role
        if (Auth::check()) {
            $this->redirectBasedOnRole(Auth::role());
            return;
        }

        $error = null;
        $success = $_GET['registered'] ?? null;
        if ($success) {
            $success = 'Akun berhasil dibuat! Silakan masuk dengan email dan kata sandi Anda.';
        }

        // Tangkap pesan flash jika ada
        if (!empty($_SESSION['flash'])) {
            if ($_SESSION['flash']['type'] === 'danger') {
                $error = $_SESSION['flash']['message'];
            } else {
                $success = $_SESSION['flash']['message'];
            }
            unset($_SESSION['flash']);
        }

        // Proses jika request berjenis POST
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            // 1. Validasi CSRF Token
            if (!Csrf::validate()) {
                $error = 'Sesi form Anda telah kedaluwarsa. Silakan muat ulang halaman.';
            } else {
                // 2. Ambil dan validasi input
                $email    = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($email)) {
                    $error = 'Alamat email wajib diisi.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Format alamat email tidak valid (contoh: nama@email.com).';
                } elseif (empty($password)) {
                    $error = 'Kata sandi wajib diisi.';
                } else {
                    // 3. Cari pengguna berdasarkan email
                    $user = $this->userModel->getByEmail($email);

                    // 4. Verifikasi password dengan password_verify
                    if ($user && $this->userModel->verifyPassword($password, $user['password'])) {
                        // Amankan session dari serangan Session Fixation
                        session_regenerate_id(true);

                        // Simpan data login ke Session
                        Auth::login($user);

                        // Alihkan pengguna berdasarkan role
                        $this->redirectBasedOnRole($user['role']);
                        return;
                    } else {
                        $error = 'Email atau kata sandi yang Anda masukkan salah.';
                    }
                }
            }
        }

        // Render View Login
        $this->view('auth/login', [
            'error'   => $error,
            'success' => $success
        ]);
    }

    /**
     * Menampilkan form registrasi (GET) dan memproses pendaftaran akun baru (POST).
     */
    public function register(): void
    {
        Auth::start();

        if (Auth::check()) {
            $this->redirectBasedOnRole(Auth::role());
            return;
        }

        $error = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            // 1. Validasi CSRF Token
            if (!Csrf::validate()) {
                $error = 'Sesi form telah kedaluwarsa. Silakan muat ulang halaman.';
            } else {
                // 2. Ambil input
                $name     = trim($_POST['name'] ?? '');
                $email    = trim($_POST['email'] ?? '');
                $phone    = trim($_POST['phone'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm  = $_POST['confirm_password'] ?? '';

                // 3. Validasi ketat input pengguna
                if (empty($name) || strlen($name) < 2) {
                    $error = 'Nama lengkap wajib diisi minimal 2 karakter.';
                } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Format alamat email tidak valid.';
                } elseif ($this->userModel->getByEmail($email) !== null) {
                    $error = "Alamat email '{$email}' sudah terdaftar. Silakan gunakan email lain atau masuk.";
                } elseif (strlen($password) < 6) {
                    $error = 'Kata sandi wajib diisi minimal 6 karakter.';
                } elseif ($password !== $confirm) {
                    $error = 'Konfirmasi kata sandi tidak cocok dengan kata sandi.';
                } else {
                    // 4. Simpan ke Database melalui Model (password di-hash di dalam Model dengan password_hash)
                    try {
                        $newUserId = $this->userModel->create([
                            'name'     => $name,
                            'email'    => $email,
                            'phone'    => !empty($phone) ? $phone : null,
                            'password' => $password, // Model User melakukan password_hash
                            'role'     => 'customer'
                        ]);

                        $newUser = $this->userModel->getById($newUserId);
                        session_regenerate_id(true);
                        Auth::login($newUser);

                        $this->redirect('index.php?url=restaurants', 'Pendaftaran berhasil! Selamat datang di QueueBites.', 'success');
                        return;
                    } catch (Throwable $e) {
                        $error = 'Gagal mendaftar: ' . $e->getMessage();
                    }
                }
            }
        }

        $this->view('auth/register', [
            'error' => $error
        ]);
    }

    /**
     * Memproses logout pengguna dan membersihkan seluruh sesi.
     */
    public function logout(): void
    {
        Auth::logout();
        $this->redirect('index.php?url=auth/login', 'Anda telah berhasil keluar.', 'info');
    }

    /**
     * Shortcut untuk login cepat akun demonstrasi (Customer, Staff, Admin).
     */
    public function quickLogin(): void
    {
        Auth::start();
        $role = $_GET['role'] ?? 'customer';
        $allowed = ['customer', 'staff', 'admin'];

        if (!in_array($role, $allowed, true)) {
            $role = 'customer';
        }

        $users = $this->userModel->getAll($role);

        if (!empty($users)) {
            session_regenerate_id(true);
            Auth::login($users[0]);
            $this->redirectBasedOnRole($role);
            return;
        }

        $this->redirect('index.php?url=auth/login', 'Akun contoh untuk role tersebut tidak ditemukan.', 'danger');
    }

    /**
     * Helper pengalihan rute berdasarkan role pengguna
     */
    private function redirectBasedOnRole(string $role): void
    {
        if ($role === 'admin') {
            $this->redirect('index.php?url=dashboard/admin');
        } elseif ($role === 'staff') {
            $this->redirect('index.php?url=dashboard/staff');
        } else {
            $this->redirect('index.php?url=restaurants');
        }
    }
}
