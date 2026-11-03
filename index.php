<?php
/**
 * ============================================================================
 * QueueBites – Mochi Mood Front Controller & MVC Router
 * File: index.php
 * ============================================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/Auth.php';

// Controllers
require_once __DIR__ . '/app/controllers/HomeController.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/RestaurantController.php';
require_once __DIR__ . '/app/controllers/QueueController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';

Auth::start();

// Tangkap route dari parameter ?url= atau REQUEST_URI
$url = $_GET['url'] ?? '';

if (empty($url)) {
    $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    
    if ($scriptDir !== '/' && strpos($reqPath, $scriptDir) === 0) {
        $reqPath = substr($reqPath, strlen($scriptDir));
    }
    
    $reqPath = trim($reqPath, '/');
    $reqPath = preg_replace('/\.php\.?$/', '', $reqPath);
    
    if (!empty($reqPath) && $reqPath !== 'index') {
        $url = $reqPath;
    }
}

$url = trim($url, '/');

// Dispatcher
switch ($url) {
    // 1. Landing Page
    case '':
    case 'home':
        (new HomeController())->index();
        break;

    // 2. Auth: Login & Register
    case 'login':
    case 'auth/login':
        (new AuthController())->login();
        break;

    case 'register':
    case 'auth/register':
        (new AuthController())->register();
        break;

    case 'auth/quick-login':
        (new AuthController())->quickLogin();
        break;

    case 'logout':
    case 'auth/logout':
        (new AuthController())->logout();
        break;

    // 3. Cafe & Menu Mochi Mood (Fokus Satu Restoran)
    case 'menu':
    case 'restaurants':
    case 'restaurants/detail':
        (new RestaurantController())->detail();
        break;

    // 4. Antrean: Ambil & Status Antrean
    case 'queues/create':
        (new QueueController())->create();
        break;

    case 'queues/status':
        (new QueueController())->status();
        break;

    // 5. Dashboard Petugas & Admin Mochi Mood
    case 'staff':
    case 'dashboard/staff':
        (new DashboardController())->staff();
        break;

    case 'admin':
    case 'dashboard/admin':
        (new DashboardController())->admin();
        break;

    case 'dashboard/admin/store-update':
        (new DashboardController())->storeUpdate();
        break;

    case 'dashboard/admin/donut-add':
        (new DashboardController())->donutAdd();
        break;

    case 'dashboard/admin/donut-delete':
        (new DashboardController())->donutDelete();
        break;

    case 'dashboard/admin/queue-delete':
        (new DashboardController())->queueDelete();
        break;

    // 6. Fetch API Endpoints
    case 'api/queue/status':
        (new QueueController())->apiStatus();
        break;

    case 'api/queue/action':
        (new DashboardController())->apiAction();
        break;

    case 'api/queue/cancel':
        (new QueueController())->cancel();
        break;

    default:
        (new HomeController())->index();
        break;
}
