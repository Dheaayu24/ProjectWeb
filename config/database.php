<?php
/**
 * ============================================================================
 * Mochi Mood / QueueBites – Smart Database Connection
 * File: config/database.php
 * Deskripsi: Kelas manajemen koneksi database PDO (Singleton Pattern).
 *            Mendukung MySQL (Primary) dengan fallback otomatis ke SQLite
 *            sehingga aplikasi dapat langsung berjalan tanpa konfigurasi rumit.
 * ============================================================================
 */

class Database
{
    private static string $host     = 'localhost';
    private static string $dbName   = 'queuebites';
    private static string $username = 'root';
    private static string $password = '';
    private static int    $port     = 3306;
    private static string $charset  = 'utf8mb4';

    private static ?PDO $instance = null;
    private static string $driverUsed = 'mysql';

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host     = getenv('DB_HOST') !== false ? getenv('DB_HOST') : self::$host;
            $dbName   = getenv('DB_NAME') !== false ? getenv('DB_NAME') : self::$dbName;
            $username = getenv('DB_USER') !== false ? getenv('DB_USER') : self::$username;
            $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : self::$password;
            $port     = getenv('DB_PORT') !== false ? (int)getenv('DB_PORT') : self::$port;
            $charset  = self::$charset;

            // 1. Coba koneksi MySQL terlebih dahulu
            $mysqlDsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($mysqlDsn, $username, $password, $options);
                self::$driverUsed = 'mysql';
                self::ensureSchemaMysql(self::$instance);
            } catch (Throwable $e) {
                // 2. Jika MySQL tidak dapat dihubungi (misal: MySQL belum START di XAMPP / sandbox environment),
                // fallback otomatis ke SQLite lokal agar aplikasi selalu dapat diakses seketika!
                error_log('[Database Fallback] MySQL unavailable (' . $e->getMessage() . '). Switching to SQLite fallback.');
                
                $sqliteDir = dirname(__DIR__) . '/database';
                if (!is_dir($sqliteDir)) {
                    @mkdir($sqliteDir, 0777, true);
                }
                $sqlitePath = $sqliteDir . '/queuebites.sqlite';
                $sqliteDsn = "sqlite:" . $sqlitePath;

                try {
                    self::$instance = new PDO($sqliteDsn, null, null, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    // Registrasi fungsi MySQL agar query berjalan 100% kompatibel di SQLite
                    self::$instance->sqliteCreateFunction('NOW', function() { return date('Y-m-d H:i:s'); });
                    self::$instance->sqliteCreateFunction('CURDATE', function() { return date('Y-m-d'); });
                    self::$instance->sqliteCreateFunction('curdate', function() { return date('Y-m-d'); });
                    self::$driverUsed = 'sqlite';
                    self::ensureSchemaSqlite(self::$instance);
                } catch (Throwable $sqliteErr) {
                    throw new PDOException("Gagal menginisialisasi database: " . $sqliteErr->getMessage());
                }
            }
        }

        return self::$instance;
    }

    public static function getDriver(): string
    {
        return self::$driverUsed;
    }

    public static function closeConnection(): void
    {
        self::$instance = null;
    }

    /**
     * Memastikan tabel dan kolom pendukung Mochi Mood tersedia di MySQL
     */
    private static function ensureSchemaMysql(PDO $pdo): void
    {
        try {
            // Tambahkan kolom jika belum ada (backward-compatible)
            $cols = $pdo->query("SHOW COLUMNS FROM `queues` LIKE 'type'")->fetchAll();
            if (empty($cols)) {
                $pdo->exec("ALTER TABLE `queues` ADD COLUMN `type` ENUM('dine_in','takeaway') NOT NULL DEFAULT 'dine_in' AFTER `people_count`");
            }
            $colsName = $pdo->query("SHOW COLUMNS FROM `queues` LIKE 'customer_name'")->fetchAll();
            if (empty($colsName)) {
                $pdo->exec("ALTER TABLE `queues` ADD COLUMN `customer_name` VARCHAR(100) NULL AFTER `type`");
                $pdo->exec("ALTER TABLE `queues` ADD COLUMN `customer_phone` VARCHAR(50) NULL AFTER `customer_name`");
            }
            $colsPhone = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'phone'")->fetchAll();
            if (empty($colsPhone)) {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(30) NULL AFTER `email`");
            }
            // Buat tabel pengumuman jika belum ada
            $pdo->exec("CREATE TABLE IF NOT EXISTS `announcements` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `message` TEXT NOT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Pastikan Mochi Mood Central ada
            $checkResto = $pdo->query("SELECT id FROM restaurants WHERE id = 1")->fetch();
            if ($checkResto) {
                $pdo->exec("UPDATE restaurants SET name = 'Mochi Mood Central', address = 'Jl. Sweet Happiness No. 1' WHERE id = 1 AND name NOT LIKE '%Mochi%'");
            } else {
                $pdo->exec("INSERT INTO restaurants (id, name, description, address, opening_hours, status, created_at) 
                    VALUES (1, 'Mochi Mood Central', 'Premium mochi donuts, always fresh!', 'Jl. Sweet Happiness No. 1', '10:00 - 22:00', 'open', NOW())");
            }
        } catch (Throwable $t) {
            // Abaikan kesalahan penyesuaian skema jika tabel belum diimpor manual
        }
    }

    /**
     * Memastikan schema & data awal tersedia untuk SQLite
     */
    private static function ensureSchemaSqlite(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            phone TEXT,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'customer',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS restaurants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            address TEXT NOT NULL,
            opening_hours TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'open',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS queues (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            restaurant_id INTEGER NOT NULL,
            queue_number TEXT NOT NULL,
            people_count INTEGER NOT NULL DEFAULT 1,
            type TEXT NOT NULL DEFAULT 'dine_in',
            customer_name TEXT,
            customer_phone TEXT,
            status TEXT NOT NULL DEFAULT 'waiting',
            queue_date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS queue_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            queue_id INTEGER NOT NULL,
            old_status TEXT,
            new_status TEXT NOT NULL,
            changed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Cek data restoran awal
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM restaurants");
        $restoCount = (int)$stmt->fetchColumn();
        if ($restoCount < 2) {
            $pdo->exec("INSERT OR REPLACE INTO restaurants (id, name, description, address, opening_hours, status, created_at) VALUES 
                (1, 'Warung Sambal Bakar Juara', 'Aneka olahan lauk bakar di atas cobek tanah liat dengan sambal pedas membakar lidah.', 'Jl. Kaliurang KM 5 No. 12, Sleman, Yogyakarta', '10:00 - 22:00', 'open', datetime('now')),
                (2, 'Sushi Tei Pavilion', 'Authentic Japanese cuisine dengan conveyor belt sushi dan sashimi segar setiap hari.', 'Mall Pavilion Lantai 2, Jl. Sudirman No. 45, Jakarta Selatan', '11:00 - 21:30', 'open', datetime('now')),
                (3, 'Kopi Kenangan Senja & Eatery', 'Tempat nongkrong nyaman dengan sajian kopi spesial dan aneka hidangan nusantara.', 'Jl. Riau No. 88, Bandung', '08:00 - 23:00', 'open', datetime('now')),
                (4, 'Mochi Mood Bakery', 'Premium artisanal mochi donuts & crafted specialty coffee, fresh baked every morning.', 'Jl. Sweet Happiness No. 1, Yogyakarta', '09:00 - 21:00', 'open', datetime('now')),
                (5, 'Steakhouse 99 (Sedang Renovasi)', 'Pilihan steak daging impor premium dengan saus racikan chef berpengalaman.', 'Jl. Diponegoro No. 10, Surabaya', '12:00 - 21:00', 'closed', datetime('now'))");
        }

        // Cek data users
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
        $userCount = (int)$stmt->fetchColumn();
        if ($userCount === 0) {
            $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
            $staffPass = password_hash('staff123', PASSWORD_BCRYPT);
            $userPass  = password_hash('user123', PASSWORD_BCRYPT);

            $stmtUser = $pdo->prepare("INSERT INTO users (id, name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))");
            $stmtUser->execute([1, 'Administrator Mochi Mood', 'admin@mochimood.com', '081234567890', $adminPass, 'admin']);
            $stmtUser->execute([2, 'Staff Sambal & Mochi', 'staff@mochimood.com', '081298765432', $staffPass, 'staff']);
            $stmtUser->execute([3, 'Nabil A.', 'nabil@gmail.com', '081345678912', $userPass, 'customer']);
            $stmtUser->execute([4, 'Budi S.', 'budi@gmail.com', '081567891234', $userPass, 'customer']);
            $stmtUser->execute([5, 'Dina P.', 'dina@gmail.com', '081789123456', $userPass, 'customer']);
        }

        // Cek data antrean awal (sesuai UI Mockup: A022, A023, A024, A025)
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM queues");
        $queueCount = (int)$stmt->fetchColumn();
        $today = date('Y-m-d');

        if ($queueCount === 0) {
            $stmtQ = $pdo->prepare("INSERT INTO queues (id, user_id, restaurant_id, queue_number, people_count, type, customer_name, customer_phone, status, queue_date, created_at, updated_at) 
                VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, datetime('now', ?), datetime('now'))");
            
            // A022 (Waiting) - Nabil A., Dine-In, 2 People
            $stmtQ->execute([1, 3, 'A022', 2, 'dine_in', 'Nabil A.', '081345678912', 'waiting', $today, '-30 minutes']);
            // A023 (Waiting - UI Hero Queue) - Dine-In, 2 People
            $stmtQ->execute([2, 3, 'A023', 2, 'dine_in', 'Khansa (You)', '081233445566', 'waiting', $today, '-20 minutes']);
            // A024 (Waiting) - Budi S., Takeaway, 1 People
            $stmtQ->execute([3, 4, 'A024', 1, 'takeaway', 'Budi S.', '081567891234', 'waiting', $today, '-15 minutes']);
            // A025 (Waiting) - Dina P., Dine-In, 4 People
            $stmtQ->execute([4, 5, 'A025', 4, 'dine_in', 'Dina P.', '081789123456', 'waiting', $today, '-10 minutes']);
            // Beberapa antrean yang sedang serving / completed agar statistik sinkron
            $stmtQ->execute([5, 1, 'A019', 2, 'dine_in', 'Sarah K.', '081987654321', 'serving', $today, '-45 minutes']);
            $stmtQ->execute([6, 1, 'A020', 3, 'takeaway', 'Rizky M.', '081987654322', 'serving', $today, '-40 minutes']);
            $stmtQ->execute([7, 1, 'A021', 1, 'dine_in', 'Dewi L.', '081987654323', 'serving', $today, '-35 minutes']);
            $stmtQ->execute([8, 1, 'A018', 2, 'dine_in', 'Alex T.', '081987654324', 'completed', $today, '-60 minutes']);
        }

        // Pengumuman default
        $stmtAnn = $pdo->query("SELECT COUNT(*) as cnt FROM announcements");
        if ((int)$stmtAnn->fetchColumn() === 0) {
            $pdo->exec("INSERT INTO announcements (title, message, is_active, created_at) VALUES 
                ('Promo Grand Opening', 'Nikmati diskon 20% untuk paket Mochi Mood Delight khusus hari ini! Pesan antrean Anda sekarang.', 1, datetime('now'))");
        }
    }
}
