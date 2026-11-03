-- ============================================================================
-- QueueBites – Your Table, Your Time
-- File: database/queuebites.sql
-- Deskripsi: Skrip pembuatan database, struktur tabel, relasi foreign key,
--            indeks integritas data, dan data awal (seed data) untuk QueueBites.
-- ============================================================================

-- 1. Buat Database jika belum ada
CREATE DATABASE IF NOT EXISTS `queuebites`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `queuebites`;

-- Nonaktifkan pemeriksaan foreign key sementara untuk memudahkan reset tabel
SET FOREIGN_KEY_CHECKS = 0;

-- 2. Hapus tabel lama jika sudah ada (urutan child ke parent)
DROP TABLE IF EXISTS `queue_history`;
DROP TABLE IF EXISTS `queues`;
DROP TABLE IF EXISTS `restaurants`;
DROP TABLE IF EXISTS `users`;

-- Aktifkan kembali pemeriksaan foreign key
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- TABEL 1: users
-- Menyimpan data akun pengguna (Admin, Staff Restoran, dan Customer)
-- ============================================================================
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT 'Nama lengkap pengguna',
    `email` VARCHAR(150) NOT NULL COMMENT 'Alamat email unik untuk autentikasi',
    `password` VARCHAR(255) NOT NULL COMMENT 'Hash kata sandi bcrypt',
    `role` ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer' COMMENT 'Peran pengguna dalam sistem',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu registrasi akun',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel data pengguna';

-- ============================================================================
-- TABEL 2: restaurants
-- Menyimpan data profil restoran yang menggunakan sistem antrean QueueBites
-- ============================================================================
CREATE TABLE `restaurants` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL COMMENT 'Nama restoran',
    `description` TEXT NULL COMMENT 'Deskripsi restoran atau menu unggulan',
    `address` TEXT NOT NULL COMMENT 'Alamat lengkap lokasi restoran',
    `opening_hours` VARCHAR(100) NOT NULL COMMENT 'Jam operasional (contoh: 10:00 - 22:00)',
    `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open' COMMENT 'Status operasional penerimaan antrean',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu data restoran didaftarkan',
    PRIMARY KEY (`id`),
    INDEX `idx_restaurants_status` (`status`),
    INDEX `idx_restaurants_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel profil restoran';

