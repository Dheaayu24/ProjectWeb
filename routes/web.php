<?php
/**
 * ============================================================================
 * QueueBites – Definisi Route Aplikasi (Web Routes)
 * File: routes/web.php
 * Deskripsi: Mendefinisikan seluruh mapping URL ke Controller dan Action yang sesuai.
 *            Memisahkan method HTTP GET (baca data / tampilan) dan POST (mutasi data).
 * ============================================================================
 */

require_once dirname(__DIR__) . '/app/helpers/Router.php';

// Muat seluruh Controller
require_once dirname(__DIR__) . '/app/controllers/HomeController.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/controllers/RestaurantController.php';
require_once dirname(__DIR__) . '/app/controllers/QueueController.php';
require_once dirname(__DIR__) . '/app/controllers/DashboardController.php';

// Inisialisasi Router jika belum ada
if (!isset($router) || !($router instanceof Router)) {
    $router = new Router();
}

// ============================================================================
// A. GET ROUTES (Menampilkan Halaman & Membaca Data)
// ============================================================================

// 1. Halaman Utama (Landing Page)
$router->get('/', [HomeController::class, 'index']);
$router->get('/home', [HomeController::class, 'index']);

// 2. Restoran (Daftar & Detail)
$router->get('/restaurants', [RestaurantController::class, 'index']);
$router->get('/restaurant', [RestaurantController::class, 'detail']);
$router->get('/restaurants/detail', [RestaurantController::class, 'detail']);
$router->get('/menu', [RestaurantController::class, 'detail']);
$router->get('/api/restaurants', [RestaurantController::class, 'apiList']);

// 3. Antrean (Tiket, Status & Posisi Antrean)
$router->get('/queues', [QueueController::class, 'index']);
$router->get('/queues/create', [QueueController::class, 'create']);
$router->get('/queue/create', [QueueController::class, 'create']);
$router->get('/queues/status', [QueueController::class, 'status']);
$router->get('/queue', [QueueController::class, 'status']);
$router->get('/api/queue/status', [QueueController::class, 'apiStatus']);

// 4. Dashboard (Petugas & Administrator)
$router->get('/dashboard', [DashboardController::class, 'staff']);
$router->get('/dashboard/staff', [DashboardController::class, 'staff']);
$router->get('/staff', [DashboardController::class, 'staff']);
$router->get('/dashboard/admin', [DashboardController::class, 'admin']);
$router->get('/admin', [DashboardController::class, 'admin']);

// 5. Autentikasi Pengguna
$router->get('/login', [AuthController::class, 'login']);
$router->get('/auth/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'register']);
$router->get('/auth/register', [AuthController::class, 'register']);
$router->get('/auth/quick-login', [AuthController::class, 'quickLogin']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/auth/logout', [AuthController::class, 'logout']);


// ============================================================================
// B. POST ROUTES (Mengubah Data / Mutasi / Autentikasi Form)
// ============================================================================

// 1. Autentikasi
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/register', [AuthController::class, 'register']);
$router->post('/auth/logout', [AuthController::class, 'logout']);

// 2. Operasi Antrean
$router->post('/queue/store', [QueueController::class, 'store']);
$router->post('/queues/create', [QueueController::class, 'store']);
$router->post('/queue/update-status', [QueueController::class, 'updateStatus']);
$router->post('/queue/cancel', [QueueController::class, 'cancel']);
$router->post('/api/queue/cancel', [QueueController::class, 'cancel']);
$router->post('/api/queue/action', [DashboardController::class, 'apiAction']);

// 3. Operasi Restoran (Admin)
$router->post('/restaurant/store', [RestaurantController::class, 'store']);
$router->post('/restaurant/update', [RestaurantController::class, 'update']);
$router->post('/restaurant/delete', [RestaurantController::class, 'delete']);

// 4. Operasi Dashboard Toko & Menu (Admin)
$router->post('/dashboard/admin/store-update', [DashboardController::class, 'storeUpdate']);
$router->post('/dashboard/admin/donut-add', [DashboardController::class, 'donutAdd']);
$router->post('/dashboard/admin/donut-delete', [DashboardController::class, 'donutDelete']);
$router->post('/dashboard/admin/queue-delete', [DashboardController::class, 'queueDelete']);

return $router;
