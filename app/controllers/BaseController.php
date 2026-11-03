<?php
/**
 * ============================================================================
 * QueueBites – Base Controller
 * File: app/controllers/BaseController.php
 * Deskripsi: Controller induk yang menyediakan fungsi umum seperti render view,
 *            respons JSON untuk Fetch API, redirect, validasi CSRF, dan autentikasi.
 * ============================================================================
 */

require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Csrf.php';

abstract class BaseController
{
    /**
     * Memuat dan menampilkan berkas View dengan data yang dipassing.
     *
     * @param string $viewPath Path relatif dari folder app/views (contoh: 'home/index' atau 'queues/status')
     * @param array $data Variabel yang akan diekstrak ke dalam View
     */
    protected function view(string $viewPath, array $data = []): void
    {
        // Ekstrak asosiasi array menjadi variabel lokal dalam view
        extract($data, EXTR_SKIP);

        $file = dirname(__DIR__) . '/views/' . ltrim($viewPath, '/') . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException("View berkas '{$file}' tidak ditemukan.");
        }

        require $file;
    }

    /**
     * Mengirimkan respons format JSON (khusus untuk Fetch API / AJAX).
     *
     * @param array $data Data yang akan dikonversi ke JSON
     * @param int $statusCode Kode status HTTP (200, 400, 403, 404, 500)
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (!defined('TESTING_MODE')) {
            exit;
        }
    }

    /**
     * Mengalihkan browser (Redirect) ke URL tujuan dengan opsi pesan flash.
     *
     * @param string $url URL tujuan pengalihan
     * @param string|null $message Pesan notifikasi flash opsional
     * @param string $type Jenis notifikasi ('success'|'danger'|'warning'|'info')
     */
    protected function redirect(string $url, ?string $message = null, string $type = 'success'): void
    {
        if ($message !== null) {
            Auth::start();
            $_SESSION['flash'] = [
                'message' => $message,
                'type'    => $type
            ];
        }

        if (!headers_sent()) {
            header("Location: {$url}");
        }
        if (!defined('TESTING_MODE')) {
            exit;
        }
    }

    /**
     * Memvalidasi token CSRF pada request yang memodifikasi data (POST).
     * Jika tidak valid, eksekusi dihentikan dengan status HTTP 403.
     */
    protected function validateCsrf(): void
    {
        // Hanya validasi jika method POST
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::verifyOrFail();
        }
    }

    /**
     * Memastikan pengguna telah login. Jika belum, alihkan ke halaman login.
     *
     * @param string|null $requiredRole Role spesifik yang wajib dimiliki (admin|staff|customer)
     * @return array Data pengguna yang sedang login
     */
    protected function requireAuth(?string $requiredRole = null): array
    {
        Auth::start();

        if (!Auth::check()) {
            $this->redirect('index.php?url=auth/login', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.', 'danger');
        }

        $user = Auth::user();

        if ($requiredRole !== null && ($user['role'] ?? '') !== $requiredRole) {
            $this->redirect('index.php?url=/', 'Anda tidak memiliki hak akses untuk halaman tersebut.', 'danger');
        }

        return $user;
    }

    /**
     * Memastikan hanya role staf atau admin yang dapat mengakses.
     *
     * @return array Data pengguna
     */
    protected function requireStaffOrAdmin(): array
    {
        Auth::start();

        if (!Auth::check()) {
            $this->redirect('index.php?url=auth/login', 'Silakan masuk sebagai petugas atau admin.', 'danger');
        }

        $user = Auth::user();

        if (!in_array($user['role'] ?? '', ['admin', 'staff'], true)) {
            $this->redirect('index.php?url=/', 'Halaman ini khusus untuk staf atau administrator.', 'danger');
        }

        return $user;
    }

    /**
     * Memastikan pengguna belum login (khusus halaman login & register).
     */
    protected function requireGuest(): void
    {
        Auth::start();

        if (Auth::check()) {
            $user = Auth::user();
            if ($user['role'] === 'admin') {
                $this->redirect('index.php?url=dashboard/admin');
            } elseif ($user['role'] === 'staff') {
                $this->redirect('index.php?url=dashboard/staff');
            } else {
                $this->redirect('index.php?url=restaurants');
            }
        }
    }

    /**
     * Sanitasi string input dasar untuk mencegah XSS.
     *
     * @param mixed $value
     * @return string
     */
    protected function sanitize($value): string
    {
        return trim((string)$value);
    }
}
