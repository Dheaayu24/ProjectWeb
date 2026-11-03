<?php
/**
 * View: Halaman Daftar Restoran
 * File: app/views/restaurants/index.php
 * Variabel dari Controller: $restaurants (array), $keyword (string|null)
 */
$title = 'Daftar Restoran – QueueBites';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container" style="padding: 40px 24px 80px;">
  <!-- Header & Search -->
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 36px; flex-wrap: wrap; gap: 20px;">
    <div>
      <div class="badge badge-sage" style="margin-bottom: 8px;">🍽️ Temukan Meja Favoritmu</div>
      <h1 class="section-title" style="margin-bottom: 6px;">Daftar Restoran</h1>
      <p class="section-subtitle">Pilih restoran untuk melihat informasi antrean dan mengambil nomor urut.</p>
    </div>

    <!-- Search Form -->
    <form method="GET" action="index.php" style="display: flex; gap: 8px; min-width: 280px;">
      <input type="hidden" name="url" value="restaurants">
      <input type="text" name="q" class="form-control" placeholder="Cari restoran / lokasi..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" style="padding: 10px 18px; font-size: 13.5px;">
      <button type="submit" class="btn btn-primary btn-sm">Cari</button>
    </form>
  </div>

  <!-- Restaurant Grid Cards -->
  <div class="resto-grid">
    <?php if (!empty($restaurants)): ?>
      <?php foreach ($restaurants as $r): ?>
        <div class="resto-card">
          <div class="resto-thumb">
            <?php
              $images = [
                1 => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=600&q=80',
                2 => 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=600&q=80',
                3 => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=600&q=80',
                4 => 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=600&q=80',
                5 => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=600&q=80',
              ];
              $thumb = $images[$r['id']] ?? 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=600&q=80';
            ?>
            <img src="<?= $thumb ?>" alt="<?= htmlspecialchars($r['name']) ?>">
            <div class="resto-status-badge">
              <span class="badge <?= $r['status'] === 'open' ? 'badge-open' : 'badge-closed' ?>">
                <?= $r['status'] === 'open' ? '🟢 Buka' : '🔴 Tutup' ?>
              </span>
            </div>
          </div>

          <div class="resto-body">
            <h3 class="resto-title"><?= htmlspecialchars($r['name']) ?></h3>
            <p class="resto-desc"><?= htmlspecialchars($r['description'] ?? 'Nikmati hidangan lezat dan pengalaman makan terbaik.') ?></p>
            
            <div class="resto-meta">
              <div class="resto-meta-item">
                <span>📍</span> <?= htmlspecialchars($r['address']) ?>
              </div>
              <div class="resto-meta-item">
                <span>⏰</span> <?= htmlspecialchars($r['opening_hours']) ?>
              </div>
            </div>

            <a href="index.php?url=restaurants/detail&id=<?= $r['id'] ?>" class="btn btn-primary btn-block">
              Lihat Detail & Antre
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; background: #FFFFFF; border-radius: 20px; border: 1px solid var(--border);">
        <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
        <h3 style="font-size: 20px; color: var(--primary); margin-bottom: 6px;">Restoran Tidak Ditemukan</h3>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 18px;">Tidak ada restoran yang cocok dengan kata kunci pencarian Anda.</p>
        <a href="index.php?url=restaurants" class="btn btn-secondary btn-sm">Lihat Seluruh Restoran</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

