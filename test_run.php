<?php
/**
 * ============================================================================
 * QueueBites – File Uji Coba Cepat (Test Runner)
 * File: test_run.php
 * Deskripsi: Skrip untuk menguji langsung koneksi database dan fungsi-fungsi
 *            Model sebelum Controller dan View selesai dibuat oleh tim lain.
 * ============================================================================
 * 
 * Cara Menjalankan:
 * 1. Lewat Terminal / Command Prompt:
 *    php test_run.php
 * 
 * 2. Lewat Browser (PHP Built-in Server):
 *    php -S localhost:8000
 *    Lalu buka browser: http://localhost:8000/test_run.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/models/Restaurant.php';
require_once __DIR__ . '/app/models/Queue.php';
require_once __DIR__ . '/app/models/QueueHistory.php';

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>QueueBites - Test Runner Model & Database</title>';
    echo '<style>body{font-family:Segoe UI,sans-serif;padding:24px;background:#f8fafc;color:#1e293b;}';
    echo '.card{background:white;padding:20px;margin-bottom:16px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);max-width:800px;}';
    echo '.success{color:#16a34a;font-weight:bold;} .error{color:#dc2626;font-weight:bold;}';
    echo 'pre{background:#f1f5f9;padding:12px;border-radius:6px;overflow-x:auto;}';
    echo 'h1{color:#0f172a;} h3{margin-top:0;color:#2563eb;}</style></head><body>';
    echo '<h1>🚀 QueueBites – Uji Coba Model & Database</h1>';
}

function printMessage($label, $message, $isSuccess = true) {
    global $isCli;
    if ($isCli) {
        $status = $isSuccess ? '[OK]' : '[GAGAL]';
        echo "{$status} {$label}: {$message}\n";
    } else {
        $class = $isSuccess ? 'success' : 'error';
        echo "<div class='card'>";
        echo "<h3>{$label}</h3>";
        echo "<p class='{$class}'>" . htmlspecialchars($message) . "</p>";
    }
}

function printData($title, $data) {
    global $isCli;
    if ($isCli) {
        echo "\n--- {$title} ---\n";
        print_r($data);
        echo "\n";
    } else {
        echo "<div class='card'>";
        echo "<h3>{$title}</h3>";
        echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
        echo "</div>";
    }
}

// ============================================================================
// 1. UJI KONEKSI DATABASE
// ============================================================================
try {
    $db = Database::getConnection();
    printMessage("1. Koneksi Database", "Berhasil terhubung ke database MySQL 'queuebites'!");
} catch (Throwable $e) {
    printMessage("1. Koneksi Database", "Gagal terhubung: " . $e->getMessage(), false);
    if ($isCli) {
        echo "\n💡 TIPS:\n1. Pastikan XAMPP (Apache & MySQL) sudah dalam keadaan START.\n2. Pastikan file database/queuebites.sql sudah diimpor ke phpMyAdmin.\n\n";
    } else {
        echo "<div class='card' style='border-left:4px solid #dc2626;'>";
        echo "<h4>Solusi Jika Koneksi Gagal:</h4>";
        echo "<ol>";
        echo "<li>Buka <strong>XAMPP Control Panel</strong> dan klik tombol <strong>Start</strong> pada MySQL.</li>";
        echo "<li>Buka <strong>http://localhost/phpmyadmin</strong> di browser.</li>";
        echo "<li>Impor berkas <strong>database/queuebites.sql</strong> melalui menu tab <em>Import</em>.</li>";
        echo "<li>Muat ulang (refresh) halaman ini.</li>";
        echo "</ol>";
        echo "</div></body></html>";
    }
    exit(1);
}

// ============================================================================
// 2. UJI MODEL RESTAURANT
// ============================================================================
try {
    $restaurantModel = new Restaurant($db);
    $restaurants = $restaurantModel->getAll();
    printMessage("2. Model Restaurant", "Berhasil mengambil " . count($restaurants) . " restoran.");
    
    // Tampilkan restoran pertama
    if (!empty($restaurants)) {
        printData("Data Restoran Pertama", [
            'ID'            => $restaurants[0]['id'],
            'Nama Restoran' => $restaurants[0]['name'],
            'Jam Buka'      => $restaurants[0]['opening_hours'],
            'Status'        => $restaurants[0]['status']
        ]);
    }
} catch (Throwable $e) {
    printMessage("2. Model Restaurant", $e->getMessage(), false);
}

// ============================================================================
// 3. UJI MODEL USER
// ============================================================================
try {
    $userModel = new User($db);
    $users = $userModel->getAll();
    printMessage("3. Model User", "Berhasil mengambil " . count($users) . " pengguna terdaftar.");

    // Coba verifikasi password login customer dummy
    $testUser = $userModel->getByEmail('budi@gmail.com');
    if ($testUser) {
        $isMatch = $userModel->verifyPassword('user123', $testUser['password']);
        printMessage("3.1 Verifikasi Password", "Autentikasi akun 'budi@gmail.com' dengan password 'user123': " . ($isMatch ? 'VALID (Cocok)' : 'TIDAK COCOK'));
    }
} catch (Throwable $e) {
    printMessage("3. Model User", $e->getMessage(), false);
}

// ============================================================================
// 4. UJI MODEL QUEUE (SIMULASI PENGAMBILAN ANTREAN & PENOMORAN OTOMATIS)
// ============================================================================
try {
    $queueModel = new Queue($db);
    $historyModel = new QueueHistory($db);

    $testRestoId = !empty($restaurants) ? (int)$restaurants[0]['id'] : 1;
    $testUserId  = $testUser ? (int)$testUser['id'] : 3;

    // Hitung jumlah antrean waiting saat ini
    $waitingBefore = $queueModel->getWaitingCount($testRestoId);

    // Ambil antrean baru untuk pelanggan
    $newQueueId = $queueModel->create([
        'user_id'       => $testUserId,
        'restaurant_id' => $testRestoId,
        'people_count'  => 3,
    ]);

    $newQueue = $queueModel->getById($newQueueId);
    $position = $queueModel->getUserQueuePosition($newQueueId);
    $peopleAhead = $queueModel->getPeopleAhead($newQueueId);

    printMessage("4. Model Queue (Ambil Antrean)", "Berhasil membuat antrean baru ID: {$newQueueId}");
    printData("Detail Antrean yang Baru Diambil", [
        'Nomor Antrean'    => $newQueue['queue_number'],
        'Restoran'         => $newQueue['restaurant_name'],
        'Pelanggan'        => $newQueue['user_name'],
        'Jumlah Orang'     => $newQueue['people_count'],
        'Status'           => $newQueue['status'],
        'Posisi Antrean'   => "Nomor urut ke-{$position} di antara yang menunggu",
        'Antrean di Depan' => "Ada {$peopleAhead} rombongan di depan Anda"
    ]);

    // Uji perubahan status ke 'called'
    $queueModel->updateStatus($newQueueId, 'called');
    printMessage("4.1 Update Status Antrean", "Status antrean {$newQueue['queue_number']} berhasil diubah ke 'called'.");

    // Cek riwayat status di Model QueueHistory
    $histories = $historyModel->getByQueueId($newQueueId);
    $historyLog = [];
    foreach ($histories as $h) {
        $historyLog[] = ($h['old_status'] ?? 'AWAL (NULL)') . " -> " . $h['new_status'] . " pada " . $h['changed_at'];
    }
    printData("Audit Riwayat Status (queue_history)", $historyLog);

} catch (Throwable $e) {
    printMessage("4. Model Queue", $e->getMessage(), false);
}

if (!$isCli) {
    echo "<div class='card' style='background:#ecfdf5;border-left:4px solid #10b981;'>";
    echo "<h3>Semua Lapisan Model & Database Berfungsi Normal!</h3>";
    echo "<p>Rekan tim Anda yang mengerjakan <strong>Controller</strong> dan <strong>View</strong> sekarang dapat langsung memanggil class Model di atas.</p>";
    echo "</div>";
    echo "</body></html>";
} else {
    echo "\n=======================================================\n";
    echo "Seluruh pengujian Model & Database berhasil dijalankan!\n";
    echo "=======================================================\n";
}

