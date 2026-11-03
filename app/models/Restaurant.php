<?php
/**
 * ============================================================================
 * QueueBites – Your Table, Your Time
 * File: app/models/Restaurant.php
 * Deskripsi: Model untuk mengelola data profil restoran dan status operasional.
 * ============================================================================
 */

require_once __DIR__ . '/../../config/database.php';

class Restaurant
{
    /**
     * Instance PDO Database
     */
    protected PDO $db;

    /**
     * Nama tabel
     */
    protected string $table = 'restaurants';

    /**
     * Daftar status operasional yang valid
     */
    public const ALLOWED_STATUSES = ['open', 'closed'];

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
     * Mengambil seluruh data restoran dengan opsi filter status.
     *
     * @param string|null $status Filter berdasarkan status ('open'|'closed')
     * @return array Daftar seluruh restoran
     */
    public function getAll(?string $status = null): array
    {
        if ($status !== null && in_array($status, self::ALLOWED_STATUSES, true)) {
            $stmt = $this->db->prepare("
                SELECT id, name, description, address, opening_hours, status, created_at 
                FROM {$this->table} 
                WHERE status = :status 
                ORDER BY name ASC
            ");
            $stmt->execute([':status' => $status]);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->query("
            SELECT id, name, description, address, opening_hours, status, created_at 
            FROM {$this->table} 
            ORDER BY id DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Mengambil data satu restoran berdasarkan ID.
     *
     * @param int $id ID Restoran
     * @return array|null Data restoran atau null jika tidak ditemukan
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, description, address, opening_hours, status, created_at 
            FROM {$this->table} 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Mengambil daftar restoran beserta jumlah antrean yang sedang menunggu hari ini.
     * Sangat memudahkan Controller saat menampilkan halaman beranda / direktori restoran.
     *
     * @return array Daftar restoran dengan kolom tambahan `waiting_count`
     */
    public function getWithWaitingCount(): array
    {
        $sql = "
            SELECT 
                r.id, 
                r.name, 
                r.description, 
                r.address, 
                r.opening_hours, 
                r.status, 
                r.created_at,
                COUNT(CASE WHEN q.status = 'waiting' AND (q.queue_date = CURDATE() OR DATE(q.created_at) = CURDATE()) THEN 1 END) AS waiting_count
            FROM {$this->table} r
            LEFT JOIN queues q ON r.id = q.restaurant_id
            GROUP BY r.id
            ORDER BY r.status DESC, r.name ASC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Menambahkan data restoran baru.
     *
     * @param array $data Array ['name' => ..., 'description' => ..., 'address' => ..., 'opening_hours' => ..., 'status' => ...]
     * @return int ID restoran yang baru dibuat
     * @throws InvalidArgumentException Jika input tidak valid
     * @throws RuntimeException Jika gagal menyimpan ke database
     */
    public function create(array $data): int
    {
        // 1. Validasi input
        $errors = $this->validateInput($data, true);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }

        $status = $data['status'] ?? 'open';

        // 2. Query insert
        $sql = "INSERT INTO {$this->table} (name, description, address, opening_hours, status, created_at)
                VALUES (:name, :description, :address, :opening_hours, :status, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':name'          => trim($data['name']),
            ':description'   => isset($data['description']) ? trim($data['description']) : null,
            ':address'       => trim($data['address']),
            ':opening_hours' => trim($data['opening_hours']),
            ':status'        => $status,
        ]);

        if (!$success) {
            throw new RuntimeException("Gagal menambahkan restoran baru.");
        }

        return (int)$this->db->lastInsertId();
    }

    /**
     * Memperbarui data restoran berdasarkan ID.
     *
     * @param int $id ID Restoran
     * @param array $data Data yang ingin diperbarui
     * @return bool True jika berhasil
     * @throws InvalidArgumentException Jika input tidak valid
     */
    public function update(int $id, array $data): bool
    {
        $existing = $this->getById($id);
        if (!$existing) {
            throw new InvalidArgumentException("Restoran dengan ID {$id} tidak ditemukan.");
        }

        $errors = $this->validateInput($data, false);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }

        $fields = [];
        $params = [':id' => $id];

        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = trim($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $fields[] = "description = :description";
            $params[':description'] = $data['description'] !== null ? trim($data['description']) : null;
        }

        if (isset($data['address'])) {
            $fields[] = "address = :address";
            $params[':address'] = trim($data['address']);
        }

        if (isset($data['opening_hours'])) {
            $fields[] = "opening_hours = :opening_hours";
            $params[':opening_hours'] = trim($data['opening_hours']);
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], self::ALLOWED_STATUSES, true)) {
                throw new InvalidArgumentException("Status '{$data['status']}' tidak valid. Pilihan: open, closed.");
            }
            $fields[] = "status = :status";
            $params[':status'] = $data['status'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Mengubah status operasional restoran ('open' atau 'closed').
     *
     * @param int $id ID Restoran
     * @param string $status Status baru ('open'|'closed')
     * @return bool True jika berhasil
     * @throws InvalidArgumentException Jika status tidak valid
     */
    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException("Status '{$status}' tidak valid. Harus 'open' atau 'closed'.");
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status WHERE id = :id");
        return $stmt->execute([
            ':status' => $status,
            ':id'     => $id
        ]);
    }

    /**
     * Menghapus data restoran berdasarkan ID.
     * Perhatian: Mengakibatkan antrean terkait terhapus sesuai aturan ON DELETE CASCADE.
     *
     * @param int $id ID Restoran
     * @return bool True jika berhasil
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Mencari restoran berdasarkan nama atau alamat.
     *
     * @param string $keyword Kata kunci pencarian
     * @return array Daftar restoran yang cocok
     */
    public function search(string $keyword): array
    {
        $searchTerm = '%' . trim($keyword) . '%';
        $stmt = $this->db->prepare("
            SELECT id, name, description, address, opening_hours, status, created_at 
            FROM {$this->table} 
            WHERE name LIKE :kw_name OR address LIKE :kw_addr 
            ORDER BY name ASC
        ");
        $stmt->execute([
            ':kw_name' => $searchTerm,
            ':kw_addr' => $searchTerm,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Validasi dasar data input restoran.
     *
     * @param array $data Input data
     * @param bool $isCreate Apakah proses pembuatan baru
     * @return array Kumpulan pesan error jika ada
     */
    protected function validateInput(array $data, bool $isCreate = true): array
    {
        $errors = [];

        if ($isCreate || isset($data['name'])) {
            if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
                $errors[] = 'Nama restoran wajib diisi minimal 2 karakter.';
            }
        }

        if ($isCreate || isset($data['address'])) {
            if (empty($data['address']) || strlen(trim($data['address'])) < 5) {
                $errors[] = 'Alamat restoran wajib diisi minimal 5 karakter.';
            }
        }

        if ($isCreate || isset($data['opening_hours'])) {
            if (empty($data['opening_hours'])) {
                $errors[] = 'Jam operasional restoran wajib diisi (contoh: 10:00 - 22:00).';
            }
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], self::ALLOWED_STATUSES, true)) {
                $errors[] = 'Status restoran tidak valid. Pilihan: open, closed.';
            }
        }

        return $errors;
    }
}

