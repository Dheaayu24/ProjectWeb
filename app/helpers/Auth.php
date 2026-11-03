<?php
/**
 * ============================================================================
 * Mochi Mood – Auth & Session Helper
 * File: app/helpers/Auth.php
 * ============================================================================
 */

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Pastikan session path lokal tersedia agar tidak gagal di Windows / XAMPP permission
            $sessionDir = dirname(__DIR__, 2) . '/storage/sessions';
            if (!is_dir($sessionDir)) {
                @mkdir($sessionDir, 0777, true);
            }
            if (is_dir($sessionDir) && is_writable($sessionDir)) {
                @session_save_path($sessionDir);
            }

            if (!headers_sent()) {
                @session_start();
            }
        }
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        self::start();
        return !empty($_SESSION['user']);
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function role(): string
    {
        $u = self::user();
        return $u['role'] ?? 'guest';
    }

    public static function login(array $user): void
    {
        self::start();
        $_SESSION['user'] = [
            'id'    => (int)$user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
            'role'  => $user['role'],
        ];
    }

    public static function logout(): void
    {
        self::start();
        unset($_SESSION['user']);
        @session_destroy();
    }

    public static function requireRole(string ...$roles): void
    {
        self::start();
        if (!self::check()) {
            header('Location: login.php?msg=need_login');
            exit;
        }

        $userRole = self::role();
        if (!in_array($userRole, $roles, true)) {
            header('Location: index.php?msg=unauthorized');
            exit;
        }
    }
}
