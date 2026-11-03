<?php
/**
 * ============================================================================
 * QueueBites – Native PHP Router Engine
 * File: app/helpers/Router.php
 * Deskripsi: Router lightweight berbasis MVC tanpa framework.
 *            Mendukung pendaftaran route GET dan POST, validasi method HTTP,
 *            ekstraksi parameter query, dispatch ke Controller & Action,
 *            serta kompatibel penuh dengan PHP CLI Server maupun Apache / XAMPP.
 * ============================================================================
 */

class Router
{
    /**
     * Menyimpan seluruh daftar route yang didaftarkan
     * Format: ['GET' => ['/' => handler, ...], 'POST' => [...]]
     */
    private array $routes = [
        'GET'  => [],
        'POST' => []
    ];

    /**
     * Mendaftarkan route dengan method GET
     *
     * @param string $uri Path endpoint (contoh: '/' atau '/restaurants')
     * @param callable|array|string $action Controller & method
     * @return self
     */
    public function get(string $uri, $action): self
    {
        $normalized = $this->normalizeUri($uri);
        $this->routes['GET'][$normalized] = $action;
        return $this;
    }

    /**
     * Mendaftarkan route dengan method POST
     *
     * @param string $uri Path endpoint (contoh: '/queue/store')
     * @param callable|array|string $action Controller & method
     * @return self
     */
    public function post(string $uri, $action): self
    {
        $normalized = $this->normalizeUri($uri);
        $this->routes['POST'][$normalized] = $action;
        return $this;
    }

