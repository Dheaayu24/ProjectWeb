<?php
/**
 * View: Dashboard Admin Toko Mochi Mood
 * File: app/views/dashboard/admin.php
 * Variabel dari Controller: $restaurant (array), $queues (array), $donuts (array), $summary (array), $success (string|null), $error (string|null)
 */
$title = 'Dashboard Admin – Mochi Mood';
include __DIR__ . '/../layouts/header.php';

$isOpen = ($restaurant['status'] ?? 'open') === 'open';
?>

<div class="container" style="padding: 40px 24px 80px;">
  <!-- Header Admin -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 14px;">
    <div>
      <div class="badge badge-called" style="margin-bottom: 6px;">
        👑 Administrator Mochi Mood
      </div>
      <h1 style="font-size: 28px; color: var(--deep-forest-green); margin-bottom: 2px;">Dashboard Admin</h1>
      <p style="font-size: 14px; color: var(--dark-olive);">Kelola antrean dan operasional toko Mochi Mood.</p>
    </div>

    <div>
      <a href="index.php?url=dashboard/staff" class="btn btn-secondary btn-sm">
        Buka Dashboard Petugas Konter →
      </a>
    </div>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success">
      <span>✅</span>
      <span><?= htmlspecialchars($success) ?></span>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
      <span>⚠️</span>
      <span><?= htmlspecialchars($error) ?></span>
    </div>
  <?php endif; ?>

  <!-- Ringkasan Jumlah Antrean & Data Operasional -->
  <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px;">
    <div class="card" style="padding: 20px;">
      <div style="font-size: 32px; font-weight: 800; color: var(--deep-forest-green); line-height: 1;">
        <?= count($queues) ?>
      </div>
      <div style="font-size: 13px; color: var(--dark-olive); margin-top: 6px;">Total Semua Antrean</div>
    </div>

    <div class="card" style="padding: 20px;">
      <div style="font-size: 32px; font-weight: 800; color: #16A34A; line-height: 1;">
        <?= (int)($summary['completed_count'] ?? 0) ?>
      </div>
      <div style="font-size: 13px; color: var(--dark-olive); margin-top: 6px;">Antrean Selesai</div>
    </div>

    <div class="card" style="padding: 20px;">
      <div style="font-size: 32px; font-weight: 800; color: #DC2626; line-height: 1;">
        <?= (int)($summary['cancelled_count'] ?? 0) ?>
      </div>
      <div style="font-size: 13px; color: var(--dark-olive); margin-top: 6px;">Antrean Dibatalkan</div>
    </div>

    <div class="card" style="padding: 20px;">
      <div style="font-size: 32px; font-weight: 800; color: var(--deep-forest-green); line-height: 1;">
        <?= count($donuts) ?>
      </div>
      <div style="font-size: 13px; color: var(--dark-olive); margin-top: 6px;">Menu Donut Aktif</div>
    </div>
  </div>

  <!-- Pengaturan Operasional Toko Mochi Mood -->
  <div class="card" style="padding: 28px; margin-bottom: 30px;">
    <h3 style="font-size: 18px; color: var(--deep-forest-green); margin-bottom: 18px;">🏪 Status Operasional Mochi Mood</h3>

    <form method="POST" action="index.php?url=dashboard/admin/store-update">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
        <div class="form-group">
          <label class="form-label">Nama Toko / Cafe</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($restaurant['name']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Jam Operasional</label>
          <input type="text" name="opening_hours" class="form-control" value="<?= htmlspecialchars($restaurant['opening_hours']) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Alamat Lengkap Toko</label>
        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($restaurant['address']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Status Penerimaan Antrean</label>
        <select name="status" class="form-control">
          <option value="open" <?= $isOpen ? 'selected' : '' ?>>Buka (Menerima Antrean Online)</option>
          <option value="closed" <?= !$isOpen ? 'selected' : '' ?>>Tutup (Antrean Ditutup Sementara)</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-sm">
        Simpan Perubahan Operasional
      </button>
    </form>
  </div>

  <!-- Pengelolaan Menu Donut (Filled Round Donuts) -->
  <div class="card" style="padding: 28px; margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
      <h3 style="font-size: 18px; color: var(--deep-forest-green);">🍩 Pengelolaan Menu Donut</h3>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('addDonutForm').style.display='block'">
        ➕ Tambah Varian Donut
      </button>
    </div>

    <!-- Form Tambah Donut (Collapsible) -->
    <div id="addDonutForm" style="display: none; background: var(--warm-cream); border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid var(--border-color);">
      <h4 style="font-size: 16px; margin-bottom: 12px; color: var(--deep-forest-green);">Tambah Menu Donut Baru</h4>
      <form method="POST" action="index.php?url=dashboard/admin/donut-add">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="form-label">Nama Donut (Bulat Berisi)</label>
            <input type="text" name="donut_name" class="form-control" placeholder="Contoh: Salted Caramel Praline" required>
          </div>
          <div class="form-group">
            <label class="form-label">Harga (Rp)</label>
            <input type="number" name="donut_price" class="form-control" placeholder="18000" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Deskripsi Isian & Topping</label>
          <input type="text" name="donut_desc" class="form-control" placeholder="Donut bulat empuk dengan isian lumer..." required>
        </div>
        <div class="form-group">
          <label class="form-label">URL / Path Foto Donut</label>
          <input type="text" name="donut_image" class="form-control" placeholder="images/donuts/matcha-crunch.jpg atau URL..." value="images/donuts/matcha-crunch.jpg">
        </div>
        <div style="display: flex; gap: 8px;">
          <button type="submit" class="btn btn-primary btn-sm">Simpan Donut</button>
          <button type="button" class="btn btn-white btn-sm" onclick="document.getElementById('addDonutForm').style.display='none'">Batal</button>
        </div>
      </form>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Donut</th>
            <th>Deskripsi Isian</th>
            <th>Harga</th>
            <th style="text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($donuts as $d): ?>
            <tr>
              <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
              <td style="font-size: 13px; color: var(--dark-olive);"><?= htmlspecialchars($d['description']) ?></td>
              <td><strong>Rp <?= number_format($d['price'], 0, ',', '.') ?></strong></td>
              <td style="text-align: right;">
                <a href="index.php?url=dashboard/admin/donut-delete&id=<?= $d['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus menu donut ini?')">
                  Hapus
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pengelolaan Antrean Pelanggan -->
  <div class="card" style="padding: 28px;">
    <h3 style="font-size: 18px; color: var(--deep-forest-green); margin-bottom: 18px;">📋 Riwayat Seluruh Antrean</h3>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Nomor</th>
            <th>Pelanggan</th>
            <th>Tipe</th>
            <th>Tamu</th>
            <th>Status</th>
            <th>Waktu</th>
            <th style="text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($queues)): ?>
            <?php foreach (array_slice($queues, 0, 15) as $q): ?>
              <tr>
                <td style="font-weight: 800; color: var(--deep-forest-green);"><?= htmlspecialchars($q['queue_number']) ?></td>
                <td>
                  <strong><?= htmlspecialchars($q['customer_name'] ?? $q['user_name'] ?? 'Pelanggan') ?></strong>
                </td>
                <td><?= ($q['type'] ?? 'dine_in') === 'takeaway' ? 'Takeaway' : 'Dine-In' ?></td>
                <td><?= (int)$q['people_count'] ?> Orang</td>
                <td>
                  <span class="badge badge-<?= $q['status'] ?>">
                    <?= strtoupper($q['status']) ?>
                  </span>
                </td>
                <td style="font-size: 12.5px; color: var(--dark-olive);">
                  <?= date('d M, H:i', strtotime($q['created_at'])) ?>
                </td>
                <td style="text-align: right;">
                  <a href="index.php?url=dashboard/admin/queue-delete&id=<?= $q['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus antrean ini?')">
                    Hapus
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; color: var(--dark-olive); padding: 24px;">Belum ada riwayat antrean.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
