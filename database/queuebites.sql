-- ============================================================================
-- Mochi Mood / QueueBites – Smart Restaurant Queue Management System
-- File: database/queuebites.sql
-- Deskripsi: Skrip pembuatan database, struktur tabel, relasi foreign key,
--            indeks integritas data, dan data awal (seed data) Mochi Mood.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `queuebites`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `queuebites`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `queue_history`;
DROP TABLE IF EXISTS `queues`;
DROP TABLE IF EXISTS `restaurants`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- TABEL 1: users
-- ============================================================================
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT 'Nama lengkap pengguna',
    `email` VARCHAR(150) NOT NULL COMMENT 'Alamat email unik',
    `phone` VARCHAR(30) NULL COMMENT 'Nomor telepon pengguna',
    `password` VARCHAR(255) NOT NULL COMMENT 'Hash kata sandi bcrypt',
    `role` ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer' COMMENT 'Peran dalam sistem',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABEL 2: restaurants
-- ============================================================================
CREATE TABLE `restaurants` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL COMMENT 'Nama restoran / bakery',
    `description` TEXT NULL,
    `address` TEXT NOT NULL,
    `opening_hours` VARCHAR(100) NOT NULL DEFAULT '10:00 - 22:00',
    `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_restaurants_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABEL 3: queues
-- ============================================================================
CREATE TABLE `queues` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `restaurant_id` INT UNSIGNED NOT NULL,
    `queue_number` VARCHAR(20) NOT NULL COMMENT 'Nomor antrean (contoh: A023)',
    `people_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `type` ENUM('dine_in', 'takeaway') NOT NULL DEFAULT 'dine_in' COMMENT 'Jenis antrean',
    `customer_name` VARCHAR(100) NULL,
    `customer_phone` VARCHAR(50) NULL,
    `status` ENUM('waiting', 'called', 'serving', 'completed', 'cancelled') NOT NULL DEFAULT 'waiting',
    `queue_date` DATE NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_restaurant_date_number` (`restaurant_id`, `queue_date`, `queue_number`),
    INDEX `idx_queues_user_id` (`user_id`),
    INDEX `idx_queues_restaurant_date_status` (`restaurant_id`, `queue_date`, `status`),
    CONSTRAINT `fk_queues_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_queues_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABEL 4: queue_history
-- ============================================================================
CREATE TABLE `queue_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue_id` INT UNSIGNED NOT NULL,
    `old_status` ENUM('waiting', 'called', 'serving', 'completed', 'cancelled') NULL,
    `new_status` ENUM('waiting', 'called', 'serving', 'completed', 'cancelled') NOT NULL,
    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_queue_history_queue_id` (`queue_id`),
    CONSTRAINT `fk_queue_history_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABEL 5: announcements
