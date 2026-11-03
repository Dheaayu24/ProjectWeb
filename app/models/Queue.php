<?php
/**
 * ============================================================================
 * QueueBites – Your Table, Your Time
 * File: app/models/Queue.php
 * Deskripsi: Model untuk mengelola sistem antrean restoran, penomoran antrean
 *            otomatis anti-duplikat, pelacakan posisi, dan pencatatan riwayat.
 * ============================================================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/QueueHistory.php';

class Queue
{
    /**
     * Instance PDO Database
     */
    protected PDO $db;

    /**
     * Nama tabel utama
     */
    protected string $table = 'queues';

    /**
     * Daftar status antrean yang valid
     */
    public const STATUS_WAITING   = 'waiting';
    public const STATUS_CALLED    = 'called';
    public const STATUS_SERVING   = 'serving';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const ALLOWED_STATUSES = [
        self::STATUS_WAITING,
        self::STATUS_CALLED,
        self::STATUS_SERVING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED
    ];

    /**
     * Constructor
     *
     * @param PDO|null $db Instance PDO opsional
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Mengambil seluruh data antrean dengan opsi filter lengkap.
     * Menggabungkan data pengguna dan restoran (JOIN) untuk kemudahan Controller.
     *
     * @param array $filters Filter: ['restaurant_id' => ..., 'status' => ..., 'date' => ..., 'user_id' => ...]
     * @return array Daftar antrean
     */
    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT 
                q.id,
                q.user_id,
                q.restaurant_id,
                q.queue_number,
                q.people_count,
                q.status,
                q.queue_date,
                q.created_at,
                q.updated_at,
                u.name AS user_name,
                u.email AS user_email,
                r.name AS restaurant_name
            FROM {$this->table} q
            JOIN users u ON q.user_id = u.id
            JOIN restaurants r ON q.restaurant_id = r.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['restaurant_id'])) {
            $sql .= " AND q.restaurant_id = :restaurant_id";
            $params[':restaurant_id'] = (int)$filters['restaurant_id'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND q.user_id = :user_id";
            $params[':user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND q.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $sql .= " AND q.queue_date = :date";
            $params[':date'] = $filters['date'];
        }

        $sql .= " ORDER BY q.queue_date DESC, q.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Mengambil detail satu antrean berdasarkan ID beserta data user dan restoran.
     *
     * @param int $id ID Antrean
     * @return array|null Data antrean atau null jika tidak ditemukan
     */
    public function getById(int $id): ?array
    {
        $sql = "
            SELECT 
                q.id,
                q.user_id,
                q.restaurant_id,
                q.queue_number,
                q.people_count,
                q.status,
                q.queue_date,
                q.created_at,
                q.updated_at,
                u.name AS user_name,
                u.email AS user_email,
                r.name AS restaurant_name,
                r.address AS restaurant_address
            FROM {$this->table} q
            JOIN users u ON q.user_id = u.id
            JOIN restaurants r ON q.restaurant_id = r.id
            WHERE q.id = :id 
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Mengambil daftar antrean berdasarkan ID Restoran.
     *
     * @param int $restaurantId ID Restoran
     * @param string|null $status Filter status antrean opsional
     * @param string|null $date Filter tanggal format 'Y-m-d' (default: hari ini)
     * @return array Daftar antrean di restoran tersebut
     */
    public function getByRestaurant(int $restaurantId, ?string $status = null, ?string $date = null): array
    {
        $targetDate = $date ?? date('Y-m-d');

        $sql = "
            SELECT 
                q.id,
                q.user_id,
                q.restaurant_id,
                q.queue_number,
                q.people_count,
                q.status,
                q.queue_date,
                q.created_at,
                q.updated_at,
                u.name AS user_name,
                u.email AS user_email
            FROM {$this->table} q
            JOIN users u ON q.user_id = u.id
            WHERE q.restaurant_id = :restaurant_id
              AND q.queue_date = :queue_date
        ";

        $params = [
            ':restaurant_id' => $restaurantId,
            ':queue_date'    => $targetDate,
        ];

        if ($status !== null && in_array($status, self::ALLOWED_STATUSES, true)) {
            $sql .= " AND q.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY q.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Menghitung jumlah antrean yang masih menunggu (status = 'waiting') di sebuah restoran.
     *
     * @param int $restaurantId ID Restoran
     * @param string|null $date Tanggal antrean (default: hari ini)
     * @return int Jumlah antrean berstatus waiting
     */
    public function getWaitingCount(int $restaurantId, ?string $date = null): int
    {
        $targetDate = $date ?? date('Y-m-d');

        $sql = "
            SELECT COUNT(*) AS total 
            FROM {$this->table} 
            WHERE restaurant_id = :restaurant_id 
              AND queue_date = :queue_date 
              AND status = :status
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':restaurant_id' => $restaurantId,
            ':queue_date'    => $targetDate,
            ':status'        => self::STATUS_WAITING,
        ]);

        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Mengambil urutan posisi antrean pengguna saat ini di antara antrean yang sedang menunggu.
     * 
     * Contoh:
     * - Posisi 1: Berarti antrean ini berada di urutan terdepan berikutnya untuk dipanggil.
     * - Posisi 3: Berarti antrean ini berada di urutan ke-3 (ada 2 antrean di depannya).
     * - Posisi 0: Jika antrean sudah bukan dalam status 'waiting' (misal: sudah called/completed/cancelled).
     *
     * @param int $queueId ID Antrean
     * @return int Posisi urutan antrean (1, 2, 3...) atau 0 jika tidak sedang waiting
     */
    public function getUserQueuePosition(int $queueId): int
    {
        // 1. Ambil data antrean yang bersangkutan
        $current = $this->getById($queueId);
        if (!$current || $current['status'] !== self::STATUS_WAITING) {
            return 0;
        }

        // 2. Hitung berapa banyak antrean berstatus 'waiting' pada restoran dan hari yang sama
        //    yang dibuat sebelum atau sama dengan ID antrean ini.
        $sql = "
            SELECT COUNT(*) AS position 
            FROM {$this->table} 
            WHERE restaurant_id = :restaurant_id 
              AND queue_date = :queue_date 
              AND status = :status 
              AND id <= :queue_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':restaurant_id' => (int)$current['restaurant_id'],
            ':queue_date'    => $current['queue_date'],
            ':status'        => self::STATUS_WAITING,
            ':queue_id'      => $queueId,
        ]);

        $row = $stmt->fetch();
        return (int)($row['position'] ?? 0);
    }

    /**
     * Helper: Menghitung berapa banyak antrean yang berada tepat di DEPAN antrean pengguna saat ini.
     *
     * @param int $queueId ID Antrean
     * @return int Jumlah rombongan di depan antrean pengguna (0 jika paling depan atau tidak waiting)
     */
    public function getPeopleAhead(int $queueId): int
    {
        $position = $this->getUserQueuePosition($queueId);
        return max(0, $position - 1);
    }

    /**
     * Menghasilkan nomor antrean berikutnya secara otomatis dan aman dari duplikasi.
     *
     * Pendekatan Keamanan Concurrency:
     * 1. Menggunakan format standar 'A-001', 'A-002', dst.
     * 2. Menjalankan query dengan locking `FOR UPDATE` di dalam transaksi database.
     * 3. Didukung oleh aturan integritas UNIQUE KEY `(restaurant_id, queue_date, queue_number)` pada tabel.
     *
     * @param int $restaurantId ID Restoran
     * @param string|null $date Tanggal antrean (format Y-m-d, default: hari ini)
     * @param string $prefix Awalan kode antrean (default: 'A')
     * @return string Nomor antrean berikutnya (contoh: 'A-001')
     */
    public function generateQueueNumber(int $restaurantId, ?string $date = null, string $prefix = 'A'): string
    {
        $targetDate = $date ?? date('Y-m-d');

        // Cari nomor antrean terakhir pada restoran dan tanggal tersebut
        // Menggunakan FOR UPDATE pada MySQL untuk mencegah race-condition saat ada request bersamaan
        $forUpdate = ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') ? ' FOR UPDATE' : '';
        $sql = "
            SELECT queue_number 
            FROM {$this->table} 
            WHERE restaurant_id = :restaurant_id 
              AND queue_date = :queue_date 
            ORDER BY id DESC 
            LIMIT 1{$forUpdate}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':restaurant_id' => $restaurantId,
            ':queue_date'    => $targetDate,
        ]);

        $lastQueue = $stmt->fetch();

        if (!$lastQueue || empty($lastQueue['queue_number'])) {
            // Belum ada antrean hari ini, mulai dari 001
            return sprintf('%s-%03d', $prefix, 1);
        }

        // Ekstrak angka dari nomor antrean sebelumnya (misal dari 'A-005' -> 5)
        $lastNumberStr = $lastQueue['queue_number'];
        if (preg_match('/(\d+)$/', $lastNumberStr, $matches)) {
            $nextSequence = (int)$matches[1] + 1;
        } else {
            $nextSequence = 1;
        }

        return sprintf('%s-%03d', $prefix, $nextSequence);
    }

    /**
     * Menambahkan antrean baru ke database.
     *
     * Fitur Keamanan & Integritas:
     * - Berjalan di dalam Database Transaction (ACID).
     * - Jika `queue_number` tidak disediakan, dibuat otomatis via `generateQueueNumber()`.
     * - Otomatis mencatat status awal ke tabel `queue_history`.
     *
     * @param array $data Data antrean: ['user_id' => ..., 'restaurant_id' => ..., 'people_count' => ...]
     * @return int ID antrean yang baru dibuat
     * @throws InvalidArgumentException Jika input tidak valid
     * @throws RuntimeException Jika terjadi kegagalan database
     */
    public function create(array $data): int
    {
        $errors = $this->validateInput($data, true);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }

        $userId       = (int)$data['user_id'];
        $restaurantId = (int)$data['restaurant_id'];
        $peopleCount  = (int)($data['people_count'] ?? 1);
        $status       = $data['status'] ?? self::STATUS_WAITING;
        $queueDate    = !empty($data['queue_date']) ? $data['queue_date'] : date('Y-m-d');

        // Buka transaksi untuk menjamin integritas penomoran dan riwayat
        $this->db->beginTransaction();

        try {
            // Generate nomor antrean jika belum ada di data input
            if (empty($data['queue_number'])) {
                $queueNumber = $this->generateQueueNumber($restaurantId, $queueDate);
            } else {
                $queueNumber = trim($data['queue_number']);
            }

            // Simpan ke tabel queues
            $sql = "
                INSERT INTO {$this->table} 
                    (user_id, restaurant_id, queue_number, people_count, status, queue_date, created_at, updated_at) 
                VALUES 
                    (:user_id, :restaurant_id, :queue_number, :people_count, :status, :queue_date, NOW(), NOW())
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id'       => $userId,
                ':restaurant_id' => $restaurantId,
                ':queue_number'  => $queueNumber,
                ':people_count'  => $peopleCount,
                ':status'        => $status,
                ':queue_date'    => $queueDate,
            ]);

            $newQueueId = (int)$this->db->lastInsertId();

            // Catat ke tabel queue_history
            $historyStmt = $this->db->prepare("
                INSERT INTO queue_history (queue_id, old_status, new_status, changed_at) 
                VALUES (:queue_id, NULL, :new_status, NOW())
            ");
            $historyStmt->execute([
                ':queue_id'   => $newQueueId,
                ':new_status' => $status,
            ]);

            // Commit transaksi jika seluruh query berhasil
            $this->db->commit();

            return $newQueueId;
        } catch (Throwable $e) {
            // Rollback jika terjadi kesalahan/duplikasi
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            // Tangani error duplicate entry dari MySQL UNIQUE index
            if ($e instanceof PDOException && $e->getCode() === '23000') {
                throw new RuntimeException("Gagal mengambil antrean: Terjadi tabrakan nomor antrean, silakan coba beberapa saat lagi.", 0, $e);
            }

            throw new RuntimeException("Gagal membuat antrean: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Mengubah status antrean dan OTOMATIS mencatat perubahannya ke `queue_history`.
     * Menggunakan Database Transaction untuk memastikan konsistensi data.
     *
     * @param int $queueId ID Antrean
     * @param string $newStatus Status baru ('waiting'|'called'|'serving'|'completed'|'cancelled')
     * @return bool True jika berhasil diperbarui
     * @throws InvalidArgumentException Jika status tidak valid atau antrean tidak ditemukan
     * @throws RuntimeException Jika transaksi database gagal
     */
    public function updateStatus(int $queueId, string $newStatus): bool
    {
        if (!in_array($newStatus, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException("Status '{$newStatus}' tidak valid.");
        }

        $this->db->beginTransaction();

        try {
            // Ambil status saat ini dengan penguncian baris (row locking pada MySQL)
            $forUpdate = ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') ? ' FOR UPDATE' : '';
            $stmtSelect = $this->db->prepare("
                SELECT status 
                FROM {$this->table} 
                WHERE id = :id{$forUpdate}
            ");
            $stmtSelect->execute([':id' => $queueId]);
            $current = $stmtSelect->fetch();

            if (!$current) {
                $this->db->rollBack();
                throw new InvalidArgumentException("Antrean dengan ID {$queueId} tidak ditemukan.");
            }

            $oldStatus = $current['status'];

            // Jika status tidak berubah, tidak perlu insert history baru
            if ($oldStatus === $newStatus) {
                $this->db->commit();
                return true;
            }

            // 1. Update tabel queues
            $stmtUpdate = $this->db->prepare("
                UPDATE {$this->table} 
                SET status = :new_status, updated_at = NOW() 
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':new_status' => $newStatus,
                ':id'         => $queueId,
            ]);

            // 2. Catat perubahan ke queue_history
            $stmtHistory = $this->db->prepare("
                INSERT INTO queue_history (queue_id, old_status, new_status, changed_at) 
                VALUES (:queue_id, :old_status, :new_status, NOW())
            ");
            $stmtHistory->execute([
                ':queue_id'   => $queueId,
                ':old_status' => $oldStatus,
                ':new_status' => $newStatus,
            ]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new RuntimeException("Gagal memperbarui status antrean: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Memperbarui informasi antrean umum (misal: jumlah orang atau nomor antrean manual).
     * Jika status juga diubah melalui array ini, method akan mendelegasikannya agar history tercatat.
     *
     * @param int $id ID Antrean
     * @param array $data Data yang ingin diperbarui
     * @return bool True jika berhasil
     */
    public function update(int $id, array $data): bool
    {
        $existing = $this->getById($id);
        if (!$existing) {
            throw new InvalidArgumentException("Antrean dengan ID {$id} tidak ditemukan.");
        }

        // Jika terdapat perubahan status, gunakan updateStatus agar riwayat tercatat aman
        if (isset($data['status']) && $data['status'] !== $existing['status']) {
            $this->updateStatus($id, $data['status']);
            unset($data['status']);
        }

        $fields = [];
        $params = [':id' => $id];

        if (isset($data['people_count'])) {
            $count = (int)$data['people_count'];
            if ($count <= 0) {
                throw new InvalidArgumentException("Jumlah orang minimal 1.");
            }
            $fields[] = "people_count = :people_count";
            $params[':people_count'] = $count;
        }

        if (!empty($data['queue_number'])) {
            $fields[] = "queue_number = :queue_number";
            $params[':queue_number'] = trim($data['queue_number']);
        }

        if (empty($fields)) {
            return true;
        }

        $fields[] = "updated_at = NOW()";

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Menghapus data antrean berdasarkan ID.
     * Riwayat terkait akan terhapus otomatis via database foreign key ON DELETE CASCADE.
     *
     * @param int $id ID Antrean
     * @return bool True jika berhasil
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Mengambil daftar riwayat antrean milik user tertentu.
     *
     * @param int $userId ID Pengguna
     * @param string|null $status Filter status opsional
     * @return array Daftar antrean milik user
     */
    public function getByUser(int $userId, ?string $status = null): array
    {
        $filters = ['user_id' => $userId];
        if ($status !== null) {
            $filters['status'] = $status;
        }

        return $this->getAll($filters);
    }

    /**
     * Mengambil antrean aktif yang sedang berjalan milik seorang pelanggan hari ini.
     * Berguna untuk mencegah user membuat antrean ganda di restoran yang sama sebelum antrean sebelumnya selesai.
     *
     * @param int $userId ID Pengguna
     * @param int|null $restaurantId ID Restoran opsional
     * @return array|null Data antrean aktif atau null jika tidak ada
     */
    public function getActiveQueueByUser(int $userId, ?int $restaurantId = null): ?array
    {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE user_id = :user_id 
              AND status IN ('waiting', 'called', 'serving')
              AND queue_date = CURDATE()
        ";

        $params = [':user_id' => $userId];

        if ($restaurantId !== null) {
            $sql .= " AND restaurant_id = :restaurant_id";
            $params[':restaurant_id'] = $restaurantId;
        }

        $sql .= " ORDER BY id DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Memanggil antrean terdepan berikutnya yang masih 'waiting' pada sebuah restoran.
     * Otomatis mengubah status menjadi 'called' dan mencatat ke riwayat.
     * Helper unggulan untuk Staff Controller / Tombol "Panggil Antrean Berikutnya".
     *
     * @param int $restaurantId ID Restoran
     * @return array|null Data antrean yang baru dipanggil atau null jika tidak ada antrean waiting
     */
    public function callNext(int $restaurantId): ?array
    {
        $targetDate = date('Y-m-d');

        // Cari antrean waiting paling awal
        $sql = "
            SELECT id FROM {$this->table} 
            WHERE restaurant_id = :restaurant_id 
              AND queue_date = :queue_date 
              AND status = :status 
            ORDER BY id ASC 
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':restaurant_id' => $restaurantId,
            ':queue_date'    => $targetDate,
            ':status'        => self::STATUS_WAITING,
        ]);

        $next = $stmt->fetch();
        if (!$next) {
            return null;
        }

        $queueId = (int)$next['id'];
        $this->updateStatus($queueId, self::STATUS_CALLED);

        return $this->getById($queueId);
    }

    /**
     * Validasi dasar data input antrean.
     *
     * @param array $data Input data
     * @param bool $isCreate Apakah proses pembuatan antrean baru
     * @return array Kumpulan pesan error
     */
    protected function validateInput(array $data, bool $isCreate = true): array
    {
        $errors = [];

        if ($isCreate || isset($data['user_id'])) {
            if (empty($data['user_id']) || !is_numeric($data['user_id']) || (int)$data['user_id'] <= 0) {
                $errors[] = 'user_id wajib berupa angka positif yang valid.';
            }
        }

        if ($isCreate || isset($data['restaurant_id'])) {
            if (empty($data['restaurant_id']) || !is_numeric($data['restaurant_id']) || (int)$data['restaurant_id'] <= 0) {
                $errors[] = 'restaurant_id wajib berupa angka positif yang valid.';
            }
        }

        if (isset($data['people_count'])) {
            if (!is_numeric($data['people_count']) || (int)$data['people_count'] < 1) {
                $errors[] = 'Jumlah tamu/orang (people_count) minimal 1.';
            }
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], self::ALLOWED_STATUSES, true)) {
                $errors[] = 'Status tidak valid. Pilihan: ' . implode(', ', self::ALLOWED_STATUSES) . '.';
            }
        }

        if (!empty($data['queue_date'])) {
            $d = DateTime::createFromFormat('Y-m-d', $data['queue_date']);
            if (!$d || $d->format('Y-m-d') !== $data['queue_date']) {
                $errors[] = 'Format queue_date tidak valid. Gunakan format YYYY-MM-DD.';
            }
        }

        return $errors;
    }
}
