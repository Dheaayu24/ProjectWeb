<?php
/**
 * View: Halaman Status Antrean
 * File: app/views/queues/status.php
 * Variabel dari Controller: $queue (array), $position (int), $peopleAhead (int), $estMinutes (int)
 */
$title = 'Status Antrean ' . htmlspecialchars($queue['queue_number']) . ' – Mochi Mood';
include __DIR__ . '/../layouts/header.php';

$isWaiting = ($queue['status'] === 'waiting');
?>

<div class="container" style="padding: 40px 24px 80px;">
  <div class="queue-box">
    
    <div style="margin-bottom: 20px;">
      <span class="badge badge-sage" style="margin-bottom: 8px;">
        🍩 Mochi Mood Live Queue
      </span>
      <h1 style="font-size: 30px; margin-bottom: 4px;">Status Antrean Anda</h1>
      <p style="font-size: 13.5px; color: var(--dark-olive);">
        Status ini diperbarui otomatis melalui Fetch API. <span id="queue_loading_icon" style="display:none;">🔄</span>
      </p>
    </div>

    <!-- Kartu Tiket Utama -->
    <div class="card" style="padding: 38px 28px;">
      
      <div style="font-size: 13px; font-weight: 600; color: var(--dark-olive); text-transform: uppercase; letter-spacing: 1px;">
        Nomor Antrean Anda
      </div>

      <!-- Nomor Antrean Ukuran Besar -->
      <div class="queue-code-large">
        <?= htmlspecialchars($queue['queue_number']) ?>
      </div>

      <!-- Badge Status Antrean -->
      <div style="margin-bottom: 22px;">
        <span class="badge badge-<?= $queue['status'] ?>" id="queue_status_badge" style="font-size: 14px; padding: 6px 18px;">
          <?= strtoupper($queue['status']) ?>
        </span>
      </div>

      <!-- 3 Kartu Metrik Utama -->
      <div class="queue-kpi-grid">
        <div class="queue-kpi-box">
          <div class="queue-kpi-lbl">Posisi Antrean</div>
          <div class="queue-kpi-val" id="queue_position_val">
            <?= $position > 0 ? '#' . $position : '-' ?>
          </div>
        </div>

        <div class="queue-kpi-box">
          <div class="queue-kpi-lbl">Orang di Depan</div>
          <div class="queue-kpi-val" id="queue_ahead_val">
            <?= (int)$peopleAhead ?> Rombongan
          </div>
        </div>

        <div class="queue-kpi-box">
          <div class="queue-kpi-lbl">Estimasi Waktu</div>
          <div class="queue-kpi-val" id="queue_wait_val">
            ≈ <?= (int)$estMinutes ?> Menit
          </div>
        </div>
      </div>

      <!-- Informasi Tambahan Tiket -->
      <div style="background-color: var(--warm-cream); border-radius: 14px; padding: 14px 18px; text-align: left; border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <div>
          <div style="font-size: 13.5px; font-weight: 600; color: var(--deep-forest-green);">
            Mochi Mood Central
          </div>
          <div style="font-size: 12px; color: var(--dark-olive);">
            Atas nama: <strong><?= htmlspecialchars($queue['customer_name'] ?? $queue['user_name'] ?? 'Pelanggan') ?></strong>
          </div>
        </div>
        <span class="badge badge-olive" style="font-size: 12px;">
          <?= ($queue['type'] ?? 'dine_in') === 'takeaway' ? 'Takeaway' : 'Dine-In' ?> • <?= $queue['people_count'] ?> Tamu
        </span>
      </div>

      <!-- Tombol Aksi Batal Antrean & Bel Audio -->
      <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
        <?php if ($queue['status'] === 'waiting'): ?>
          <button type="button" class="btn btn-danger btn-sm" onclick="QueueBites.cancelQueue(<?= (int)$queue['id'] ?>)">
            Batal Antrean ✕
          </button>
        <?php endif; ?>

        <button type="button" class="btn btn-white btn-sm" onclick="QueueBites.playChime()" title="Uji Suara Bel Panggilan">
          🔔 Uji Suara Bel
        </button>

        <a href="index.php?url=restaurants/detail" class="btn btn-secondary btn-sm">
          Menu Donut
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Inisialisasi Auto-Polling Fetch API -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  QueueBites.initStatusPolling(<?= (int)$queue['id'] ?>);
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
