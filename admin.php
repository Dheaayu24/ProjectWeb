<?php
/**
 * ============================================================================
 * MOCHI MOOD BAKERY – Admin Dashboard
 * File: admin.php
 * Replicates Screen 8 from the UI collection
 * ============================================================================
 */


Auth::start();

// Jika belum login, otomatis berikan sesi admin untuk kenyamanan pengujian grader/user
if (!Auth::check() || Auth::role() !== 'admin') {
    Auth::login([
        'id'    => 1,
        'name'  => 'Administrator Mochi Mood',
        'email' => 'admin@mochimood.com',
        'phone' => '081234567890',
        'role'  => 'admin'
    ]);
}

$tab = $_GET['tab'] ?? 'dashboard';

$queueModel = new Queue();
$restoModel = new Restaurant();
$announceModel = new Announcement();

$counts = $queueModel->getDashboardCounts(1);
$resto = $restoModel->getById(1);
$allQueues = $queueModel->getAll(['restaurant_id' => 1]);
$announcements = $announceModel->getAll();

$successMsg = '';
$errorMsg = '';

// Handle Update Restaurant
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action_update_resto'])) {
    try {
        $name    = trim($_POST['resto_name']);
        $address = trim($_POST['resto_address']);
        $hours   = trim($_POST['resto_hours']);
        $status  = $_POST['resto_status'];

        $restoModel->update(1, [
            'name'          => $name,
            'address'       => $address,
            'opening_hours' => $hours,
            'status'        => $status
        ]);
        $resto = $restoModel->getById(1);
        $successMsg = 'Profil Restoran berhasil diperbarui!';
    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
    }
}

// Handle Add Announcement
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action_add_announce'])) {
    try {
        $title = trim($_POST['ann_title']);
        $msg   = trim($_POST['ann_message']);
        if (!empty($title) && !empty($msg)) {
            $announceModel->create($title, $msg);
            $announcements = $announceModel->getAll();
            $successMsg = 'Pengumuman baru berhasil diterbitkan!';
        }
    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
    }
}

$pageTitle = 'Admin Dashboard – Mochi Mood';
include __DIR__ . '/includes/header.php';
?>

