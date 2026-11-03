<?php
/**
 * ============================================================================
 * QueueBites – Queue Controller
 * File: app/controllers/QueueController.php
 * Deskripsi: Mengelola siklus hidup antrean: pembuatan nomor urut unik (A023),
 *            pelacakan posisi real-time, pembaruan status, pembatalan aman,
 *            serta respons endpoint JSON untuk Fetch API.
 * ============================================================================
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Queue.php';
require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../models/QueueHistory.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Csrf.php';

class QueueController extends BaseController
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
     * Menampilkan daftar antrean (GET /queues)
     * - Jika pengguna login: menampilkan riwayat antreannya
     * - Jika request meminta JSON: mengembalikan data antrean dalam format JSON
     */
    public function index(): void
    {
        Auth::start();
        $user = Auth::user();

        if ($user) {
            $queues = $this->queueModel->getByUser((int)$user['id']);
        } else {
            $queues = $this->queueModel->getByRestaurant(1);
        }

        if ($this->isJsonRequest() || (isset($_GET['format']) && $_GET['format'] === 'json')) {
            $this->json([
                'success' => true,
                'total'   => count($queues),
                'data'    => $queues
            ]);
            return;
        }

        // Jika ada antrean aktif hari ini, arahkan langsung ke tiket live
        if ($user) {
            $active = $this->queueModel->getActiveQueueByUser((int)$user['id'], 1);
            if ($active) {
                $this->redirect('index.php?url=queues/status&id=' . $active['id']);
                return;
            }
        }

        $this->redirect('index.php?url=queues/create');
    }

    /**
     * Menampilkan form pendaftaran antrean baru (GET /queues/create)
     */
    public function create(): void
    {
        Auth::start();
        $user = Auth::user();

        $restaurantId = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 1;
        $restaurant = $this->restaurantModel->getById($restaurantId);

        if (!$restaurant) {
            $restaurant = $this->restaurantModel->getById(1);
        }

        $error = null;
        if (!empty($_SESSION['flash']) && $_SESSION['flash']['type'] === 'danger') {
            $error = $_SESSION['flash']['message'];
            unset($_SESSION['flash']);
        }

        $this->view('queues/create', [
            'restaurant' => $restaurant,
            'user'       => $user,
            'error'      => $error
        ]);
    }

    /**
     * Memproses pembuatan antrean baru (POST /queue/store atau POST /queues/create)
     * Menghasilkan nomor antrean otomatis (A023) dan mencatat riwayat awal.
     */
    public function store(): void
    {
        Auth::start();
        $user = Auth::user();

        // 1. Validasi CSRF Token
        $this->validateCsrf();

        // 2. Ambil data input
        $customerName  = trim($_POST['customer_name'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');
        $type          = trim($_POST['type'] ?? 'dine_in');
        $peopleCount   = isset($_POST['people_count']) ? (int)$_POST['people_count'] : 1;
        $restaurantId  = isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 1;

        // Jika tipe takeaway, jumlah rombongan default 1
        if ($type === 'takeaway') {
            $peopleCount = 1;
        }

        // 3. Validasi ketat input
        $errors = [];

        if (empty($customerName) || strlen($customerName) < 2) {
            $errors[] = 'Nama pemesan wajib diisi minimal 2 karakter.';
        }

        if (empty($customerPhone) || strlen($customerPhone) < 8) {
            $errors[] = 'Nomor telepon / WhatsApp wajib diisi dengan benar.';
        }

        if (!in_array($type, [Queue::TYPE_DINE_IN, Queue::TYPE_TAKEAWAY], true)) {
            $errors[] = 'Pilihan kunjungan tidak valid (pilih Dine-In atau Takeaway).';
        }

        if ($type === 'dine_in' && $peopleCount < 1) {
            $errors[] = 'Jumlah pengunjung minimal 1 orang.';
        }

        // Periksa keberadaan dan status restoran
        $restaurant = $this->restaurantModel->getById($restaurantId);
        if (!$restaurant) {
            $errors[] = 'Restoran yang dipilih tidak ditemukan.';
        } elseif ($restaurant['status'] === 'closed') {
            $errors[] = 'Maaf, restoran sedang tutup dan tidak menerima antrean baru saat ini.';
        }

        // Jika ada error validasi, kembalikan ke form
        if (!empty($errors)) {
            if ($this->isJsonRequest()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
            }
            $this->view('queues/create', [
                'restaurant' => $restaurant ?? ['name' => 'Mochi Mood'],
                'user'       => $user,
                'error'      => implode(' ', $errors)
            ]);
            return;
        }

        // 4. Tentukan user_id pemilik antrean (User login atau default customer guest)
        $userId = $user ? (int)$user['id'] : 3; // ID 3 adalah Budi S. / customer default

        try {
            // Model Queue menangani penomoran otomatis A023, simpan ke database,
            // dan pencatatan riwayat awal ke queue_history via transaksi PDO
            $newQueueId = $this->queueModel->create([
                'user_id'        => $userId,
                'restaurant_id'  => $restaurantId,
                'customer_name'  => $customerName,
                'customer_phone' => $customerPhone,
                'people_count'   => $peopleCount,
                'type'           => $type,
                'status'         => Queue::STATUS_WAITING,
                'queue_date'     => date('Y-m-d'),
            ]);

            $newQueue = $this->queueModel->getById($newQueueId);

            // Simpan ID antrean aktif ke session pengguna agar dapat dipantau
            $_SESSION['active_queue_id'] = $newQueueId;

            // Jika dipanggil via Fetch API, kembalikan respons JSON
            if ($this->isJsonRequest()) {
                $this->json([
                    'success'      => true,
                    'message'      => 'Nomor antrean berhasil dibuat!',
                    'queue_id'     => $newQueueId,
                    'queue_number' => $newQueue['queue_number'],
                    'redirect'     => 'index.php?url=queues/status&id=' . $newQueueId
                ]);
            }

            // Redirect ke halaman status antrean (Live Ticket)
            $this->redirect('index.php?url=queues/status&id=' . $newQueueId, 'Nomor antrean Anda berhasil dibuat!', 'success');
        } catch (Throwable $e) {
            if ($this->isJsonRequest()) {
                $this->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            $this->view('queues/create', [
                'restaurant' => $restaurant,
                'user'       => $user,
                'error'      => 'Gagal membuat antrean: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Menampilkan status tiket dan posisi antrean pengguna (GET /queues/status?id=1)
     */
    public function status(): void
    {
        Auth::start();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_SESSION['active_queue_id'] ?? 0);

        if ($id <= 0) {
            // Cari antrean aktif hari ini untuk pengguna yang sedang login
            $user = Auth::user();
            if ($user) {
                $active = $this->queueModel->getActiveQueueByUser((int)$user['id'], 1);
                if ($active) {
                    $id = (int)$active['id'];
                }
            }

            if ($id <= 0) {
                // Ambil antrean pertama di toko Mochi Mood
                $first = $this->queueModel->getByRestaurant(1);
                if (!empty($first)) {
                    $id = (int)$first[0]['id'];
                } else {
                    $this->redirect('index.php?url=queues/create');
                    return;
                }
            }
        }

        $queue = $this->queueModel->getById($id);

        if (!$queue) {
            $this->redirect('index.php?url=queues/create', 'Antrean tidak ditemukan.', 'danger');
            return;
        }

        // Ambil metrik posisi, orang di depan, dan estimasi waktu tunggu
        $position    = $this->queueModel->getUserQueuePosition($id);
        $peopleAhead = $this->queueModel->getPeopleAhead($id);
        $estMinutes  = $this->queueModel->getEstimatedWaitMinutes($id);

        $this->view('queues/status', [
            'queue'       => $queue,
            'position'    => $position,
            'peopleAhead' => $peopleAhead,
            'estMinutes'  => $estMinutes
        ]);
    }

    /**
     * Memperbarui status antrean oleh Petugas/Admin (POST /queue/update-status)
     * Status yang valid: waiting, called, serving, completed, cancelled.
     * Mencatat otomatis perubahan ke tabel queue_history.
     */
    public function updateStatus(): void
    {
        // 1. Cek otorisasi role (Hanya Petugas dan Administrator)
        $this->requireStaffOrAdmin();

        // 2. Tangkap input dari POST atau JSON payload
        $input = $this->getRequestData();
        $queueId   = isset($input['queue_id']) ? (int)$input['queue_id'] : 0;
        $newStatus = trim($input['status'] ?? '');

        if ($queueId <= 0) {
            $this->respondError('ID antrean tidak valid.', 400);
            return;
        }

        if (!in_array($newStatus, Queue::ALLOWED_STATUSES, true)) {
            $this->respondError("Status '{$newStatus}' tidak valid. Pilihan: " . implode(', ', Queue::ALLOWED_STATUSES), 422);
            return;
        }

        $queue = $this->queueModel->getById($queueId);
        if (!$queue) {
            $this->respondError('Antrean tidak ditemukan.', 404);
            return;
        }

        try {
            // Model memperbarui status dan mencatat log audit ke queue_history
            $this->queueModel->updateStatus($queueId, $newStatus);

            $msg = "Status antrean #{$queue['queue_number']} berhasil diubah menjadi '{$newStatus}'.";

            if ($this->isJsonRequest()) {
                $this->json([
                    'success'    => true,
                    'message'    => $msg,
                    'queue_id'   => $queueId,
                    'new_status' => $newStatus
                ]);
            }

            $this->redirect('index.php?url=dashboard/staff', $msg, 'success');
        } catch (Throwable $e) {
            $this->respondError('Gagal memperbarui status antrean: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Membatalkan antrean (POST /queue/cancel)
     * Pengguna hanya boleh membatalkan antrean miliknya sendiri,
     * kecuali jika pengguna adalah petugas atau administrator.
     */
    public function cancel(): void
    {
        Auth::start();
        $user = Auth::user();

        $input = $this->getRequestData();
        $queueId = isset($input['queue_id']) ? (int)$input['queue_id'] : 0;

        if ($queueId <= 0) {
            $this->respondError('ID antrean tidak valid.', 400);
            return;
        }

        $queue = $this->queueModel->getById($queueId);
        if (!$queue) {
            $this->respondError('Antrean tidak ditemukan.', 404);
            return;
        }

        // Pemeriksaan Otorisasi Hak Milik:
        // Jika bukan admin dan bukan staff, pastikan antrean ini adalah milik user yang login
        if ($user && !in_array($user['role'], ['admin', 'staff'], true)) {
            if ((int)$queue['user_id'] !== (int)$user['id']) {
                $this->respondError('Anda tidak memiliki otorisasi untuk membatalkan antrean milik orang lain.', 403);
                return;
            }
        }

        try {
            // Batalkan antrean via Model (mencatat log ke queue_history)
            $this->queueModel->cancelQueue($queueId);

            if ($this->isJsonRequest()) {
                $this->json([
                    'success' => true,
                    'message' => 'Antrean berhasil dibatalkan.'
                ]);
            }

            $this->redirect('index.php?url=queues/create', 'Antrean berhasil dibatalkan.', 'info');
        } catch (Throwable $e) {
            $this->respondError('Gagal membatalkan antrean: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Endpoint API status antrean secara real-time untuk Fetch API (GET /api/queue/status?id=1)
     */
    public function apiStatus(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'Parameter ID antrean tidak valid.'], 400);
            return;
        }

        $queue = $this->queueModel->getById($id);

        if (!$queue) {
            $this->json(['success' => false, 'message' => 'Antrean tidak ditemukan.'], 404);
            return;
        }

        $pos   = $this->queueModel->getUserQueuePosition($id);
        $ahead = $this->queueModel->getPeopleAhead($id);
        $est   = $this->queueModel->getEstimatedWaitMinutes($id);

        $this->json([
            'success'     => true,
            'queue'       => $queue,
            'position'    => $pos,
            'ahead_count' => $ahead,
            'est_minutes' => $est,
            'timestamp'   => time(),
        ]);
    }

    /**
     * Membaca data payload request baik dari form POST biasa maupun application/json
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

    /**
     * Helper merespons error dengan format JSON atau Redirect
     */
    private function respondError(string $message, int $code = 400): void
    {
        if ($this->isJsonRequest()) {
            $this->json(['success' => false, 'message' => $message], $code);
        }
        $this->redirect('index.php?url=dashboard/staff', $message, 'danger');
    }

    /**
     * Cek apakah permintaan berasal dari Fetch API
     */
    private function isJsonRequest(): bool
    {
        return (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (!empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));
    }
}