-- ============================================================================
-- TABEL 3: queues
-- Menyimpan transaksi antrean pelanggan di setiap restoran
-- ============================================================================
CREATE TABLE `queues` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'ID pelanggan pembuat antrean (relasi ke users.id)',
    `restaurant_id` INT UNSIGNED NOT NULL COMMENT 'ID restoran yang dituju (relasi ke restaurants.id)',
    `queue_number` VARCHAR(20) NOT NULL COMMENT 'Nomor urut antrean harian (contoh: A-001)',
    `people_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Jumlah orang/tamu dalam satu rombongan antrean',
    `status` ENUM('waiting', 'called', 'serving', 'completed', 'cancelled') NOT NULL DEFAULT 'waiting' COMMENT 'Status antrean pelanggan',
    `queue_date` DATE NOT NULL COMMENT 'Tanggal antrean diambil untuk menjaga keunikan harian',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu nomor antrean diambil',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu terakhir status/data diperbarui',
    PRIMARY KEY (`id`),
    -- Aturan Unik: Nomor antrean tidak boleh ganda pada restoran dan tanggal yang sama
    UNIQUE KEY `uq_restaurant_date_number` (`restaurant_id`, `queue_date`, `queue_number`),
    INDEX `idx_queues_user_id` (`user_id`),
    INDEX `idx_queues_restaurant_date_status` (`restaurant_id`, `queue_date`, `status`),
    CONSTRAINT `fk_queues_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_queues_restaurant`
        FOREIGN KEY (`restaurant_id`)
        REFERENCES `restaurants` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel transaksi antrean';

-- ============================================================================
-- TABEL 4: queue_history
-- Mencatat jejak audit/riwayat setiap perubahan status antrean
-- ============================================================================
CREATE TABLE `queue_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue_id` INT UNSIGNED NOT NULL COMMENT 'ID antrean yang statusnya berubah (relasi ke queues.id)',
    `old_status` ENUM('waiting', 'called', 'serving', 'completed', 'cancelled') NULL COMMENT 'Status sebelum perubahan (NULL jika pertama kali dibuat)',
    `new_status` ENUM('waiting', 'called', 'serving', 'completed', 'cancelled') NOT NULL COMMENT 'Status sesudah perubahan',
    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu terjadinya perubahan status',
    PRIMARY KEY (`id`),
    INDEX `idx_queue_history_queue_id` (`queue_id`),
    INDEX `idx_queue_history_changed_at` (`changed_at`),
    CONSTRAINT `fk_queue_history_queue`
        FOREIGN KEY (`queue_id`)
        REFERENCES `queues` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel riwayat perubahan status antrean';

-- ============================================================================
-- DATA AWAL (SEED DATA / DUMMY DATA UNTUK PENGUJIAN)
-- ============================================================================

-- Data Pengguna:
-- Password admin: admin123
-- Password staff: staff123
-- Password user: user123
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator QueueBites', 'admin@queuebites.com', '$2y$10$9mwUK1jeMjhVOqx8sWRbYOizluKDUJ2oOV/zl9aE9W0fkySC1yGve', 'admin', NOW()),
(2, 'Staff Sambal Bakar', 'staff.sambal@queuebites.com', '$2y$10$CII.aNCCtWOvC6pea6Q3O.G0pdoGh9DekQldCw/ix0yhPhFfxmgrS', 'staff', NOW()),
(3, 'Budi Santoso', 'budi@gmail.com', '$2y$10$J2WoosMZSHTyjTzr4x83feg8tUT0sve3Z4.opdB7mZoTJerF.qMzi', 'customer', NOW()),
(4, 'Siti Rahma', 'siti@gmail.com', '$2y$10$J2WoosMZSHTyjTzr4x83feg8tUT0sve3Z4.opdB7mZoTJerF.qMzi', 'customer', NOW()),
(5, 'Ahmad Fauzi', 'ahmad@gmail.com', '$2y$10$J2WoosMZSHTyjTzr4x83feg8tUT0sve3Z4.opdB7mZoTJerF.qMzi', 'customer', NOW());

-- Data Restoran
INSERT INTO `restaurants` (`id`, `name`, `description`, `address`, `opening_hours`, `status`, `created_at`) VALUES
(1, 'Warung Sambal Bakar Juara', 'Aneka olahan lauk bakar di atas cobek tanah liat dengan sambal pedas membakar lidah.', 'Jl. Kaliurang KM 5 No. 12, Sleman, Yogyakarta', '10:00 - 22:00', 'open', NOW()),
(2, 'Sushi Tei Pavilion', 'Authentic Japanese cuisine dengan conveyor belt sushi dan sashimi segar setiap hari.', 'Mall Pavilion Lantai 2, Jl. Sudirman No. 45, Jakarta Selatan', '11:00 - 21:30', 'open', NOW()),
(3, 'Kopi Kenangan Senja & Eatery', 'Tempat nongkrong nyaman dengan sajian kopi spesial dan aneka hidangan nusantara.', 'Jl. Riau No. 88, Bandung', '08:00 - 23:00', 'open', NOW()),
(4, 'Steakhouse 99 (Sedang Renovasi)', 'Pilihan steak daging impor premium dengan saus racikan chef berpengalaman.', 'Jl. Diponegoro No. 10, Surabaya', '12:00 - 21:00', 'closed', NOW());

-- Data Antrean Hari Ini untuk Restoran 1 (Sambal Bakar Juara)
-- Menggunakan CURDATE() agar selalu relevan saat file diimpor kapan saja
INSERT INTO `queues` (`id`, `user_id`, `restaurant_id`, `queue_number`, `people_count`, `status`, `queue_date`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'A-001', 2, 'completed', CURDATE(), DATE_SUB(NOW(), INTERVAL 45 MINUTE), DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
(2, 4, 1, 'A-002', 4, 'serving', CURDATE(), DATE_SUB(NOW(), INTERVAL 30 MINUTE), DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
(3, 5, 1, 'A-003', 2, 'waiting', CURDATE(), DATE_SUB(NOW(), INTERVAL 10 MINUTE), DATE_SUB(NOW(), INTERVAL 10 MINUTE));

-- Data Riwayat Perubahan Status Antrean
INSERT INTO `queue_history` (`id`, `queue_id`, `old_status`, `new_status`, `changed_at`) VALUES
-- Riwayat Antrean 1 (completed)
(1, 1, NULL, 'waiting', DATE_SUB(NOW(), INTERVAL 45 MINUTE)),
(2, 1, 'waiting', 'called', DATE_SUB(NOW(), INTERVAL 35 MINUTE)),
(3, 1, 'called', 'serving', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(4, 1, 'serving', 'completed', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),

-- Riwayat Antrean 2 (serving)
(5, 2, NULL, 'waiting', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(6, 2, 'waiting', 'called', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
(7, 2, 'called', 'serving', DATE_SUB(NOW(), INTERVAL 5 MINUTE)),

-- Riwayat Antrean 3 (waiting)
(8, 3, NULL, 'waiting', DATE_SUB(NOW(), INTERVAL 10 MINUTE));

