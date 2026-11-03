<?php
/**
 * View: Halaman Cafe dan Menu Mochi Mood
 * File: app/views/restaurants/detail.php
 * Variabel dari Controller: $restaurant (array), $donuts (array), $waitingCount (int)
 */
$title = 'Cafe & Menu – Mochi Mood';
include __DIR__ . '/../layouts/header.php';

$isOpen = ($restaurant['status'] ?? 'open') === 'open';
?>

<div class="container" style="padding: 40px 24px 80px;">
  <!-- Header Informasi Toko Mochi Mood -->
  <div class="card" style="margin-bottom: 36px; padding: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
      <div>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
          <h1 style="font-size: 32px; color: var(--deep-forest-green); margin: 0;"><?= htmlspecialchars($restaurant['name']) ?></h1>
          <span class="badge <?= $isOpen ? 'badge-open' : 'badge-closed' ?>">
            <?= $isOpen ? '🟢 Buka' : '🔴 Tutup' ?>
          </span>
        </div>
        <p style="font-size: 14.5px; color: var(--dark-olive); max-width: 620px; line-height: 1.5;">
          <?= htmlspecialchars($restaurant['description']) ?>
        </p>

        <div style="display: flex; gap: 24px; margin-top: 18px; font-size: 13.5px; color: var(--dark-olive); flex-wrap: wrap;">
          <div>📍 <?= htmlspecialchars($restaurant['address']) ?></div>
          <div>⏰ Jam Buka: <?= htmlspecialchars($restaurant['opening_hours']) ?></div>
          <div>👥 Menunggu: <strong><?= (int)$waitingCount ?> Rombongan</strong></div>
        </div>
      </div>

      <div>
        <a href="index.php?url=queues/create" class="btn btn-primary btn-lg" <?= !$isOpen ? 'style="opacity:0.6; pointer-events:none;"' : '' ?>>
          Ambil Antrean Sekarang 🎫
        </a>
      </div>
    </div>
  </div>

  <!-- Daftar Donut (Filled Round Donuts) -->
  <div>
    <div style="margin-bottom: 24px;">
      <h2 style="font-size: 26px; color: var(--deep-forest-green); margin-bottom: 4px;">Daftar Menu Donut</h2>
      <p style="font-size: 14px; color: var(--dark-olive);">Donut bulat empuk dengan isian leleh dan topping melimpah.</p>
    </div>

    <div class="menu-grid">
      <?php if (!empty($donuts)): ?>
        <?php foreach ($donuts as $d): ?>
          <div class="donut-card">
            <div class="donut-thumb">
              <img src="<?= htmlspecialchars($d['image_url']) ?>" alt="<?= htmlspecialchars($d['name']) ?>">
            </div>
            <div class="donut-body">
              <h3 class="donut-title"><?= htmlspecialchars($d['name']) ?></h3>
              <p class="donut-desc"><?= htmlspecialchars($d['description']) ?></p>
              
              <div class="donut-footer">
                <span class="donut-price">Rp <?= number_format($d['price'], 0, ',', '.') ?></span>
                <span class="badge badge-sage">Fresh Daily</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="grid-column: 1/-1; color: var(--dark-olive);">Menu donut belum tersedia.</p>
      <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 48px;">
      <a href="index.php?url=queues/create" class="btn btn-primary btn-lg">
        Ambil Nomor Antrean Sekarang →
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
