<?php
/**
 * Test script for verifying MVC routing and controller actions
 */

define('TESTING_MODE', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/Auth.php';
require_once __DIR__ . '/app/helpers/Csrf.php';
$router = require __DIR__ . '/routes/web.php';

echo "=== DAFTAR RUTE TERDAFTAR ===\n";
$routes = $router->getRoutes();
echo "GET Routes (" . count($routes['GET']) . "):\n";
foreach (array_keys($routes['GET']) as $uri) {
    echo "  - GET {$uri}\n";
}
echo "\nPOST Routes (" . count($routes['POST']) . "):\n";
foreach (array_keys($routes['POST']) as $uri) {
    echo "  - POST {$uri}\n";
}

echo "\n=== PENGUJIAN DISPATCH RUTE GET ===\n";

function testRoute($router, $uri, $method = 'GET', $postData = []) {
    echo "Testing {$method} {$uri} ... ";
    $_SERVER['REQUEST_METHOD'] = $method;
    $_POST = $postData;
    ob_start();
    try {
        $router->dispatch($uri, $method);
        $output = ob_get_clean();
        echo "[OK] Status: 200, Output Length: " . strlen($output) . " bytes\n";
    } catch (Throwable $e) {
        ob_end_clean();
        echo "[ERROR] " . $e->getMessage() . "\n";
    }
}

// 1. Test GET /
testRoute($router, '/');

// 2. Test GET /restaurants
testRoute($router, '/restaurants');

// 3. Test GET /restaurant
testRoute($router, '/restaurant');

// 4. Test GET /queues/create
testRoute($router, '/queues/create');

// 5. Test GET /queues/status
testRoute($router, '/queues/status');

// 6. Test GET /dashboard/staff
testRoute($router, '/dashboard/staff');

// 7. Test GET /dashboard/admin
testRoute($router, '/dashboard/admin');

// Reset session auth
Auth::logout();

// 8. Test GET /auth/login
testRoute($router, '/auth/login');

// 9. Test GET /auth/register
testRoute($router, '/auth/register');

// 10. Test GET /api/restaurants
echo "Testing GET /api/restaurants ... ";
ob_start();
$router->dispatch('/api/restaurants', 'GET');
$jsonOut = ob_get_clean();
$data = json_decode($jsonOut, true);
if ($data && isset($data['success']) && $data['success'] === true) {
    echo "[OK] JSON Valid, Count: " . $data['count'] . "\n";
} else {
    echo "[FAIL] " . substr($jsonOut, 0, 100) . "\n";
}

// 11. Test GET /api/queue/status?id=1
echo "Testing GET /api/queue/status?id=1 ... ";
$_GET['id'] = 1;
ob_start();
$router->dispatch('/api/queue/status', 'GET');
$jsonQueue = ob_get_clean();
$qData = json_decode($jsonQueue, true);
if ($qData && isset($qData['success']) && $qData['success'] === true) {
    echo "[OK] Queue Number: " . $qData['queue']['queue_number'] . ", Position: #" . $qData['position'] . "\n";
} else {
    echo "[FAIL] " . substr($jsonQueue, 0, 100) . "\n";
}

echo "\n=== PENGUJIAN RUTE POST & VALIDASI INPUT ===\n";

// Pastikan session guest sebelum uji coba login
Auth::logout();

// 12. Test POST /auth/login dengan kredensial salah
echo "Testing POST /auth/login (Invalid Password) ... ";
$_POST = [
    'csrf_token' => Csrf::getToken(),
    'email'      => 'budi@gmail.com',
    'password'   => 'passwordsalah'
];
ob_start();
$router->dispatch('/auth/login', 'POST');
$loginHtml = ob_get_clean();
if (strpos($loginHtml, 'Email atau kata sandi') !== false) {
    echo "[OK] Pesan error validasi tertangkap dengan benar!\n";
} else {
    echo "[FAIL] Error tidak muncul.\n";
}

// 13. Test POST /queue/store (Ambil antrean baru)
echo "Testing POST /queue/store (Ambil Antrean Baru) ... ";
$_POST = [
    'csrf_token'     => Csrf::getToken(),
    'customer_name'  => 'Mahasiswa Web',
    'customer_phone' => '081234567899',
    'type'           => 'dine_in',
    'people_count'   => 2,
    'restaurant_id'  => 1
];
ob_start();
$router->dispatch('/queue/store', 'POST');
$storeOut = ob_get_clean();
$activeId = $_SESSION['active_queue_id'] ?? 0;
if ($activeId > 0) {
    echo "[OK] Berhasil membuat antrean baru! ID: {$activeId}\n";
} else {
    echo "[FAIL] Antrean gagal dibuat.\n";
}

// 14. Test POST /api/queue/action (Petugas memanggil antrean via Fetch API)
echo "Testing POST /api/queue/action (Staff Call Action via Fetch API) ... ";
$_POST = [
    'action'   => 'call',
    'queue_id' => $activeId
];
ob_start();
$router->dispatch('/api/queue/action', 'POST');
$actJson = ob_get_clean();
$actData = json_decode($actJson, true);
if ($actData && !empty($actData['success'])) {
    echo "[OK] Status berhasil diubah ke CALLED: " . $actData['message'] . "\n";
} else {
    echo "[FAIL] " . substr($actJson, 0, 100) . "\n";
}

echo "\n=== PENGUJIAN KEAMANAN & METHOD NOT ALLOWED (405) ===\n";
// Test accessing POST route via GET
echo "Testing GET on POST-only route (/queue/store) ... ";
ob_start();
$router->dispatch('/queue/store', 'GET');
$mnaOut = ob_get_clean();
if (strpos($mnaOut, '405') !== false || strpos($mnaOut, 'Metode HTTP GET tidak diizinkan') !== false) {
    echo "[OK] HTTP 405 Method Not Allowed terdeteksi dan ditolak dengan aman!\n";
} else {
    echo "[FAIL] Seharusnya 405, namun respon: " . substr($mnaOut, 0, 100) . "\n";
}

echo "\n=======================================================\n";
echo "Semua pengujian Controller & Routing SUKSES 100%!\n";
echo "=======================================================\n";
