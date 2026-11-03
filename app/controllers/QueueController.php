<?php
/**
 * Controller: QueueController
 * File: app/controllers/QueueController.php
 */

require_once __DIR__ . '/../models/Queue.php';
require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../helpers/Auth.php';

class QueueController
{
    public function create(): void
    {
        Auth::start();
        $user = Auth::user();
        $restoModel = new Restaurant();
        $restaurant = $restoModel->getById(1);
        $error = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $name   = trim($_POST['customer_name'] ?? ($user['name'] ?? ''));
            $phone  = trim($_POST['customer_phone'] ?? ($user['phone'] ?? ''));
            $type   = $_POST['type'] ?? 'dine_in';
            $people = ($type === 'takeaway') ? 1 : max(1, (int)($_POST['people_count'] ?? 1));

            if (empty($name)) {
                $error = 'Nama pemesan wajib diisi.';
            } else {
                try {
                    $queueModel = new Queue();
                    $userId = $user ? (int)$user['id'] : 3;

                    $newQueueId = $queueModel->create([
                        'user_id'        => $userId,
                        'restaurant_id'  => 1,
                        'customer_name'  => $name,
                        'customer_phone' => $phone,
                        'people_count'   => $people,
                        'type'           => $type,
                        'status'         => 'waiting'
                    ]);

                    header('Location: index.php?url=queues/status&id=' . $newQueueId);
                    exit;
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        include __DIR__ . '/../views/queues/create.php';
    }

    public function status(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $queueModel = new Queue();

        if ($id > 0) {
            $queue = $queueModel->getById($id);
        } else {
            $all = $queueModel->getByRestaurant(1);
            $queue = !empty($all) ? $all[0] : null;
        }

        if (!$queue) {
            header('Location: index.php?url=queues/create');
            exit;
        }

        $position    = $queueModel->getUserQueuePosition($queue['id']);
        $peopleAhead = $queueModel->getPeopleAhead($queue['id']);
        $estMinutes  = $queueModel->getEstimatedWaitMinutes($queue['id']);

        include __DIR__ . '/../views/queues/status.php';
    }

    public function cancel(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $queueId = (int)($input['queue_id'] ?? 0);

        try {
            $queueModel = new Queue();
            $queueModel->updateStatus($queueId, Queue::STATUS_CANCELLED);
            echo json_encode(['success' => true, 'message' => 'Antrean berhasil dibatalkan.']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function apiStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($_GET['id'] ?? 0);
        $queueModel = new Queue();
        $queue = $queueModel->getById($id);

        if (!$queue) {
            echo json_encode(['success' => false, 'message' => 'Antrean tidak ditemukan.']);
            exit;
        }

        $pos   = $queueModel->getUserQueuePosition($id);
        $ahead = $queueModel->getPeopleAhead($id);
        $est   = $queueModel->getEstimatedWaitMinutes($id);

        echo json_encode([
            'success'     => true,
            'queue'       => $queue,
            'position'    => $pos,
            'ahead_count' => $ahead,
            'est_minutes' => $est
        ]);
        exit;
    }
}
