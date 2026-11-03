<?php
/**
 * ============================================================================
 * QueueBites – Your Table, Your Time
 * File: config/database.php
 * Deskripsi: Kelas manajemen koneksi database menggunakan PDO (Singleton Pattern).
 * ============================================================================
 */

class Database
{
    /**
     * Konfigurasi Database Default.
     * Menggunakan kredensial umum untuk development lokal (XAMPP).
     * Dapat dioverride secara fleksibel melalui Environment Variables (misal .env atau server config).
     */
    private static string $host     = 'localhost';
    private static string $dbName   = 'queuebites';
    private static string $username = 'root';
    private static string $password = ''; // Kosong untuk default XAMPP lokal
    private static int    $port     = 3306;
    private static string $charset  = 'utf8mb4';

    /**
     * Instance PDO tunggal untuk seluruh siklus request.
     */
    private static ?PDO $instance = null;

    /**
     * Private constructor untuk mencegah instansiasi langsung (Pola Singleton).
     */
    private function __construct()
    {
    }

    /**
     * Mencegah cloning objek PDO singleton.
     */
    private function __clone()
    {
    }

    /**
     * Mendapatkan koneksi database PDO tunggal.
     * Koneksi hanya dibuat satu kali (hemat memori dan efisien).
     *
     * @return PDO Objek koneksi PDO yang aktif.
     * @throws PDOException Jika koneksi gagal dibuat.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            // Membaca kredensial dari Environment Variable jika tersedia, jika tidak gunakan default lokal
            $host     = getenv('DB_HOST') !== false ? getenv('DB_HOST') : self::$host;
            $dbName   = getenv('DB_NAME') !== false ? getenv('DB_NAME') : self::$dbName;
            $username = getenv('DB_USER') !== false ? getenv('DB_USER') : self::$username;
            $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : self::$password;
            $port     = getenv('DB_PORT') !== false ? (int)getenv('DB_PORT') : self::$port;
            $charset  = self::$charset;

            // DSN (Data Source Name)
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

            // Opsi konfigurasi PDO
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Mode Exception saat error
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Return data dalam bentuk associative array
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Gunakan native prepared statements
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Log detail pesan error ke error log server tanpa membocorkan kredensial ke user
                error_log('[QueueBites Database Error] ' . $e->getMessage());

                // Lempar exception dengan pesan yang informatif namun aman
                throw new PDOException(
                    "Koneksi ke database '{$dbName}' gagal. Pastikan layanan MySQL aktif dan kredensial di config/database.php sesuai."
                );
            }
        }

        return self::$instance;
    }

    /**
     * Menutup koneksi database (berguna untuk testing atau garbage collection).
     *
     * @return void
     */
    public static function closeConnection(): void
    {
        self::$instance = null;
    }
}

