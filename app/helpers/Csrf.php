<?php
/**
 * ============================================================================
 * QueueBites – CSRF Protection Helper
 * File: app/helpers/Csrf.php
 * Deskripsi: Menyediakan generator token CSRF dan validasi form demi keamanan aplikasi.
 * ============================================================================
 */

require_once __DIR__ . '/Auth.php';

class Csrf
{
    /**
     * Nama key session untuk token CSRF
     */
    private const SESSION_KEY = 'csrf_token';

    /**
     * Memastikan token CSRF ada dalam session dan mengembalikannya.
     *
     * @return string Token CSRF
     */
    public static function getToken(): string
    {
        Auth::start();
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Menghasilkan elemen input HTML hidden untuk disisipkan ke dalam form POST.
     *
     * @return string HTML input element
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Memvalidasi token CSRF dari request POST atau header HTTP_X_CSRF_TOKEN.
     *
     * @param string|null $token Token yang ingin diuji (opsional)
     * @return bool True jika token valid dan cocok
     */
    public static function validate(?string $token = null): bool
    {
        Auth::start();
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        if (empty($sessionToken)) {
            return false;
        }

        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if ($token === null) {
                // Cek payload JSON jika request berupa application/json
                $rawInput = file_get_contents('php://input');
                if (!empty($rawInput)) {
                    $json = json_decode($rawInput, true);
                    if (is_array($json) && !empty($json['csrf_token'])) {
                        $token = $json['csrf_token'];
                    }
                }
            }
        }

        if (empty($token) || !is_string($token)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Verifikasi token CSRF dan hentikan eksekusi dengan HTTP 403 jika tidak valid.
     *
     * @throws RuntimeException Jika CSRF tidak valid
     */
    public static function verifyOrFail(?string $token = null): void
    {
        if (!self::validate($token)) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => 'Token CSRF tidak valid atau kedaluwarsa. Silakan refresh halaman.'
                ]);
            } else {
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 Forbidden</title></head>';
                echo '<body style="font-family:sans-serif;padding:40px;text-align:center;">';
                echo '<h1 style="color:#b91c1c;">403 - Akses Ditolak (CSRF Error)</h1>';
                echo '<p>Sesi form Anda telah kedaluwarsa atau token CSRF tidak valid.</p>';
                echo '<a href="javascript:history.back()" style="display:inline-block;margin-top:16px;padding:8px 16px;background:#1e40af;color:#fff;text-decoration:none;border-radius:6px;">Kembali</a>';
                echo '</body></html>';
            }
            exit;
        }
    }
}
