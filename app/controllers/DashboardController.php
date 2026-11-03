<?php
/**
 * ============================================================================
 * QueueBites – Dashboard Controller
 * File: app/controllers/DashboardController.php
 * Deskripsi: Mengelola tampilan dan fungsionalitas panel operasional untuk
 *            Petugas Konter (Staff Dashboard) dan Administrator (Admin Dashboard).
 *            Menyajikan ringkasan KPI antrean (menunggu, sedang dilayani, selesai).
 * ============================================================================
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Queue.php';
require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../models/QueueHistory.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Csrf.php';
require_once __DIR__ . '/../../config/database.php';

class DashboardController extends BaseController
{
    protected Queue $queueModel;
    protected Restaurant $restaurantModel;
    protected QueueHistory $historyModel;

    public function __construct()
    {
        $this->queueModel = new Queue();
        $this->restaurantModel = new Restaurant();
        $this->historyModel = new QueueHistory();
    }

    /**
     * Menampilkan Dashboard Petugas Konter (GET /dashboard atau GET /dashboard/staff)
     * Menampilkan 3 metrik utama: Total antrean hari ini, sedang dilayani, dan menunggu.
     */
    public function staff(): void
    {
        Auth::start();
        $user = Auth::user();

        // Jika belum login, gunakan profil staf demo agar mudah diuji langsung
        if (!$user) {
            Auth::login([
                'id'    => 2,
                'name'  => 'Petugas Konter Mochi',
                'email' => 'staff@mochimood.com',
                'phone' => '081298765432',
                'role'  => 'staff'
            ]);
            $user = Auth::user();
        }

        // Pastikan hanya staff atau admin yang dapat membuka dashboard petugas
        if (!in_array($user['role'], ['staff', 'admin'], true)) {
            $this->redirect('index.php?url=/', 'Akses khusus untuk petugas atau administrator.', 'danger');
            return;
        }

        $restaurantId = 1; // Default resto Mochi Mood
        $restaurant = $this->restaurantModel->getById($restaurantId);
        $queues = $this->queueModel->getByRestaurant($restaurantId);
        $summary = $this->queueModel->getDashboardCounts($restaurantId);

        $this->view('dashboard/staff', [
            'queues'     => $queues,
            'summary'    => $summary,
            'restaurant' => $restaurant,
            'user'       => $user,
        ]);
    }

    /**
     * Menampilkan Dashboard Administrator (GET /dashboard/admin)
     * Menampilkan statistik operasional, ringkasan antrean, kelola toko, dan menu.
     */
    public function admin(): void
    {
        Auth::start();
        $user = Auth::user();

        // Autologin admin demo jika belum login untuk kemudahan penilaian
        if (!$user || $user['role'] !== 'admin') {
            Auth::login([
                'id'    => 1,
                'name'  => 'Admin Mochi Mood',
                'email' => 'admin@mochimood.com',
                'phone' => '081234567890',
                'role'  => 'admin'
            ]);
            $user = Auth::user();
        }

        $restaurantId = 1;
        $restaurant = $this->restaurantModel->getById($restaurantId);
        $queues = $this->queueModel->getByRestaurant($restaurantId);
        $summary = $this->queueModel->getDashboardCounts($restaurantId);

        // Ambil daftar menu dari database (dengan fallback)
        $donuts = [];
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM menu_items WHERE restaurant_id = :id ORDER BY id DESC");
            $stmt->execute([':id' => $restaurantId]);
            $donuts = $stmt->fetchAll();
        } catch (Throwable $t) {
            $donuts = [];
        }

        // Hitung rincian status antrean (selesai dan dibatalkan)
        $completed = 0;
        $cancelled = 0;
        foreach ($queues as $q) {
            if ($q['status'] === Queue::STATUS_COMPLETED) {
                $completed++;
            }
            if ($q['status'] === Queue::STATUS_CANCELLED) {
                $cancelled++;
            }
        }
        $summary['completed_count'] = $completed;
        $summary['cancelled_count'] = $cancelled;

        $success = $_GET['msg'] ?? null;
        $error   = $_GET['err'] ?? null;

        $this->view('dashboard/admin', [
            'restaurant' => $restaurant,
            'queues'     => $queues,
            'donuts'     => $donuts,
            'summary'    => $summary,
            'user'       => $user,
            'success'    => $success,
            'error'      => $error,
        ]);
    }

    /**
     * Endpoint API Aksi Petugas untuk Fetch API (POST /api/queue/action)
     * Aksi yang didukung: call, serve, complete, call_next.
     */
    public function apiAction(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $input = $this->getRequestData();
        $action  = trim($input['action'] ?? '');
        $queueId = isset($input['queue_id']) ? (int)$input['queue_id'] : 0;

        try {
            if ($action === 'call') {
                $this->queueModel->updateStatus($queueId, Queue::STATUS_CALLED);
                $this->json(['success' => true, 'message' => 'Antrean berhasil dipanggil.']);
            } elseif ($action === 'serve') {
                $this->queueModel->updateStatus($queueId, Queue::STATUS_SERVING);
                $this->json(['success' => true, 'message' => 'Mulai melayani pelanggan.']);
            } elseif ($action === 'complete') {
                $this->queueModel->updateStatus($queueId, Queue::STATUS_COMPLETED);
                $this->json(['success' => true, 'message' => 'Antrean telah selesai dilayani.']);
            } elseif ($action === 'call_next') {
                $next = $this->queueModel->callNextQueue(1);
                if ($next) {
                    $this->json([
                        'success' => true,
                        'message' => 'Memanggil nomor ' . $next['queue_number'],
                        'queue'   => $next
                    ]);
                } else {
                    $this->json([
                        'success' => false,
                        'message' => 'Tidak ada antrean yang sedang menunggu.'
                    ]);
                }
            } else {
                $this->json(['success' => false, 'message' => 'Aksi operasional tidak dikenali.'], 400);
            }
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Memperbarui profil dan status operasional toko dari Dashboard Admin (POST)
     */
    public function storeUpdate(): void
    {
        $this->requireAuth('admin');

        $name         = trim($_POST['name'] ?? '');
        $address      = trim($_POST['address'] ?? '');
        $openingHours = trim($_POST['opening_hours'] ?? '');
        $status       = trim($_POST['status'] ?? 'open');

        if (empty($name) || empty($address) || empty($openingHours)) {
            $this->redirect('index.php?url=dashboard/admin&err=' . urlencode('Nama, alamat, dan jam buka wajib diisi!'));
            return;
        }

        try {
            $this->restaurantModel->update(1, [
                'name'          => $name,
                'address'       => $address,
                'opening_hours' => $openingHours,
                'status'        => $status,
            ]);

            $this->redirect('index.php?url=dashboard/admin&msg=' . urlencode('Profil operasional restoran berhasil diperbarui!'));
        } catch (Throwable $e) {
            $this->redirect('index.php?url=dashboard/admin&err=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Menambahkan item menu donut baru dari Dashboard Admin (POST)
     */
    public function donutAdd(): void
    {
        $this->requireAuth('admin');

        $name  = trim($_POST['donut_name'] ?? '');
        $price = isset($_POST['donut_price']) ? (int)$_POST['donut_price'] : 0;
        $desc  = trim($_POST['donut_desc'] ?? '');
        $img   = trim($_POST['donut_image'] ?? '');

        if (empty($name) || $price <= 0) {
            $this->redirect('index.php?url=dashboard/admin&err=' . urlencode('Nama menu dan harga positif wajib diisi!'));
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO menu_items (restaurant_id, name, description, price, image_url) VALUES (1, :name, :desc, :price, :img)");
            $stmt->execute([
                ':name'  => $name,
                ':desc'  => $desc,
                ':price' => $price,
                ':img'   => $img,
            ]);

            $this->redirect('index.php?url=dashboard/admin&msg=' . urlencode('Menu baru berhasil ditambahkan!'));
        } catch (Throwable $e) {
            $this->redirect('index.php?url=dashboard/admin&err=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Menghapus item menu donut dari Dashboard Admin (POST)
     */
    public function donutDelete(): void
    {
        $this->requireAuth('admin');

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM menu_items WHERE id = :id");
            $stmt->execute([':id' => $id]);
        }

        $this->redirect('index.php?url=dashboard/admin&msg=' . urlencode('Menu berhasil dihapus.'));
    }

    /**
     * Menghapus data antrean dari riwayat (POST)
     */
    public function queueDelete(): void
    {
        $this->requireAuth('admin');

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $this->queueModel->delete($id);
        }

        $this->redirect('index.php?url=dashboard/admin&msg=' . urlencode('Antrean berhasil dihapus dari sistem.'));
    }

    /**
     * Membaca input JSON atau form POST
     */
    private function getRequestData(): array
    {
        $data = $_POST;
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $data = array_merge($data, $json);
            }
        }
        return $data;
    }
}
