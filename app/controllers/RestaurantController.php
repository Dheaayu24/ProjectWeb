<?php
/**
 * ============================================================================
 * QueueBites – Restaurant Controller
 * File: app/controllers/RestaurantController.php
 * Deskripsi: Mengelola data restoran, direktori pencarian, halaman detail,
 *            serta operasi CRUD restoran dengan hak akses administrator.
 * ============================================================================
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../models/Queue.php';
require_once __DIR__ . '/../../config/database.php';

class RestaurantController extends BaseController
{
    protected Restaurant $restaurantModel;
    protected Queue $queueModel;

    public function __construct()
    {
        $this->restaurantModel = new Restaurant();
        $this->queueModel = new Queue();
    }

    /**
     * Menampilkan daftar seluruh restoran (GET /restaurants)
     * Mendukung fitur pencarian melalui parameter GET 'q'.
     */
    public function index(): void
    {
        $keyword = isset($_GET['q']) ? trim($_GET['q']) : null;

        if (!empty($keyword)) {
            $restaurants = $this->restaurantModel->search($keyword);
        } else {
            $restaurants = $this->restaurantModel->getWithWaitingCount();
            if (empty($restaurants)) {
                $restaurants = $this->restaurantModel->getAll();
            }
        }

        // Jika request dari Fetch API yang menginginkan JSON
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            $this->json([
                'success' => true,
                'count'   => count($restaurants),
                'data'    => $restaurants
            ]);
            return;
        }

        $this->view('restaurants/index', [
            'restaurants' => $restaurants,
            'keyword'     => $keyword
        ]);
    }

    /**
     * Menampilkan detail restoran beserta status antrean dan menu (GET /restaurant?id=1)
     */
    public function detail(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 1; // Default ke ID 1 (Mochi Mood)

        if ($id <= 0) {
            $this->redirect('index.php?url=restaurants', 'ID restoran tidak valid.', 'danger');
            return;
        }

        $restaurant = $this->restaurantModel->getById($id);

        if (!$restaurant) {
            $this->redirect('index.php?url=restaurants', 'Restoran tidak ditemukan.', 'danger');
            return;
        }

        // Ambil jumlah antrean yang sedang menunggu hari ini
        $waitingCount = $this->queueModel->getWaitingCount($id);

        // Ambil daftar menu dari database restoran terkait
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM menu_items WHERE restaurant_id = :restaurant_id ORDER BY id ASC");
        $stmt->execute([':restaurant_id' => $id]);
        $donuts = $stmt->fetchAll();

        $this->view('restaurants/detail', [
            'restaurant'   => $restaurant,
            'donuts'       => $donuts,
            'waitingCount' => $waitingCount
        ]);
    }

    /**
     * Menambahkan restoran baru (POST /restaurant/store)
     * Hanya dapat dilakukan oleh Administrator dengan proteksi CSRF.
     */
    public function store(): void
    {
        // 1. Otorisasi: Hanya Admin
        $this->requireAuth('admin');

        // 2. Proteksi CSRF
        $this->validateCsrf();

        // 3. Validasi Input
        $name         = trim($_POST['name'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $address      = trim($_POST['address'] ?? '');
        $openingHours = trim($_POST['opening_hours'] ?? '');
        $status       = trim($_POST['status'] ?? 'open');

        $errors = [];
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Nama restoran wajib diisi minimal 2 karakter.';
        }
        if (empty($address) || strlen($address) < 5) {
            $errors[] = 'Alamat restoran wajib diisi minimal 5 karakter.';
        }
        if (empty($openingHours)) {
            $errors[] = 'Jam operasional wajib diisi (contoh: 10:00 - 22:00).';
        }
        if (!in_array($status, Restaurant::ALLOWED_STATUSES, true)) {
            $errors[] = 'Status restoran tidak valid. Pilihan: open, closed.';
        }

        if (!empty($errors)) {
            if ($this->isJsonRequest()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
            }
            $this->redirect('index.php?url=dashboard/admin', implode(' ', $errors), 'danger');
            return;
        }

        try {
            $newId = $this->restaurantModel->create([
                'name'          => $name,
                'description'   => $description,
                'address'       => $address,
                'opening_hours' => $openingHours,
                'status'        => $status,
            ]);

            if ($this->isJsonRequest()) {
                $this->json(['success' => true, 'message' => 'Restoran berhasil ditambahkan.', 'id' => $newId]);
            }

            $this->redirect('index.php?url=dashboard/admin', 'Restoran baru berhasil ditambahkan!', 'success');
        } catch (Throwable $e) {
            if ($this->isJsonRequest()) {
                $this->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            $this->redirect('index.php?url=dashboard/admin', 'Gagal menambahkan restoran: ' . $e->getMessage(), 'danger');
        }
    }

    /**
     * Memperbarui data profil restoran (POST /restaurant/update)
     * Hanya dapat dilakukan oleh Administrator.
     */
    public function update(): void
    {
        $this->requireAuth('admin');
        $this->validateCsrf();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('index.php?url=dashboard/admin', 'ID restoran tidak valid.', 'danger');
            return;
        }

        $existing = $this->restaurantModel->getById($id);
        if (!$existing) {
            $this->redirect('index.php?url=dashboard/admin', 'Restoran tidak ditemukan.', 'danger');
            return;
        }

        $data = [];
        if (isset($_POST['name'])) {
            $data['name'] = trim($_POST['name']);
        }
        if (isset($_POST['description'])) {
            $data['description'] = trim($_POST['description']);
        }
        if (isset($_POST['address'])) {
            $data['address'] = trim($_POST['address']);
        }
        if (isset($_POST['opening_hours'])) {
            $data['opening_hours'] = trim($_POST['opening_hours']);
        }
        if (isset($_POST['status'])) {
            $data['status'] = trim($_POST['status']);
        }

        try {
            $this->restaurantModel->update($id, $data);

            if ($this->isJsonRequest()) {
                $this->json(['success' => true, 'message' => 'Data restoran berhasil diperbarui.']);
            }

            $this->redirect('index.php?url=dashboard/admin', 'Data restoran berhasil diperbarui!', 'success');
        } catch (Throwable $e) {
            if ($this->isJsonRequest()) {
                $this->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            $this->redirect('index.php?url=dashboard/admin', 'Gagal memperbarui restoran: ' . $e->getMessage(), 'danger');
        }
    }

    /**
     * Menghapus data restoran (POST /restaurant/delete)
     * Hanya dapat dilakukan oleh Administrator.
     */
    public function delete(): void
    {
        $this->requireAuth('admin');
        $this->validateCsrf();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('index.php?url=dashboard/admin', 'ID restoran tidak valid.', 'danger');
            return;
        }

        // Cegah menghapus restoran default demo (ID 1)
        if ($id === 1) {
            $this->redirect('index.php?url=dashboard/admin', 'Restoran utama (ID 1) tidak dapat dihapus.', 'warning');
            return;
        }

        try {
            $this->restaurantModel->delete($id);

            if ($this->isJsonRequest()) {
                $this->json(['success' => true, 'message' => 'Restoran berhasil dihapus.']);
            }

            $this->redirect('index.php?url=dashboard/admin', 'Restoran berhasil dihapus.', 'success');
        } catch (Throwable $e) {
            $this->redirect('index.php?url=dashboard/admin', 'Gagal menghapus restoran: ' . $e->getMessage(), 'danger');
        }
    }

    /**
     * Endpoint API JSON daftar restoran untuk Fetch API (GET /api/restaurants)
     */
    public function apiList(): void
    {
        $restaurants = $this->restaurantModel->getWithWaitingCount();
        $this->json([
            'success' => true,
            'count'   => count($restaurants),
            'data'    => $restaurants
        ]);
    }

    /**
     * Mengecek apakah request adalah AJAX / Fetch API
     */
    private function isJsonRequest(): bool
    {
        return (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (!empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));
    }
}