    /**
     * Menjalankan proses routing (mencocokkan request dengan rute yang sesuai)
     *
     * @param string|null $requestUri URI opsional (otomatis membaca server jika null)
     * @param string|null $requestMethod Method HTTP opsional
     */
    public function dispatch(?string $requestUri = null, ?string $requestMethod = null): void
    {
        // 1. Tentukan Method HTTP
        $method = strtoupper($requestMethod ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
        $_SERVER['REQUEST_METHOD'] = $method;
        
        // Dukungan method override (misal: POST dengan _method=PUT / _method=DELETE)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper(trim($_POST['_method']));
            if (in_array($override, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
                $method = $override;
            }
        }

        // 2. Tentukan URI yang diminta
        $uri = $requestUri ?? $this->resolveCurrentUri();
        $normalizedUri = $this->normalizeUri($uri);

        // 3. Cek apakah route cocok dengan method saat ini
        if (isset($this->routes[$method][$normalizedUri])) {
            $this->executeAction($this->routes[$method][$normalizedUri]);
            return;
        }

        // 4. Periksa apakah path terdaftar di method LAIN (Contoh: Mengakses POST via GET)
        $otherMethods = [];
        foreach ($this->routes as $m => $registered) {
            if ($m !== $method && isset($registered[$normalizedUri])) {
                $otherMethods[] = $m;
            }
        }

        if (!empty($otherMethods)) {
            $this->abort(405, "Metode HTTP {$method} tidak diizinkan untuk rute '{$normalizedUri}'. Rute ini hanya menerima: " . implode(', ', $otherMethods));
            return;
        }

        // 5. Jika tidak ditemukan sama sekali, beri response 404
        $this->abort(404, "Halaman atau endpoint '{$normalizedUri}' tidak ditemukan.");
    }

    /**
     * Menjalankan Controller & Action yang terdaftar
     *
     * @param callable|array|string $action Handler route
     */
    private function executeAction($action): void
    {
        // A. Jika berupa callable Closure
        if (is_callable($action)) {
            call_user_func($action);
            return;
        }

        // B. Jika berupa format array: [HomeController::class, 'index'] atau [new HomeController(), 'index']
        if (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;

            if (is_string($controller)) {
                if (!class_exists($controller)) {
                    throw new RuntimeException("Kelas Controller '{$controller}' tidak ditemukan.");
                }
                $instance = new $controller();
            } else {
                $instance = $controller;
            }

            if (!method_exists($instance, $method)) {
                $className = is_object($instance) ? get_class($instance) : (string)$instance;
                throw new RuntimeException("Method '{$method}' tidak ditemukan pada Controller '{$className}'.");
            }

            $instance->$method();
            return;
        }

        // C. Jika berupa format string "HomeController@index"
        if (is_string($action) && strpos($action, '@') !== false) {
            [$controllerName, $method] = explode('@', $action, 2);

            if (!class_exists($controllerName)) {
                throw new RuntimeException("Kelas Controller '{$controllerName}' tidak ditemukan.");
            }

            $instance = new $controllerName();

            if (!method_exists($instance, $method)) {
                throw new RuntimeException("Method '{$method}' tidak ditemukan pada '{$controllerName}'.");
            }

            $instance->$method();
            return;
        }

        throw new InvalidArgumentException("Format action route tidak valid.");
    }

    /**
     * Mengekstrak URI aktif dari query parameter ?url= atau REQUEST_URI
     *
     * @return string Path URI yang bersih
     */
    private function resolveCurrentUri(): string
    {
        // Prioritas 1: Parameter query eksplisit ?url= atau ?r=
        if (!empty($_GET['url'])) {
            $url = $_GET['url'];
            // Buang query string internal jika ada di dalam parameter url (misal url=restaurant?id=1)
            $parsed = parse_url($url, PHP_URL_PATH);
            return $parsed ?: $url;
        }

        // Prioritas 2: Analisis REQUEST_URI (Clean URL / Apache Rewrite / Built-in server)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        // Hilangkan subdirektori tempat script PHP berada (contoh jika di XAMPP /ProjectWeb/public/...)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace('\\', '/', dirname($scriptName));

        if ($scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        }

        // Hilangkan akhiran index.php jika ada
        $path = preg_replace('/^(\/index\.php)/', '', $path);

        return $path ?: '/';
    }

    /**
     * Menormalisasi URI: menghilangkan trailing slash ganda dan spasi
     *
     * @param string $uri URI mentah
     * @return string URI standar berawalan '/'
     */
    private function normalizeUri(string $uri): string
    {
        $uri = trim($uri);
        $uri = parse_url($uri, PHP_URL_PATH) ?? $uri;
        $uri = '/' . trim($uri, '/');
        return $uri;
    }

    /**
     * Mengembalikan seluruh rute yang terdaftar
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Menghentikan aplikasi dengan kode status HTTP dan tampilan error elegan
     *
     * @param int $code Kode status HTTP (404, 405, 500)
     * @param string $message Pesan deskripsi kesalahan
     */
    private function abort(int $code, string $message): void
    {
        if (!headers_sent()) {
            http_response_code($code);
        }

        // Jika request menginginkan JSON (Fetch API)
        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'status'  => $code,
                'error'   => $message
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (!defined('TESTING_MODE')) {
                exit;
            }
            return;
        }

        echo '<!DOCTYPE html>';
        echo '<html lang="id"><head><meta charset="utf-8">';
        echo '<title>' . $code . ' – ' . htmlspecialchars($message) . '</title>';
        echo '<style>';
        echo 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#F8F9F5;color:#202B1E;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px;}';
        echo '.error-card{background:#FFFFFF;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,0.06);padding:40px;max-width:540px;text-align:center;border:1px solid #E2E8D8;}';
        echo '.error-code{font-size:64px;font-weight:900;color:#2D5A27;line-height:1;margin:0 0 10px;}';
        echo '.error-title{font-size:20px;font-weight:700;margin:0 0 12px;color:#1B3D17;}';
        echo '.error-desc{font-size:14px;color:#4A5847;line-height:1.6;margin:0 0 24px;}';
        echo '.btn{display:inline-block;padding:12px 24px;background:#2D5A27;color:#FFFFFF;text-decoration:none;border-radius:12px;font-weight:600;font-size:14px;transition:0.2s;}';
        echo '.btn:hover{background:#1B3D17;}';
        echo '</style></head><body>';
        echo '<div class="error-card">';
        echo '<div class="error-code">' . $code . '</div>';
        echo '<h1 class="error-title">' . ($code === 404 ? 'Halaman Tidak Ditemukan' : ($code === 405 ? 'Metode HTTP Tidak Diizinkan' : 'Terjadi Kesalahan')) . '</h1>';
        echo '<p class="error-desc">' . htmlspecialchars($message) . '</p>';
        echo '<a href="index.php?url=/" class="btn">← Kembali ke Beranda</a>';
        echo '</div></body></html>';
        if (!defined('TESTING_MODE')) {
            exit;
        }
    }
}
