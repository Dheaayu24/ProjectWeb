<?php
/**
 * ============================================================================
 * QueueBites – Your Table, Your Time
 * File: app/models/User.php
 * Deskripsi: Model untuk mengelola data tabel 'users' (Admin, Staff, Customer).
 * ============================================================================
 */

require_once __DIR__ . '/../../config/database.php';

class User
{
    /**
     * Instance PDO Database
     */
    protected PDO $db;

    /**
     * Nama tabel
     */
    protected string $table = 'users';

    /**
     * Daftar role yang diizinkan dalam sistem
     */
    public const ALLOWED_ROLES = ['admin', 'staff', 'customer'];

    /**
     * Constructor
     * Menggunakan koneksi PDO yang disuntikkan atau otomatis mengambil dari Database Singleton.
     *
     * @param PDO|null $db Instance PDO opsional
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Mengambil seluruh data pengguna dengan opsi filter role.
     *
     * @param string|null $role Filter berdasarkan role ('admin'|'staff'|'customer')
     * @return array Daftar seluruh user
     */
    public function getAll(?string $role = null): array
    {
        if ($role !== null && in_array($role, self::ALLOWED_ROLES, true)) {
            $stmt = $this->db->prepare("
                SELECT id, name, email, role, created_at 
                FROM {$this->table} 
                WHERE role = :role 
                ORDER BY id DESC
            ");
            $stmt->execute([':role' => $role]);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->query("
            SELECT id, name, email, role, created_at 
            FROM {$this->table} 
            ORDER BY id DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Mengambil data satu pengguna berdasarkan ID.
     *
     * @param int $id ID Pengguna
     * @return array|null Data pengguna atau null jika tidak ditemukan
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, email, role, created_at 
            FROM {$this->table} 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Mengambil data satu pengguna berdasarkan Email (termasuk password hash untuk login).
     *
     * @param string $email Alamat email pengguna
     * @return array|null Data pengguna lengkap atau null jika tidak ditemukan
     */
    public function getByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, email, password, role, created_at 
            FROM {$this->table} 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->execute([':email' => trim($email)]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Menambahkan data pengguna baru.
     *
     * @param array $data Array asosiatif ['name' => ..., 'email' => ..., 'password' => ..., 'role' => ...]
     * @return int ID pengguna yang baru dibuat
     * @throws InvalidArgumentException Jika validasi gagal
     * @throws RuntimeException Jika penyimpanan database gagal
     */
    public function create(array $data): int
    {
        // 1. Validasi Input
        $errors = $this->validateInput($data, true);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }

        // 2. Cek apakah email sudah terdaftar
        if ($this->getByEmail($data['email']) !== null) {
            throw new InvalidArgumentException("Email '{$data['email']}' sudah terdaftar.");
        }

        // 3. Hash password jika belum di-hash
        $password = $data['password'];
        $pwdInfo = password_get_info($password);
        if (empty($pwdInfo['algo'])) {
            $password = password_hash($password, PASSWORD_BCRYPT);
        }

        $role = $data['role'] ?? 'customer';

        // 4. Eksekusi query insert dengan prepared statement
        $sql = "INSERT INTO {$this->table} (name, email, password, role, created_at) 
                VALUES (:name, :email, :password, :role, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':name'     => trim($data['name']),
            ':email'    => trim(strtolower($data['email'])),
            ':password' => $password,
            ':role'     => $role,
        ]);

        if (!$success) {
            throw new RuntimeException("Gagal menambahkan pengguna baru ke database.");
        }

        return (int)$this->db->lastInsertId();
    }

    /**
     * Memperbarui data pengguna berdasarkan ID.
     *
     * @param int $id ID pengguna
     * @param array $data Data yang ingin diperbarui
     * @return bool True jika berhasil
     * @throws InvalidArgumentException Jika validasi input gagal
     */
    public function update(int $id, array $data): bool
    {
        // Pastikan user ada
        $existing = $this->getById($id);
        if (!$existing) {
            throw new InvalidArgumentException("Pengguna dengan ID {$id} tidak ditemukan.");
        }

        // Validasi input
        $errors = $this->validateInput($data, false);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }

        // Cek duplikasi email jika email diubah
        if (!empty($data['email']) && strtolower(trim($data['email'])) !== strtolower($existing['email'])) {
            $checkEmail = $this->getByEmail($data['email']);
            if ($checkEmail !== null && (int)$checkEmail['id'] !== $id) {
                throw new InvalidArgumentException("Email '{$data['email']}' sudah digunakan pengguna lain.");
            }
        }

        $fields = [];
        $params = [':id' => $id];

        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = trim($data['name']);
        }

        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = trim(strtolower($data['email']));
        }

        if (!empty($data['password'])) {
            $pwdInfo = password_get_info($data['password']);
            $hash = empty($pwdInfo['algo']) ? password_hash($data['password'], PASSWORD_BCRYPT) : $data['password'];
            $fields[] = "password = :password";
            $params[':password'] = $hash;
        }

        if (isset($data['role'])) {
            if (!in_array($data['role'], self::ALLOWED_ROLES, true)) {
                throw new InvalidArgumentException("Role '{$data['role']}' tidak valid.");
            }
            $fields[] = "role = :role";
            $params[':role'] = $data['role'];
        }

        if (empty($fields)) {
            return false; // Tidak ada data yang diubah
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Menghapus data pengguna berdasarkan ID.
     *
     * @param int $id ID pengguna
     * @return bool True jika berhasil
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Melakukan pencarian pengguna berdasarkan kata kunci (nama atau email).
     *
     * @param string $keyword Kata kunci pencarian
     * @return array Daftar pengguna yang sesuai
     */
    public function search(string $keyword): array
    {
        $searchTerm = '%' . trim($keyword) . '%';
        $stmt = $this->db->prepare("
            SELECT id, name, email, role, created_at 
            FROM {$this->table} 
            WHERE name LIKE :kw_name OR email LIKE :kw_email 
            ORDER BY id DESC
        ");
        $stmt->execute([
            ':kw_name'  => $searchTerm,
            ':kw_email' => $searchTerm,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Verifikasi kata sandi teks biasa dengan hash tersimpan.
     * Helper praktis untuk autentikasi Controller.
     *
     * @param string $plainPassword Password teks biasa
     * @param string $hash Hash password dari database
     * @return bool True jika cocok
     */
    public function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    /**
     * Validasi dasar data input pengguna.
     *
     * @param array $data Input data
     * @param bool $isCreate Apakah proses pembuatan akun baru (memerlukan field wajib)
     * @return array Kumpulan pesan error jika ada
     */
    protected function validateInput(array $data, bool $isCreate = true): array
    {
        $errors = [];

        // Validasi Nama
        if ($isCreate || isset($data['name'])) {
            if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
                $errors[] = 'Nama wajib diisi minimal 2 karakter.';
            }
        }

        // Validasi Email
        if ($isCreate || isset($data['email'])) {
            if (empty($data['email']) || !filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format alamat email tidak valid.';
            }
        }

        // Validasi Password
        if ($isCreate) {
            if (empty($data['password']) || strlen($data['password']) < 6) {
                $errors[] = 'Password wajib diisi minimal 6 karakter.';
            }
        } elseif (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                $errors[] = 'Password baru minimal 6 karakter.';
            }
        }

        // Validasi Role
        if (isset($data['role'])) {
            if (!in_array($data['role'], self::ALLOWED_ROLES, true)) {
                $errors[] = 'Role tidak valid. Pilihan: admin, staff, customer.';
            }
        }

        return $errors;
    }
}
