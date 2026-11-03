<?php
/**
 * ============================================================================
 * MOCHI MOOD – Real-Time Queue Status API
 * File: api/queue_status.php
 * Polling endpoint for live queue tracking (returns position, status, est. time)
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Queue.php';

$number = $_GET['number'] ?? '';
$queueId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($number) && $queueId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parameter nomor atau ID antrean dibutuhkan']);
    exit;
}

try {
    $queueModel = new Queue();

    if ($queueId > 0) {
        $queue = $queueModel->getById($queueId);
    } else {
        $queue = $queueModel->getByQueueNumber($number);
    }

    if (!$queue) {
        echo json_encode(['success' => false, 'message' => 'Antrean tidak ditemukan']);
        exit;
    }

    $pos = $queueModel->getUserQueuePosition($queue['id']);
    $estMinutes = $queueModel->getEstimatedWaitMinutes($queue['id']);

    echo json_encode([
        'success'     => true,
        'queue'       => $queue,
        'position'    => $pos,
        'est_minutes' => $estMinutes,
        'ahead_count' => max(0, $pos - 1)
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

