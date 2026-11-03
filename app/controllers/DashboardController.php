<?php
/**
 * Controller: DashboardController (Fokus Mochi Mood)
 * File: app/controllers/DashboardController.php
 */

require_once __DIR__ . '/../models/Queue.php';
require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../../config/database.php';

class DashboardController
{
    public function staff(): void
    {
        Auth::start();
        $user = Auth::user();
        if (!$user) {
            Auth::login([
                'id'    => 2,
                'name'  => 'Petugas Konter Mochi',
                'email' => 'staff@mochimood.com',
                'phone' => '081298765432',
                'role'  => 'staff'
            ]);
            $user = Auth::user();
        }

        $queueModel = new Queue();
        $restoModel = new Restaurant();

        $queues = $queueModel->getByRestaurant(1);
        $summary = $queueModel->getDashboardCounts(1);
        $restaurant = $restoModel->getById(1);

        include __DIR__ . '/../views/dashboard/staff.php';
    }

    public function admin(): void
    {
        Auth::start();
        $user = Auth::user();
        if (!$user || $user['role'] !== 'admin') {
            Auth::login([
                'id'    => 1,
                'name'  => 'Admin Mochi Mood',
                'email' => 'admin@mochimood.com',
                'phone' => '081234567890',
                'role'  => 'admin'
            ]);
            $user = Auth::user();
        }

        $restoModel = new Restaurant();
        $queueModel = new Queue();

        $restaurant = $restoModel->getById(1);
        $queues = $queueModel->getByRestaurant(1);
        $summary = $queueModel->getDashboardCounts(1);

        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM menu_items WHERE restaurant_id = 1 ORDER BY id DESC");
        $donuts = $stmt ? $stmt->fetchAll() : [];

        $completed = 0;
        $cancelled = 0;
        foreach ($queues as $q) {
            if ($q['status'] === 'completed') $completed++;
            if ($q['status'] === 'cancelled') $cancelled++;
        }
        $summary['completed_count'] = $completed;
        $summary['cancelled_count'] = $cancelled;

        $success = $_GET['msg'] ?? null;
        $error   = $_GET['err'] ?? null;

        include __DIR__ . '/../views/dashboard/admin.php';
    }

    public function apiAction(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action  = $input['action'] ?? '';
        $queueId = (int)($input['queue_id'] ?? 0);

        try {
            $queueModel = new Queue();

            if ($action === 'call') {
                $queueModel->updateStatus($queueId, Queue::STATUS_CALLED);
                echo json_encode(['success' => true, 'message' => 'Antrean berhasil dipanggil.']);
            } elseif ($action === 'serve') {
                $queueModel->updateStatus($queueId, Queue::STATUS_SERVING);
                echo json_encode(['success' => true, 'message' => 'Mulai melayani pelanggan.']);
            } elseif ($action === 'complete') {
                $queueModel->updateStatus($queueId, Queue::STATUS_COMPLETED);
                echo json_encode(['success' => true, 'message' => 'Antrean telah diselesaikan.']);
            } elseif ($action === 'call_next') {
                $next = $queueModel->callNextQueue(1);
                if ($next) {
                    echo json_encode(['success' => true, 'message' => 'Memanggil nomor ' . $next['queue_number'], 'queue' => $next]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Tidak ada antrean yang sedang menunggu.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
            }
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function storeUpdate(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $restoModel = new Restaurant();
                $restoModel->update(1, [
                    'name'          => trim($_POST['name']),
                    'address'       => trim($_POST['address']),
                    'opening_hours' => trim($_POST['opening_hours']),
                    'status'        => $_POST['status'] ?? 'open'
                ]);
                header('Location: index.php?url=dashboard/admin&msg=' . urlencode('Operasional Mochi Mood berhasil diperbarui!'));
                exit;
            } catch (Throwable $e) {
                header('Location: index.php?url=dashboard/admin&err=' . urlencode($e->getMessage()));
                exit;
            }
        }
    }

    public function donutAdd(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $name  = trim($_POST['donut_name']);
            $price = (int)$_POST['donut_price'];
            $desc  = trim($_POST['donut_desc']);
            $img   = trim($_POST['donut_image']);

            if (!empty($name) && $price > 0) {
                $db = Database::getConnection();
                $stmt = $db->prepare("INSERT INTO menu_items (restaurant_id, name, description, price, image_url) VALUES (1, ?, ?, ?, ?)");
                $stmt->execute([$name, $desc, $price, $img]);
                header('Location: index.php?url=dashboard/admin&msg=' . urlencode('Menu donut baru berhasil ditambahkan!'));
                exit;
            }
        }
        header('Location: index.php?url=dashboard/admin');
        exit;
    }

    public function donutDelete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: index.php?url=dashboard/admin&msg=' . urlencode('Menu donut berhasil dihapus.'));
            exit;
        }
        header('Location: index.php?url=dashboard/admin');
        exit;
    }

    public function queueDelete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $queueModel = new Queue();
            $queueModel->delete($id);
            header('Location: index.php?url=dashboard/admin&msg=' . urlencode('Antrean berhasil dihapus.'));
            exit;
        }
        header('Location: index.php?url=dashboard/admin');
        exit;
    }
}
