<?php
/**
 * View: Landing Page Mochi Mood
 * File: app/views/home/index.php
 * Variabel dari Controller: $restaurant (array), $featuredDonuts (array)
 */
$title = 'Mochi Mood – A Little Mochi, A Lot of Happiness ♡';
include __DIR__ . '/../layouts/header.php';
?>

<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <div class="hero-grid">
      <div>
        <div class="hero-badge">
          ✨ Artisanal Filled Round Donuts
        </div>
        <h1 class="hero-title">
          A Little Mochi,<br>
          A Lot of <span>Happiness</span> ♡
        </h1>
        <p class="hero-desc">
          Donut bulat empuk dengan isian lumer dan topping melimpah. Ambil nomor antrean online sekarang dan nikmati tanpa perlu menunggu lama!
        </p>

        <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
          <a href="index.php?url=queues/create" class="btn btn-primary btn-lg">
            Mulai Antre →
          </a>
          <a href="index.php?url=restaurants/detail" class="btn btn-secondary btn-lg">
            Lihat Menu Donut
          </a>
        </div>
      </div>

      <div style="display: flex; justify-content: center;">
        <div class="hero-image-card">
          <!-- Filled Round Donut (Chewy's Dessert Style: Bulat Penuh Tanpa Lubang) -->
          <img src="https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=800&q=80" alt="Freshly Baked Filled Round Donut Mochi Mood">
          <div class="hero-float-pill">
            <span>🍩</span>
            <span>Freshly Baked Every Morning</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Menu Donut Unggulan (Filled Round Donuts) -->
<section style="padding: 50px 0 60px;">
  <div class="container">
    <div class="section-head">
      <div class="badge badge-sage" style="margin-bottom: 8px;">Signature Flavors</div>
      <h2 class="section-title">Menu Donut Unggulan</h2>
      <p class="section-sub">Donut bulat penuh dengan isian melimpah yang paling disukai pelanggan.</p>
    </div>

    <div class="menu-grid">
      <?php if (!empty($featuredDonuts)): ?>
        <?php foreach (array_slice($featuredDonuts, 0, 3) as $d): ?>
          <div class="donut-card">
            <div class="donut-thumb">
              <img src="<?= htmlspecialchars($d['image_url']) ?>" alt="<?= htmlspecialchars($d['name']) ?>">
            </div>
            <div class="donut-body">
              <h3 class="donut-title"><?= htmlspecialchars($d['name']) ?></h3>
              <p class="donut-desc"><?= htmlspecialchars($d['description']) ?></p>
              <div class="donut-footer">
                <span class="donut-price">Rp <?= number_format($d['price'], 0, ',', '.') ?></span>
                <a href="index.php?url=queues/create" class="btn btn-primary btn-sm">Antre Sekarang</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 32px;">
      <a href="index.php?url=restaurants/detail" class="btn btn-secondary">
        Lihat Seluruh Menu & Detail Cafe →
      </a>
    </div>
  </div>
</section>

<!-- 3 Langkah Sederhana Mengambil Antrean -->
<section class="steps-section" style="background-color: var(--soft-white); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
  <div class="container">
    <div class="section-head">
      <div class="badge badge-olive" style="margin-bottom: 8px;">Mudah & Cepat</div>
      <h2 class="section-title">Cara Ambil Antrean</h2>
      <p class="section-sub">Tiga langkah praktis untuk menikmati Mochi Mood tanpa antre berdiri.</p>
    </div>

    <div class="steps-grid">
      <div class="step-card">
        <div class="step-number">1</div>
        <h3>Pilih Kunjungan</h3>
        <p>Tentukan apakah Anda ingin Dine-In (makan di tempat) atau Takeaway (bawa pulang).</p>
      </div>

      <div class="step-card">
        <div class="step-number">2</div>
        <h3>Dapatkan Nomor</h3>
        <p>Isi jumlah rombongan dan konfirmasi untuk memperoleh tiket antrean digital Anda.</p>
      </div>

      <div class="step-card">
        <div class="step-number">3</div>
        <h3>Pantau Real-Time</h3>
        <p>Pantau posisi giliran Anda dari HP. Datang ke cafe saat nomor Anda dipanggil!</p>
      </div>
    </div>

    <div style="text-align: center; margin-top: 36px;">
      <a href="index.php?url=queues/create" class="btn btn-primary btn-lg">
        Mulai Ambil Antrean 🎫
      </a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
