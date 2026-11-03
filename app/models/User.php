<?php
/**
 * ============================================================================
 * Mochi Mood / QueueBites – Model Pengguna
 * File: app/models/User.php
 * Deskripsi: Model untuk mengelola data tabel 'users' (Admin, Staff, Customer).
 * ============================================================================
 */

require_once __DIR__ . '/../../config/database.php';

class User
{
    protected PDO $db;
    protected string $table = 'users';

    public const ALLOWED_ROLES = ['admin', 'staff', 'customer'];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getAll(?string $role = null): array
    {
        if ($role !== null && in_array($role, self::ALLOWED_ROLES, true)) {
            $stmt = $this->db->prepare("
                SELECT id, name, email, phone, role, created_at 
                FROM {$this->table} 
                WHERE role = :role 
                ORDER BY id DESC
            ");
            $stmt->execute([':role' => $role]);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->query("
            SELECT id, name, email, phone, role, created_at 
            FROM {$this->table} 
            ORDER BY id DESC
        ");
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, role, created_at 
            FROM {$this->table} 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function getByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, password, role, created_at 
            FROM {$this->table} 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->execute([':email' => trim($email)]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $errors = $this->validateInput($data, true);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }

        if ($this->getByEmail($data['email']) !== null) {
            throw new InvalidArgumentException("Email '{$data['email']}' sudah terdaftar.");
        }

        $password = $data['password'];
        $pwdInfo = password_get_info($password);
        if (empty($pwdInfo['algo'])) {
            $password = password_hash($password, PASSWORD_BCRYPT);
        }

        $role  = $data['role'] ?? 'customer';
        $phone = !empty($data['phone']) ? trim($data['phone']) : null;

        $sql = "INSERT INTO {$this->table} (name, email, phone, password, role, created_at) 
                VALUES (:name, :email, :phone, :password, :role, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':name'     => trim($data['name']),
            ':email'    => trim(strtolower($data['email'])),
            ':phone'    => $phone,
            ':password' => $password,
            ':role'     => $role,
        ]);

        if (!$success) {
            throw new RuntimeException("Gagal menambahkan pengguna baru ke database.");
        }

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $existing = $this->getById($id);
        if (!$existing) {
            throw new InvalidArgumentException("Pengguna dengan ID {$id} tidak ditemukan.");
        }

        $errors = $this->validateInput($data, false);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }

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

        if (isset($data['phone'])) {
            $fields[] = "phone = :phone";
            $params[':phone'] = trim($data['phone']);
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
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function search(string $keyword): array
    {
        $searchTerm = '%' . trim($keyword) . '%';
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, role, created_at 
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

    public function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    protected function validateInput(array $data, bool $isCreate = true): array
    {
        $errors = [];

        if ($isCreate || isset($data['name'])) {
            if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
                $errors[] = 'Nama wajib diisi minimal 2 karakter.';
            }
        }

        if ($isCreate || isset($data['email'])) {
            if (empty($data['email']) || !filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format alamat email tidak valid.';
            }
        }

        if ($isCreate) {
            if (empty($data['password']) || strlen($data['password']) < 6) {
                $errors[] = 'Password wajib diisi minimal 6 karakter.';
            }
        } elseif (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                $errors[] = 'Password baru minimal 6 karakter.';
            }
        }

        if (isset($data['role'])) {
            if (!in_array($data['role'], self::ALLOWED_ROLES, true)) {
                $errors[] = 'Role tidak valid. Pilihan: admin, staff, customer.';
            }
        }

        return $errors;
    }
}