-- ============================================================================
CREATE TABLE `announcements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABEL 6: menu_items (Filled Round Donuts)
-- ============================================================================
CREATE TABLE `menu_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `restaurant_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `price` INT UNSIGNED NOT NULL DEFAULT 0,
    `image_url` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_menu_items_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA
-- ============================================================================
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator Mochi Mood', 'admin@mochimood.com', '081234567890', '$2y$10$9mwUK1jeMjhVOqx8sWRbYOizluKDUJ2oOV/zl9aE9W0fkySC1yGve', 'admin', NOW()),
(2, 'Staff Sambal & Mochi', 'staff@mochimood.com', '081298765432', '$2y$10$CII.aNCCtWOvC6pea6Q3O.G0pdoGh9DekQldCw/ix0yhPhFfxmgrS', 'staff', NOW()),
(3, 'Nabil A.', 'nabil@gmail.com', '081345678912', '$2y$10$J2WoosMZSHTyjTzr4x83feg8tUT0sve3Z4.opdB7mZoTJerF.qMzi', 'customer', NOW()),
(4, 'Budi S.', 'budi@gmail.com', '081567891234', '$2y$10$J2WoosMZSHTyjTzr4x83feg8tUT0sve3Z4.opdB7mZoTJerF.qMzi', 'customer', NOW()),
(5, 'Dina P.', 'dina@gmail.com', '081789123456', '$2y$10$J2WoosMZSHTyjTzr4x83feg8tUT0sve3Z4.opdB7mZoTJerF.qMzi', 'customer', NOW());

INSERT INTO `restaurants` (`id`, `name`, `description`, `address`, `opening_hours`, `status`, `created_at`) VALUES
(1, 'Mochi Mood Central', 'Premium mochi donuts, always fresh, now with our smart queue system!', 'Jl. Sweet Happiness No. 1', '10:00 - 22:00', 'open', NOW());

INSERT INTO `queues` (`id`, `user_id`, `restaurant_id`, `queue_number`, `people_count`, `type`, `customer_name`, `customer_phone`, `status`, `queue_date`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'A022', 2, 'dine_in', 'Nabil A.', '081345678912', 'waiting', CURDATE(), DATE_SUB(NOW(), INTERVAL 30 MINUTE), NOW()),
(2, 3, 1, 'A023', 2, 'dine_in', 'Khansa (You)', '081233445566', 'waiting', CURDATE(), DATE_SUB(NOW(), INTERVAL 20 MINUTE), NOW()),
(3, 4, 1, 'A024', 1, 'takeaway', 'Budi S.', '081567891234', 'waiting', CURDATE(), DATE_SUB(NOW(), INTERVAL 15 MINUTE), NOW()),
(4, 5, 1, 'A025', 4, 'dine_in', 'Dina P.', '081789123456', 'waiting', CURDATE(), DATE_SUB(NOW(), INTERVAL 10 MINUTE), NOW()),
(5, 1, 1, 'A019', 2, 'dine_in', 'Sarah K.', '081987654321', 'serving', CURDATE(), DATE_SUB(NOW(), INTERVAL 45 MINUTE), NOW()),
(6, 1, 1, 'A020', 3, 'takeaway', 'Rizky M.', '081987654322', 'serving', CURDATE(), DATE_SUB(NOW(), INTERVAL 40 MINUTE), NOW()),
(7, 1, 1, 'A021', 1, 'dine_in', 'Dewi L.', '081987654323', 'serving', CURDATE(), DATE_SUB(NOW(), INTERVAL 35 MINUTE), NOW()),
(8, 1, 1, 'A018', 2, 'dine_in', 'Alex T.', '081987654324', 'completed', CURDATE(), DATE_SUB(NOW(), INTERVAL 60 MINUTE), NOW());

INSERT INTO `queue_history` (`id`, `queue_id`, `old_status`, `new_status`, `changed_at`) VALUES
(1, 1, NULL, 'waiting', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 2, NULL, 'waiting', DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
(3, 3, NULL, 'waiting', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
(4, 4, NULL, 'waiting', DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(5, 5, 'waiting', 'serving', DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
(6, 6, 'waiting', 'serving', DATE_SUB(NOW(), INTERVAL 8 MINUTE)),
(7, 7, 'waiting', 'serving', DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
(8, 8, 'serving', 'completed', DATE_SUB(NOW(), INTERVAL 20 MINUTE));

INSERT INTO `announcements` (`id`, `title`, `message`, `is_active`, `created_at`) VALUES
(1, 'Promo Grand Opening', 'Nikmati diskon 20% untuk paket Mochi Mood Delight khusus hari ini! Pesan antrean Anda sekarang.', 1, NOW());

INSERT INTO `menu_items` (`id`, `restaurant_id`, `name`, `description`, `price`, `image_url`, `created_at`) VALUES
(1, 1, 'Matcha Crunch', 'Donut bulat empuk tanpa lubang dengan lelehan krim Uji Matcha murni, dilapisi glaze matcha harum dan taburan renyah crushed pistachios & cookie crumbs.', 18000, 'images/donuts/matcha-crunch.jpg', NOW()),
(2, 1, 'Choco Bliss', 'Donut bulat empuk berisikan dark chocolate ganache Belgia lumer, dilapisi glaze cokelat mengkilap dan taburan crispy chocolate pearls & cocoa nibs.', 18000, 'images/donuts/choco-bliss.jpg', NOW()),
(3, 1, 'Strawberry Cloud', 'Donut bulat lembut berisikan custard vanila lembut, dibalut pastel pink strawberry cream glaze dan taburan freeze-dried strawberry crumbles renyah.', 20000, 'images/donuts/strawberry-cloud.jpg', NOW()),
(4, 1, 'Lotus Dream', 'Donut bulat dengan lelehan selai Lotus Biscoff speculoos karamel hangat, topping biscoff cream dan remahan biskuit renyah.', 20000, 'images/donuts/lotus-dream.jpg', NOW()),
(5, 1, 'Vanilla Bean Diplomat', 'Donut bomboloni bulat klasik ditaburi gula halus salju, dengan isian melimpah krim custard diplomat vanila Madagaskar asli.', 16000, 'images/donuts/vanilla-diplomat.jpg', NOW()),
(6, 1, 'Tiramisu Mascarpone', 'Donut bulat dengan isian krim mascarpone Italia dan espresso artisan, ditaburi bubuk cocoa pekat dan hiasan cokelat biji kopi.', 22000, 'images/donuts/tiramisu-mascarpone.jpg', NOW());
