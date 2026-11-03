<?php
/**
 * ============================================================================
 * QueueBites – Public Front Controller
 * File: public/index.php
 * Deskripsi: Titik masuk utama aplikasi (Entry Point).
 *            Memuat konfigurasi, session, router, dan mendispatch request ke Controller.
 * ============================================================================
 */

// Aktifkan pelaporan error untuk mempermudah debugging selama pengembangan
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Muat koneksi database, helper auth, dan proteksi CSRF
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/helpers/Auth.php';
require_once dirname(__DIR__) . '/app/helpers/Csrf.php';

// Inisialisasi sesi aplikasi
Auth::start();

// Muat router dan seluruh rute yang terdaftar
$router = require_once dirname(__DIR__) . '/routes/web.php';

// Jalankan pencocokan request ke Controller
try {
    $router->dispatch();
} catch (Throwable $e) {
    http_response_code(500);

    // Jika request menginginkan format JSON (misal Fetch API)
    if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'status'  => 500,
            'error'   => $e->getMessage(),
            'file'    => basename($e->getFile()),
            'line'    => $e->getLine()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Tampilan error ramah pengguna untuk browser
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
    echo '<title>500 – Kesalahan Server</title>';
    echo '<style>';
    echo 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#FFF5F5;color:#991B1B;padding:40px 20px;}';
    echo '.card{max-width:650px;margin:40px auto;background:#FFFFFF;border:1px solid #FECACA;border-radius:16px;padding:32px;box-shadow:0 10px 25px rgba(0,0,0,0.05);}';
    echo 'h2{margin-top:0;color:#DC2626;}';
    echo 'pre{background:#FEF2F2;padding:12px;border-radius:8px;font-size:13px;overflow-x:auto;}';
    echo '.btn{display:inline-block;padding:10px 20px;background:#DC2626;color:#FFF;text-decoration:none;border-radius:8px;font-weight:600;margin-top:14px;}';
    echo '</style></head><body>';
    echo '<div class="card">';
    echo '<h2>⚠️ Terjadi Kesalahan Sistem (HTTP 500)</h2>';
    echo '<p><strong>Pesan Kesalahan:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>File: ' . htmlspecialchars(basename($e->getFile())) . ' (Line: ' . (int)$e->getLine() . ')</pre>';
    echo '<a href="javascript:history.back()" class="btn">← Kembali</a>';
    echo '</div></body></html>';
    exit;
}