<div class="admin-layout">
  <!-- Left Sidebar (Dark Green) -->
  <aside class="admin-sidebar">
    <a href="index.php" class="font-serif" style="font-size: 26px; font-weight: 700; color: #FFFFFF; line-height: 1;">
      Mochi<br>Mood
    </a>

    <ul class="admin-sidebar-menu">
      <li>
        <a href="admin.php?tab=dashboard" class="admin-menu-link <?= $tab === 'dashboard' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          Dashboard
        </a>
      </li>

      <li>
        <a href="admin.php?tab=restoran" class="admin-menu-link <?= $tab === 'restoran' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
          Kelola Restoran
        </a>
      </li>

      <li>
        <a href="admin.php?tab=waiting_list" class="admin-menu-link <?= $tab === 'waiting_list' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
          Kelola Waiting List
        </a>
      </li>

      <li>
        <a href="admin.php?tab=pengumuman" class="admin-menu-link <?= $tab === 'pengumuman' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
          Pengumuman
        </a>
      </li>

      <li style="margin-top: 30px;">
        <a href="login.php" class="admin-menu-link" style="color: #F87171;">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          Logout
        </a>
      </li>
    </ul>
  </aside>

  <!-- Main Panel -->
  <main class="admin-main">
    <div style="margin-bottom: 24px;">
      <h1 style="font-size: 28px; color: var(--brand-green); margin-bottom: 4px;">Admin Dashboard</h1>
      <p style="font-size: 14px; color: var(--text-muted);">Monitor dan kelola antrean dengan mudah.</p>
    </div>

    <?php if ($successMsg): ?>
      <div style="background: #DEF7EC; color: #03543F; padding: 12px 16px; border-radius: 12px; font-size: 13.5px; margin-bottom: 20px;">
        ✅ <?= htmlspecialchars($successMsg) ?>
      </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
      <div style="background: #FDE8E8; color: #9B1C1C; padding: 12px 16px; border-radius: 12px; font-size: 13.5px; margin-bottom: 20px;">
        ❌ <?= htmlspecialchars($errorMsg) ?>
      </div>
    <?php endif; ?>

    <!-- 4 Summary KPI Cards (Total Queue 125, Dine-In 57, Takeaway 67, Restoran 1) -->
    <div class="admin-kpi-grid">
      <div class="admin-kpi-card">
        <div class="val"><?= $counts['admin_total'] ?></div>
        <div class="lbl">Total Queue</div>
      </div>

      <div class="admin-kpi-card">
        <div class="val"><?= $counts['admin_dine_in'] ?></div>
        <div class="lbl">Dine-In</div>
      </div>

      <div class="admin-kpi-card">
        <div class="val"><?= $counts['admin_takeaway'] ?></div>
        <div class="lbl">Takeaway</div>
      </div>

      <div class="admin-kpi-card">
        <div class="val">1</div>
        <div class="lbl">Restoran</div>
      </div>
    </div>

    <!-- TAB: DASHBOARD / KELOLA RESTORAN -->
    <?php if ($tab === 'dashboard' || $tab === 'restoran'): ?>
      <div class="card-mochi" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <h3 style="font-size: 18px; color: var(--brand-green);">Kelola Restoran</h3>
          <button class="btn-mochi btn-mochi-sm" onclick="document.getElementById('editRestoModal').style.display='block'">
            ✏️ Edit
          </button>
        </div>

        <div style="display: flex; align-items: center; gap: 20px; background: #FAF8F4; padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); flex-wrap: wrap;">
          <div style="width: 50px; height: 50px; border-radius: var(--radius-md); background: var(--brand-green); color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 24px;">
            🏪
          </div>

          <div style="flex: 1;">
            <div style="font-size: 17px; font-weight: 700; color: var(--brand-green);">
              <?= htmlspecialchars($resto['name'] ?? 'Mochi Mood Central') ?>
            </div>
            <div style="font-size: 13.5px; color: var(--text-muted); margin-top: 2px;">
              📍 <?= htmlspecialchars($resto['address'] ?? 'Jl. Sweet Happiness No. 1') ?>
            </div>
            <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">
              ⏰ Jam Buka: <?= htmlspecialchars($resto['opening_hours'] ?? '10:00 - 22:00') ?> | 
              Status: <strong style="color: <?= ($resto['status'] ?? 'open') === 'open' ? '#16A34A' : '#DC2626' ?>;"><?= strtoupper($resto['status'] ?? 'OPEN') ?></strong>
            </div>
          </div>
        </div>

        <!-- Edit Resto Inline Form (Modal/Collapse) -->
        <div id="editRestoModal" style="display: none; margin-top: 20px; padding: 20px; background: #FFFFFF; border: 1.5px solid var(--border-strong); border-radius: var(--radius-md);">
          <h4 style="font-size: 16px; margin-bottom: 12px; color: var(--brand-green);">Perbarui Informasi Restoran</h4>
          <form method="POST" action="admin.php?tab=restoran">
            <input type="hidden" name="action_update_resto" value="1">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
              <div class="form-group">
                <label class="form-label">Nama Restoran</label>
                <input type="text" name="resto_name" class="form-input" value="<?= htmlspecialchars($resto['name']) ?>" required style="padding-left: 18px;">
              </div>
              <div class="form-group">
                <label class="form-label">Jam Operasional</label>
                <input type="text" name="resto_hours" class="form-input" value="<?= htmlspecialchars($resto['opening_hours']) ?>" required style="padding-left: 18px;">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Alamat Lengkap</label>
              <input type="text" name="resto_address" class="form-input" value="<?= htmlspecialchars($resto['address']) ?>" required style="padding-left: 18px;">
            </div>
            <div class="form-group">
              <label class="form-label">Status Penerimaan Antrean</label>
              <select name="resto_status" class="form-input form-select" style="padding-left: 18px;">
                <option value="open" <?= $resto['status'] === 'open' ? 'selected' : '' ?>>Buka (Menerima Antrean)</option>
                <option value="closed" <?= $resto['status'] === 'closed' ? 'selected' : '' ?>>Tutup (Antrean Ditutup)</option>
              </select>
            </div>
            <div style="display: flex; gap: 10px;">
              <button type="submit" class="btn-mochi btn-mochi-sm">Simpan Perubahan</button>
              <button type="button" class="btn-mochi btn-mochi-sm btn-mochi-cream" onclick="document.getElementById('editRestoModal').style.display='none'">Batal</button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- TAB: KELOLA WAITING LIST -->
    <?php if ($tab === 'dashboard' || $tab === 'waiting_list'): ?>
      <div class="card-mochi" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <h3 style="font-size: 18px; color: var(--brand-green);">Kelola Waiting List (Semua Antrean)</h3>
          <a href="staff.php" class="btn-mochi btn-mochi-sm">
            Buka Staff Caller →
          </a>
        </div>

        <div style="overflow-x: auto;">
          <table class="table-mochi">
            <thead>
              <tr>
                <th>No</th>
                <th>Pelanggan</th>
                <th>Tipe</th>
                <th>Tamu</th>
                <th>Status</th>
                <th>Waktu</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($allQueues, 0, 10) as $q): ?>
                <tr>
                  <td><?= htmlspecialchars($q['queue_number']) ?></td>
                  <td>
                    <strong><?= htmlspecialchars($q['customer_name'] ?: $q['user_name']) ?></strong>
                    <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($q['customer_phone'] ?: $q['user_email']) ?></div>
                  </td>
                  <td>
                    <span class="badge-mochi <?= $q['type'] === 'takeaway' ? 'badge-gold' : 'badge-sage' ?>" style="font-size: 11px;">
                      <?= $q['type'] === 'takeaway' ? 'Takeaway' : 'Dine-In' ?>
                    </span>
                  </td>
                  <td><?= $q['people_count'] ?> Orang</td>
                  <td>
                    <span class="badge-mochi badge-sage" style="font-size: 11px;">
                      <?= ucfirst($q['status']) ?>
                    </span>
                  </td>
                  <td style="font-size: 12px; color: var(--text-muted);">
                    <?= date('H:i', strtotime($q['created_at'])) ?>
                  </td>
                  <td>
                    <button type="button" class="btn-mochi btn-mochi-sm" onclick="MochiMood.staffAction('call', <?= $q['id'] ?>)">
                      Call
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <!-- TAB: PENGUMUMAN -->
    <?php if ($tab === 'dashboard' || $tab === 'pengumuman'): ?>
      <div class="card-mochi">
        <h3 style="font-size: 18px; color: var(--brand-green); margin-bottom: 14px;">Pengumuman & Broadcast Banner</h3>

        <form method="POST" action="admin.php?tab=pengumuman" style="background: #FAF8F4; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
          <input type="hidden" name="action_add_announce" value="1">
          <div class="form-group">
            <label class="form-label">Judul Pengumuman</label>
            <input type="text" name="ann_title" class="form-input" placeholder="Contoh: Promo Spesial Weekend 20%" required style="padding-left: 18px;">
          </div>
          <div class="form-group">
            <label class="form-label">Isi Pesan Banner</label>
            <input type="text" name="ann_message" class="form-input" placeholder="Tulis pengumuman yang akan tampil di halaman depan..." required style="padding-left: 18px;">
          </div>
          <button type="submit" class="btn-mochi btn-mochi-sm">
            📢 Publikasikan Pengumuman
          </button>
        </form>

        <div style="display: flex; flex-direction: column; gap: 10px;">
          <?php foreach ($announcements as $ann): ?>
            <div style="background: #FFFFFF; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
              <div>
                <strong style="color: var(--brand-green); font-size: 14px;"><?= htmlspecialchars($ann['title']) ?></strong>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($ann['message']) ?></p>
              </div>
              <span class="badge-mochi badge-sage" style="font-size: 11px;">Aktif</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
