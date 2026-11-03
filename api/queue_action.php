<?php
/**
 * ============================================================================
 * MOCHI MOOD – API Action Handler
 * File: api/queue_action.php
 * Handles staff calling, advancing, completing, and cancelling queues
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Queue.php';
require_once __DIR__ . '/../app/helpers/Auth.php';

Auth::start();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action  = $input['action'] ?? '';
$queueId = isset($input['queue_id']) ? (int)$input['queue_id'] : 0;

if (!$queueId && $action !== 'call_next') {
    echo json_encode(['success' => false, 'message' => 'Queue ID is required']);
    exit;
}

try {
    $queueModel = new Queue();

    switch ($action) {
        case 'call':
            $queueModel->updateStatus($queueId, Queue::STATUS_CALLED);
            echo json_encode(['success' => true, 'message' => 'Antrean berhasil dipanggil']);
            break;

        case 'serve':
        case 'start_serving':
            $queueModel->updateStatus($queueId, Queue::STATUS_SERVING);
            echo json_encode(['success' => true, 'message' => 'Antrean sedang dilayani']);
            break;

        case 'complete':
            $queueModel->updateStatus($queueId, Queue::STATUS_COMPLETED);
            echo json_encode(['success' => true, 'message' => 'Antrean selesai dilayani']);
            break;

        case 'cancel':
            $queueModel->updateStatus($queueId, Queue::STATUS_CANCELLED);
            echo json_encode(['success' => true, 'message' => 'Antrean dibatalkan']);
            break;

        case 'call_next':
            $called = $queueModel->callNextQueue();
            if ($called) {
                echo json_encode(['success' => true, 'message' => 'Memanggil antrean ' . $called['queue_number'], 'queue' => $called]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Tidak ada antrean yang sedang menunggu']);
            }
            break;

        case 'delete':
            $queueModel->delete($queueId);
            echo json_encode(['success' => true, 'message' => 'Antrean berhasil dihapus']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

