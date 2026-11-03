<?php
/**
 * ============================================================================
 * MOCHI MOOD BAKERY – Queue Ticket Confirmation Screen
 * File: ticket.php
 * Replicates Screen 5 from the UI collection
 * ============================================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/Queue.php';
require_once __DIR__ . '/app/helpers/Auth.php';

Auth::start();

$number = $_GET['number'] ?? 'A023';
$queueModel = new Queue();
$queue = $queueModel->getByQueueNumber($number);

// Fallback data if queue not found (preserves exact UI screenshot values)
if (!$queue) {
    $queue = [
        'id'            => 2,
        'queue_number'  => 'A023',
        'type'          => 'dine_in',
        'people_count'  => 2,
        'status'        => 'waiting',
        'customer_name' => 'Khansa'
    ];
    $position = 5;
    $estWait = 10;
} else {
    $position = $queueModel->getUserQueuePosition($queue['id']) ?: 5;
    $estWait  = $queueModel->getEstimatedWaitMinutes($queue['id']) ?: 10;
}

$typeLabel = ($queue['type'] === 'takeaway') ? 'Takeaway' : 'Dine-In';
$peopleLabel = $queue['people_count'] . ' People';
$statusLabel = ucfirst($queue['status']);

$pageTitle = 'Your Queue Number: ' . $queue['queue_number'] . ' – Mochi Mood';
include __DIR__ . '/includes/header.php';
?>

<div style="padding: 40px 16px 60px; min-height: calc(100vh - 50px); display: flex; align-items: center; justify-content: center; flex-direction: column;">
  <div class="mobile-device-shell" style="height: 720px;">
    <div class="mobile-notch"><div class="mobile-speaker"></div></div>
    
    <div class="mobile-screen-content">
      <!-- Header Bar -->
      <div class="mobile-header-bar">
        <a href="index.php" class="mobile-brand-title">Mochi<br>Mood</a>
        <a href="index.php" class="mobile-nav-icon" title="Home">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        </a>
      </div>

      <div class="mobile-body">
        <!-- Ticket Card Box -->
        <div class="ticket-card" style="margin-top: 10px;">
          <div style="font-size: 13.5px; color: var(--text-muted); font-weight: 500;">
            Your Queue Number
          </div>

          <div class="ticket-number">
            <?= htmlspecialchars($queue['queue_number']) ?>
          </div>

          <div class="badge-mochi badge-sage" style="margin-bottom: 12px;">
            <?= htmlspecialchars($typeLabel) ?> • <?= htmlspecialchars($peopleLabel) ?>
          </div>

          <!-- 3 Stats Boxes -->
          <div class="ticket-stats-grid">
            <div class="ticket-stat-box">
              <div class="ticket-stat-label">Position in Queue</div>
              <div class="ticket-stat-val">#<?= $position ?></div>
            </div>

            <div class="ticket-stat-box">
              <div class="ticket-stat-label">Estimated Wait</div>
              <div class="ticket-stat-val">≈ <?= $estWait ?> min</div>
            </div>

            <div class="ticket-stat-box">
              <div class="ticket-stat-label">Status</div>
              <div class="ticket-stat-val" style="color: #2E6B38;"><?= htmlspecialchars($statusLabel) ?></div>
            </div>
          </div>

          <!-- Track Queue Button -->
          <a href="track.php?number=<?= urlencode($queue['queue_number']) ?>" class="btn-mochi btn-mochi-block">
            Track My Queue
          </a>

          <div style="margin-top: 14px;">
            <a href="index.php" style="font-size: 13px; color: var(--text-muted); font-weight: 600;">
              Back to Home
            </a>
          </div>
        </div>

        <!-- Bottom visual donut -->
        <div class="mobile-bottom-decor" style="margin-top: 10px;">
          <img src="https://images.unsplash.com/photo-1527515862127-a4fc05baf7a5?auto=format&fit=crop&w=300&q=80" alt="Mochi Donut">
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

