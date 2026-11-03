<?php
/**
 * ============================================================================
 * QueueBites – Your Table, Your Time
 * File: app/models/QueueHistory.php
 * Deskripsi: Model untuk mencatat dan mengelola riwayat perubahan status antrean.
 * ============================================================================
 */

require_once __DIR__ . '/../../config/database.php';

class QueueHistory
{
    /**
     * Instance PDO Database
     */
    protected PDO $db;

    /**
     * Nama tabel
     */
    protected string $table = 'queue_history';

    /**
     * Daftar status yang valid
     */
    public const ALLOWED_STATUSES = ['waiting', 'called', 'serving', 'completed', 'cancelled'];

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
     * Mengambil seluruh data riwayat antrean beserta detail nomor antrean dan nama restoran.
     *
     * @param int $limit Batas jumlah data yang diambil
     * @return array Daftar seluruh catatan riwayat antrean
     */
    public function getAll(int $limit = 100): array
    {
        $sql = "
            SELECT 
                qh.id,
                qh.queue_id,
                qh.old_status,
                qh.new_status,
                qh.changed_at,
                q.queue_number,
                u.name AS user_name,
                r.name AS restaurant_name
            FROM {$this->table} qh
            JOIN queues q ON qh.queue_id = q.id
            JOIN users u ON q.user_id = u.id
            JOIN restaurants r ON q.restaurant_id = r.id
            ORDER BY qh.id DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Mengambil satu catatan riwayat berdasarkan ID.
     *
     * @param int $id ID Riwayat
     * @return array|null Data riwayat atau null jika tidak ditemukan
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT qh.*, q.queue_number, r.name AS restaurant_name 
            FROM {$this->table} qh
            JOIN queues q ON qh.queue_id = q.id
            JOIN restaurants r ON q.restaurant_id = r.id
            WHERE qh.id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Mengambil seluruh urutan riwayat status untuk sebuah antrean tertentu (kronologis).
     *
     * @param int $queueId ID antrean (queues.id)
     * @return array Urutan riwayat perubahan status antrean
     */
    public function getByQueueId(int $queueId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, queue_id, old_status, new_status, changed_at 
            FROM {$this->table} 
            WHERE queue_id = :queue_id 
            ORDER BY id ASC
        ");
        $stmt->execute([':queue_id' => $queueId]);

        return $stmt->fetchAll();
    }

    /**
     * Menambahkan catatan perubahan status antrean baru.
     *
     * @param array $data Array ['queue_id' => ..., 'old_status' => ..., 'new_status' => ...]
     * @return int ID catatan riwayat yang baru dibuat
     * @throws InvalidArgumentException Jika validasi gagal
     * @throws RuntimeException Jika penyimpanan gagal
     */
    public function create(array $data): int
    {
        $errors = $this->validateInput($data);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }

        $sql = "INSERT INTO {$this->table} (queue_id, old_status, new_status, changed_at)
                VALUES (:queue_id, :old_status, :new_status, NOW())";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':queue_id'   => (int)$data['queue_id'],
            ':old_status' => !empty($data['old_status']) ? $data['old_status'] : null,
            ':new_status' => $data['new_status'],
        ]);

        if (!$success) {
            throw new RuntimeException("Gagal menyimpan riwayat status antrean.");
        }

        return (int)$this->db->lastInsertId();
    }

    /**
     * Memperbarui data riwayat status antrean.
     *
     * @param int $id ID Riwayat
     * @param array $data Data yang diperbarui
     * @return bool True jika berhasil
     */
    public function update(int $id, array $data): bool
    {
        $existing = $this->getById($id);
        if (!$existing) {
            throw new InvalidArgumentException("Catatan riwayat dengan ID {$id} tidak ditemukan.");
        }

        $fields = [];
        $params = [':id' => $id];

        if (array_key_exists('old_status', $data)) {
            if ($data['old_status'] !== null && !in_array($data['old_status'], self::ALLOWED_STATUSES, true)) {
                throw new InvalidArgumentException("old_status '{$data['old_status']}' tidak valid.");
            }
            $fields[] = "old_status = :old_status";
            $params[':old_status'] = $data['old_status'];
        }

        if (isset($data['new_status'])) {
            if (!in_array($data['new_status'], self::ALLOWED_STATUSES, true)) {
                throw new InvalidArgumentException("new_status '{$data['new_status']}' tidak valid.");
            }
            $fields[] = "new_status = :new_status";
            $params[':new_status'] = $data['new_status'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Menghapus satu catatan riwayat berdasarkan ID.
     *
     * @param int $id ID Riwayat
     * @return bool True jika berhasil
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Menghapus seluruh catatan riwayat untuk antrean tertentu.
     *
     * @param int $queueId ID antrean
     * @return bool True jika berhasil
     */
    public function deleteByQueueId(int $queueId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE queue_id = :queue_id");
        return $stmt->execute([':queue_id' => $queueId]);
    }

    /**
     * Mengambil log aktivitas status antrean terbaru untuk dashboard Staff / Admin.
     *
     * @param int $limit Batas data yang ditampilkan
     * @return array Daftar riwayat terbaru
     */
    public function getRecent(int $limit = 10): array
    {
        return $this->getAll($limit);
    }

    /**
     * Validasi dasar data input riwayat antrean.
     *
     * @param array $data Input data
     * @return array Kumpulan pesan error jika ada
     */
    protected function validateInput(array $data): array
    {
        $errors = [];

        if (empty($data['queue_id']) || !is_numeric($data['queue_id']) || (int)$data['queue_id'] <= 0) {
            $errors[] = 'queue_id harus berupa angka positif yang valid.';
        }

        if (empty($data['new_status']) || !in_array($data['new_status'], self::ALLOWED_STATUSES, true)) {
            $errors[] = 'new_status wajib diisi dengan salah satu: ' . implode(', ', self::ALLOWED_STATUSES) . '.';
        }

        if (!empty($data['old_status']) && !in_array($data['old_status'], self::ALLOWED_STATUSES, true)) {
            $errors[] = 'old_status tidak valid. Harus salah satu: ' . implode(', ', self::ALLOWED_STATUSES) . '.';
        }

        return $errors;
    }
}

