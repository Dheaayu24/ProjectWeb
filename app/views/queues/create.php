<?php
/**
 * View: Halaman Ambil Antrean
 * File: app/views/queues/create.php
 * Variabel dari Controller: $restaurant (array), $user (array|null), $error (string|null)
 */
$title = 'Ambil Antrean – Mochi Mood';
include __DIR__ . '/../layouts/header.php';

$isOpen = ($restaurant['status'] ?? 'open') === 'open';
?>

<div class="container" style="padding: 40px 24px 80px;">
  <div style="max-width: 480px; margin: 0 auto;">
    
    <div style="text-align: center; margin-bottom: 24px;">
      <span class="badge badge-sage" style="margin-bottom: 8px;">🍩 Mochi Mood Queue</span>
      <h1 style="font-size: 28px; color: var(--deep-forest-green); margin-bottom: 4px;">Ambil Antrean</h1>
      <p style="font-size: 14px; color: var(--dark-olive);">Pilih jenis kunjungan dan dapatkan nomor urut Anda.</p>
    </div>

    <div class="card" style="padding: 34px 28px;">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
          <span>⚠️</span>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <?php if (!$isOpen): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
          <span>🔴</span>
          <span>Maaf, saat ini Mochi Mood sedang tutup dan tidak menerima antrean baru.</span>
        </div>
      <?php endif; ?>

      <form method="POST" action="index.php?url=queues/create">
        <?= Csrf::field() ?>
        <!-- Pilihan Dine-In atau Takeaway -->
        <label class="form-label" style="margin-bottom: 10px;">Pilihan Kunjungan</label>
        <input type="hidden" name="type" id="visit_type_input" value="dine_in">
        
        <div class="type-toggle-group">
          <div class="type-toggle-card active" id="toggle_dine" onclick="QueueBites.setType('dine_in')">
            🍽️ Dine-In<br><small style="font-size: 11.5px; opacity: 0.85;">Makan di Tempat</small>
          </div>
          <div class="type-toggle-card" id="toggle_takeaway" onclick="QueueBites.setType('takeaway')">
            🛍️ Takeaway<br><small style="font-size: 11.5px; opacity: 0.85;">Bawa Pulang</small>
          </div>
        </div>

        <!-- Nama Pemesan -->
        <div class="form-group">
          <label class="form-label">Nama Pemesan</label>
          <input type="text" name="customer_name" class="form-control" placeholder="Nama Anda" required value="<?= htmlspecialchars($user['name'] ?? 'Khansa') ?>">
        </div>

        <!-- Nomor Telepon / WhatsApp -->
        <div class="form-group">
          <label class="form-label">Nomor WhatsApp / HP</label>
          <input type="tel" name="customer_phone" class="form-control" placeholder="08xxxxxxxxxx" required value="<?= htmlspecialchars($user['phone'] ?? '081234567890') ?>">
        </div>

        <!-- Jumlah Pengunjung (Tampil saat Dine-In) -->
        <div class="form-group" id="people_count_wrapper">
          <label class="form-label">Jumlah Pengunjung</label>
          <select name="people_count" class="form-control">
            <option value="1">1 Orang (Solo)</option>
            <option value="2" selected>2 Orang (Duet)</option>
            <option value="3">3 Orang</option>
            <option value="4">4 Orang (Family Table)</option>
            <option value="5">5 Orang</option>
            <option value="6">6+ Orang (Rombongan Besar)</option>
          </select>
        </div>

        <!-- Tombol Konfirmasi Antrean -->
        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 14px;" <?= !$isOpen ? 'disabled style="opacity:0.6; cursor:not-allowed;"' : '' ?>>
          Konfirmasi Antrean 🎫
        </button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

