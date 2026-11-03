<?php
/**
 * View: Dashboard Petugas Mochi Mood
 * File: app/views/dashboard/staff.php
 * Variabel dari Controller: $queues (array), $summary (array), $restaurant (array)
 */
$title = 'Dashboard Petugas – Mochi Mood';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container" style="padding: 40px 24px 80px;">
  <!-- Header Petugas -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 14px;">
    <div>
      <div class="badge badge-sage" style="margin-bottom: 6px;">
        🧑‍🍳 Petugas Konter: Mochi Mood
      </div>
      <h1 style="font-size: 28px; color: var(--deep-forest-green); margin-bottom: 2px;">Dashboard Petugas</h1>
      <p style="font-size: 14px; color: var(--dark-olive);">Kelola dan panggil antrean pelanggan toko Mochi Mood.</p>
    </div>

    <div>
      <button class="btn btn-primary" onclick="QueueBites.staffAction(0, 'call_next')" title="Panggil antrean urutan pertama yang sedang menunggu">
        📢 Panggil Antrean Berikutnya
      </button>
    </div>
  </div>

  <!-- 3 Ringkasan Antrean -->
  <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 28px;">
    <div class="card" style="padding: 20px;">
      <div style="font-size: 32px; font-weight: 800; color: var(--deep-forest-green); line-height: 1;">
        <?= (int)($summary['today_total'] ?? count($queues)) ?>
      </div>
      <div style="font-size: 13px; color: var(--dark-olive); margin-top: 6px; font-weight: 500;">
        Total Antrean Hari Ini
      </div>
    </div>

    <div class="card" style="padding: 20px; border-left: 4px solid #1E40AF;">
      <div style="font-size: 32px; font-weight: 800; color: #1E40AF; line-height: 1;">
        <?= (int)($summary['in_service'] ?? 0) ?>
      </div>
      <div style="font-size: 13px; color: var(--dark-olive); margin-top: 6px; font-weight: 500;">
        Sedang Dilayani
      </div>
    </div>

    <div class="card" style="padding: 20px; border-left: 4px solid #854D0E;">
      <div style="font-size: 32px; font-weight: 800; color: #854D0E; line-height: 1;">
        <?= (int)($summary['waiting'] ?? 0) ?>
      </div>
      <div style="font-size: 13px; color: var(--dark-olive); margin-top: 6px; font-weight: 500;">
        Menunggu Panggilan
      </div>
    </div>
  </div>

  <!-- Daftar Antrean Pelanggan -->
  <div class="card" style="padding: 28px;">
    <h3 style="font-size: 18px; color: var(--deep-forest-green); margin-bottom: 18px;">Daftar Pelanggan Antre</h3>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Nomor</th>
            <th>Nama Pelanggan</th>
            <th>Jumlah Pengunjung</th>
            <th>Tipe</th>
            <th>Status</th>
            <th style="text-align: right;">Aksi Petugas</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($queues)): ?>
            <?php foreach ($queues as $q): ?>
              <tr>
                <td style="font-size: 18px; font-weight: 800; color: var(--deep-forest-green);">
                  <?= htmlspecialchars($q['queue_number']) ?>
                </td>
                <td>
                  <strong><?= htmlspecialchars($q['customer_name'] ?? $q['user_name'] ?? 'Pelanggan') ?></strong>
                  <div style="font-size: 11.5px; color: var(--dark-olive);">
                    <?= htmlspecialchars($q['customer_phone'] ?? $q['user_email'] ?? '') ?>
                  </div>
                </td>
                <td><?= (int)$q['people_count'] ?> Orang</td>
                <td>
                  <span class="badge badge-sage" style="font-size: 11px;">
                    <?= ($q['type'] ?? 'dine_in') === 'takeaway' ? 'Takeaway' : 'Dine-In' ?>
                  </span>
                </td>
                <td>
                  <span class="badge badge-<?= $q['status'] ?>">
                    <?= strtoupper($q['status']) ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <div style="display: inline-flex; gap: 6px;">
                    <!-- Tombol Panggil -->
                    <button type="button" class="btn btn-primary btn-sm" onclick="QueueBites.staffAction(<?= (int)$q['id'] ?>, 'call')">
                      Panggil
                    </button>
                    <!-- Tombol Mulai Melayani -->
                    <button type="button" class="btn btn-secondary btn-sm" onclick="QueueBites.staffAction(<?= (int)$q['id'] ?>, 'serve')">
                      Mulai Melayani
                    </button>
                    <!-- Tombol Selesaikan -->
                    <button type="button" class="btn btn-white btn-sm" onclick="QueueBites.staffAction(<?= (int)$q['id'] ?>, 'complete')">
                      Selesaikan
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; color: var(--dark-olive); padding: 36px;">
                Belum ada antrean pelanggan yang aktif saat ini.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
