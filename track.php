<?php
/**
 * ============================================================================
 * MOCHI MOOD BAKERY – Track My Queue Live Radar Screen
 * File: track.php
 * Replicates Screen 6 from the UI collection (Dark Forest Green Theme)
 * ============================================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/Queue.php';
require_once __DIR__ . '/app/helpers/Auth.php';

Auth::start();

$number = $_GET['number'] ?? 'A023';
$queueModel = new Queue();
$queue = $queueModel->getByQueueNumber($number);

// Match mockup data defaults
if (!$queue) {
    $queue = [
        'id'           => 2,
        'queue_number' => 'A023',
        'status'       => 'waiting',
    ];
    $position = 3;
    $estWait = 5;
} else {
    $pos = $queueModel->getUserQueuePosition($queue['id']);
    $position = $pos > 0 ? $pos : 3;
    $estWait  = $queueModel->getEstimatedWaitMinutes($queue['id']) ?: 5;
}

$pageTitle = 'Live Queue Tracker: ' . $queue['queue_number'] . ' – Mochi Mood';
include __DIR__ . '/includes/header.php';
?>

<div style="padding: 40px 16px 60px; min-height: calc(100vh - 50px); display: flex; align-items: center; justify-content: center; flex-direction: column;">
  <div class="mobile-device-shell radar-screen" style="height: 720px; border-color: #122115;">
    <div class="mobile-notch" style="background: #122115;"><div class="mobile-speaker"></div></div>
    
    <div class="mobile-screen-content radar-screen">
      <!-- Header Bar -->
      <div class="mobile-header-bar">
        <a href="index.php" class="mobile-brand-title">Mochi<br>Mood</a>
        <a href="javascript:location.reload();" class="mobile-nav-icon" title="Refresh Status">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
        </a>
      </div>

      <div class="mobile-body" style="text-align: center; justify-content: space-around;">
        <!-- Circular Radar Indicator -->
        <div class="radar-gauge-wrap">
          <div class="radar-outer-ring"></div>
          <div class="radar-inner-ring">
            <span class="radar-queue-num"><?= htmlspecialchars($queue['queue_number']) ?></span>
            <span class="radar-queue-pos" id="radar_position_text">#<?= $position ?> in Queue</span>
          </div>
        </div>

        <!-- Almost Your Turn Alert Banner -->
        <div>
          <div class="radar-pill-alert" id="radar_banner">
            🔔 Almost Your Turn!
          </div>
        </div>

        <!-- Estimated Wait Time -->
        <div style="margin: 10px 0;">
          <div style="font-size: 13px; color: rgba(255, 255, 255, 0.75); margin-bottom: 2px;">
            Estimated Time
          </div>
          <div style="font-size: 20px; font-weight: 700; color: #FFFFFF;" id="radar_est_time">
            ≈ <?= $estWait ?> min
          </div>
        </div>

        <!-- Sweet Reminder Message -->
        <div style="background: rgba(255, 255, 255, 0.08); border-radius: var(--radius-pill); padding: 10px 18px; margin: 10px auto; max-width: 260px; font-size: 13px; color: rgba(255, 255, 255, 0.9);">
          Please prepare to come soon ♡
        </div>

        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
          <button type="button" class="btn-mochi btn-mochi-sm btn-mochi-cream" onclick="MochiMood.playChime()" title="Test Bell Sound">
            🔔 Test Chime
          </button>
          <a href="ticket.php?number=<?= urlencode($queue['queue_number']) ?>" class="btn-mochi btn-mochi-sm" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);">
            View Ticket
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  MochiMood.initTrackerPolling('<?= htmlspecialchars($queue['queue_number']) ?>');
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

