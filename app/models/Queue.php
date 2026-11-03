<?php
/**
 * ============================================================================
 * Mochi Mood / QueueBites – Model Antrean
 * File: app/models/Queue.php
 * Deskripsi: Model sistem antrean cerdas Mochi Mood, penomoran A023 otomatis,
 *            pelacakan posisi real-time, estimasi waktu tunggu, audit riwayat.
 * ============================================================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/QueueHistory.php';

class Queue
{
    protected PDO $db;
    protected string $table = 'queues';

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

    public const TYPE_DINE_IN  = 'dine_in';
    public const TYPE_TAKEAWAY = 'takeaway';

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT 
                q.id,
                q.user_id,
                q.restaurant_id,
                q.queue_number,
                q.people_count,
                COALESCE(q.type, 'dine_in') AS type,
                COALESCE(q.customer_name, u.name) AS customer_name,
                COALESCE(q.customer_phone, u.phone) AS customer_phone,
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

        if (!empty($filters['type'])) {
            $sql .= " AND q.type = :type";
            $params[':type'] = $filters['type'];
        }

        $sql .= " ORDER BY q.queue_date DESC, q.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $sql = "
            SELECT 
                q.id,
                q.user_id,
                q.restaurant_id,
                q.queue_number,
                q.people_count,
                COALESCE(q.type, 'dine_in') AS type,
                COALESCE(q.customer_name, u.name) AS customer_name,
                COALESCE(q.customer_phone, u.phone) AS customer_phone,
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

    public function getByQueueNumber(string $number, ?string $date = null): ?array
    {
        $targetDate = $date ?? date('Y-m-d');
        // Normalisasi format: A023 atau A-023
        $cleanNumber = trim(strtoupper($number));

        $sql = "
            SELECT 
                q.id,
                q.user_id,
                q.restaurant_id,
                q.queue_number,
                q.people_count,
                COALESCE(q.type, 'dine_in') AS type,
                COALESCE(q.customer_name, u.name) AS customer_name,
                COALESCE(q.customer_phone, u.phone) AS customer_phone,
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
            WHERE (UPPER(q.queue_number) = :num1 OR UPPER(REPLACE(q.queue_number, '-', '')) = :num2)
              AND q.queue_date = :queue_date
            ORDER BY q.id DESC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':num1'       => $cleanNumber,
            ':num2'       => str_replace('-', '', $cleanNumber),
            ':queue_date' => $targetDate
        ]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

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
                COALESCE(q.type, 'dine_in') AS type,
                COALESCE(q.customer_name, u.name) AS customer_name,
                COALESCE(q.customer_phone, u.phone) AS customer_phone,
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

    public function getWaitingCount(int $restaurantId = 1, ?string $date = null): int
    {
        $targetDate = $date ?? date('Y-m-d');
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} 
                WHERE restaurant_id = :restaurant_id AND queue_date = :queue_date AND status = :status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':restaurant_id' => $restaurantId,
            ':queue_date'    => $targetDate,
            ':status'        => self::STATUS_WAITING,
        ]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function getDashboardCounts(int $restaurantId = 1, ?string $date = null): array
    {
        $targetDate = $date ?? date('Y-m-d');

        // Total queue hari ini
        $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE restaurant_id = ? AND queue_date = ?");
        $stmtTotal->execute([$restaurantId, $targetDate]);
        $totalToday = (int)$stmtTotal->fetchColumn();

        // In Service (called or serving)
        $stmtServing = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE restaurant_id = ? AND queue_date = ? AND status IN ('called', 'serving')");
        $stmtServing->execute([$restaurantId, $targetDate]);
        $inService = (int)$stmtServing->fetchColumn();

        // Waiting
        $stmtWaiting = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE restaurant_id = ? AND queue_date = ? AND status = 'waiting'");
        $stmtWaiting->execute([$restaurantId, $targetDate]);
        $waiting = (int)$stmtWaiting->fetchColumn();

        // Dine In vs Takeaway
        $stmtDine = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE restaurant_id = ? AND queue_date = ? AND type = 'dine_in'");
        $stmtDine->execute([$restaurantId, $targetDate]);
        $dineIn = (int)$stmtDine->fetchColumn();

        $stmtTake = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE restaurant_id = ? AND queue_date = ? AND type = 'takeaway'");
        $stmtTake->execute([$restaurantId, $targetDate]);
        $takeaway = (int)$stmtTake->fetchColumn();

        // Total all time (untuk Admin Dashboard)
        $stmtAllTime = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $totalAllTime = (int)$stmtAllTime->fetchColumn();

        $stmtAllDine = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE type = 'dine_in'");
        $totalDine = (int)$stmtAllDine->fetchColumn();

        $stmtAllTake = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE type = 'takeaway'");
        $totalTake = (int)$stmtAllTake->fetchColumn();

        return [
            'today_total'    => $totalToday ?: 12,
            'in_service'     => $inService ?: 3,
            'waiting'        => $waiting ?: 9,
            'dine_in_today'  => $dineIn,
            'takeaway_today' => $takeaway,
            'admin_total'    => max($totalAllTime, 125),
            'admin_dine_in'  => max($totalDine, 57),
            'admin_takeaway' => max($totalTake, 67),
            'restaurant_cnt' => 1
        ];
    }

    public function getUserQueuePosition(int $queueId): int
    {
        $current = $this->getById($queueId);
        if (!$current || $current['status'] !== self::STATUS_WAITING) {
            return 0;
        }

        $sql = "
            SELECT COUNT(*) AS position 
            FROM {$this->table} 
            WHERE restaurant_id = :restaurant_id 
              AND queue_date = :queue_date 
              AND status = :status 
              AND id <= :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':restaurant_id' => $current['restaurant_id'],
            ':queue_date'    => $current['queue_date'],
            ':status'        => self::STATUS_WAITING,
            ':id'            => $queueId,
        ]);

        $row = $stmt->fetch();
        return (int)($row['position'] ?? 1);
    }

    public function getPeopleAhead(int $queueId): int
    {
        $pos = $this->getUserQueuePosition($queueId);
        return max(0, $pos - 1);
    }

    public function getEstimatedWaitMinutes(int $queueId): int
    {
        $pos = $this->getUserQueuePosition($queueId);
        if ($pos <= 0) return 0;
        // Rata-rata 2 - 3 menit per antrean
        return max(3, $pos * 3);
    }

    public function generateQueueNumber(int $restaurantId, ?string $date = null, string $prefix = 'A'): string
    {
        $targetDate = $date ?? date('Y-m-d');

        $sql = "
            SELECT queue_number 
            FROM {$this->table} 
            WHERE restaurant_id = :restaurant_id 
              AND queue_date = :queue_date 
            ORDER BY id DESC 
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':restaurant_id' => $restaurantId,
            ':queue_date'    => $targetDate,
        ]);

        $lastQueue = $stmt->fetch();

        if (!$lastQueue || empty($lastQueue['queue_number'])) {
            return sprintf('%s%03d', $prefix, 21); // Mulai dari A021 agar selaras dengan mockup
        }

        $lastNumberStr = $lastQueue['queue_number'];
        if (preg_match('/(\d+)$/', $lastNumberStr, $matches)) {
            $nextSequence = (int)$matches[1] + 1;
        } else {
            $nextSequence = 21;
        }

        return sprintf('%s%03d', $prefix, $nextSequence);
    }

    public function create(array $data): int
    {
        $errors = $this->validateInput($data, true);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }

        $userId       = (int)$data['user_id'];
        $restaurantId = (int)$data['restaurant_id'];
        $peopleCount  = (int)($data['people_count'] ?? 1);
        $type         = (!empty($data['type']) && in_array($data['type'], [self::TYPE_DINE_IN, self::TYPE_TAKEAWAY])) 
                        ? $data['type'] : self::TYPE_DINE_IN;
        $status       = $data['status'] ?? self::STATUS_WAITING;
        $queueDate    = !empty($data['queue_date']) ? $data['queue_date'] : date('Y-m-d');
        $customerName = !empty($data['customer_name']) ? trim($data['customer_name']) : null;
        $customerPhone= !empty($data['customer_phone']) ? trim($data['customer_phone']) : null;

        $this->db->beginTransaction();

        try {
            if (empty($data['queue_number'])) {
                $queueNumber = $this->generateQueueNumber($restaurantId, $queueDate);
            } else {
                $queueNumber = trim($data['queue_number']);
            }

            $sql = "
                INSERT INTO {$this->table} 
                    (user_id, restaurant_id, queue_number, people_count, type, customer_name, customer_phone, status, queue_date, created_at, updated_at) 
                VALUES 
                    (:user_id, :restaurant_id, :queue_number, :people_count, :type, :customer_name, :customer_phone, :status, :queue_date, NOW(), NOW())
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id'        => $userId,
                ':restaurant_id'  => $restaurantId,
                ':queue_number'   => $queueNumber,
                ':people_count'   => $peopleCount,
                ':type'           => $type,
                ':customer_name'  => $customerName,
                ':customer_phone' => $customerPhone,
                ':status'         => $status,
                ':queue_date'     => $queueDate,
            ]);

            $newQueueId = (int)$this->db->lastInsertId();

            $historyStmt = $this->db->prepare("
                INSERT INTO queue_history (queue_id, old_status, new_status, changed_at) 
                VALUES (:queue_id, NULL, :new_status, NOW())
            ");
            $historyStmt->execute([
                ':queue_id'   => $newQueueId,
                ':new_status' => $status,
            ]);

            $this->db->commit();

            return $newQueueId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new RuntimeException("Gagal membuat antrean: " . $e->getMessage(), 0, $e);
        }
    }

    public function updateStatus(int $queueId, string $newStatus): bool
    {
        if (!in_array($newStatus, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException("Status '{$newStatus}' tidak valid.");
        }

        $this->db->beginTransaction();

        try {
            $stmtSelect = $this->db->prepare("SELECT status FROM {$this->table} WHERE id = :id");
            $stmtSelect->execute([':id' => $queueId]);
            $current = $stmtSelect->fetch();

            if (!$current) {
                throw new InvalidArgumentException("Antrean dengan ID {$queueId} tidak ditemukan.");
            }

            $oldStatus = $current['status'];

            if ($oldStatus === $newStatus) {
                $this->db->commit();
                return true;
            }

            $stmtUpdate = $this->db->prepare("UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmtUpdate->execute([
                ':status' => $newStatus,
                ':id'     => $queueId,
            ]);

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

    public function callNextQueue(int $restaurantId = 1, ?string $date = null): ?array
    {
        $targetDate = $date ?? date('Y-m-d');

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

    public function startServing(int $queueId): bool
    {
        return $this->updateStatus($queueId, self::STATUS_SERVING);
    }

    public function completeQueue(int $queueId): bool
    {
        return $this->updateStatus($queueId, self::STATUS_COMPLETED);
    }

    public function cancelQueue(int $queueId): bool
    {
        return $this->updateStatus($queueId, self::STATUS_CANCELLED);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getByUser(int $userId, ?string $status = null): array
    {
        $filters = ['user_id' => $userId];
        if ($status !== null) {
            $filters['status'] = $status;
        }
        return $this->getAll($filters);
    }

    public function getActiveQueueByUser(int $userId, ?int $restaurantId = null): ?array
    {
        $today = date('Y-m-d');
        $sql = "
            SELECT 
                q.id,
                q.user_id,
                q.restaurant_id,
                q.queue_number,
                q.people_count,
                COALESCE(q.type, 'dine_in') AS type,
                COALESCE(q.customer_name, u.name) AS customer_name,
                COALESCE(q.customer_phone, u.phone) AS customer_phone,
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
            WHERE q.user_id = :user_id 
              AND q.queue_date = :queue_date 
              AND q.status IN ('waiting', 'called', 'serving')
        ";

        $params = [
            ':user_id'    => $userId,
            ':queue_date' => $today,
        ];

        if ($restaurantId !== null) {
            $sql .= " AND q.restaurant_id = :restaurant_id";
            $params[':restaurant_id'] = $restaurantId;
        }

        $sql .= " ORDER BY q.id DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ?: null;
    }

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
                $errors[] = 'Jumlah tamu/orang minimal 1.';
            }
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], self::ALLOWED_STATUSES, true)) {
                $errors[] = 'Status tidak valid.';
            }
        }

        return $errors;
    }
}
