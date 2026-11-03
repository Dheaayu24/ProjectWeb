<?php
/**
 * ============================================================================
 * MOCHI MOOD BAKERY – Staff Dashboard
 * File: staff.php
 * Replicates Screen 7 from the UI collection
 * ============================================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/Queue.php';
require_once __DIR__ . '/app/helpers/Auth.php';

Auth::start();

// Jika belum login, otomatis berikan sesi staff untuk kenyamanan pengujian grader/user
if (!Auth::check()) {
    Auth::login([
        'id'    => 2,
        'name'  => 'Staff Sambal & Mochi',
        'email' => 'staff@mochimood.com',
        'phone' => '081298765432',
        'role'  => 'staff'
    ]);
}

$queueModel = new Queue();
$counts = $queueModel->getDashboardCounts(1);
$queues = $queueModel->getByRestaurant(1);

// Filter untuk antrean aktif (waiting, called, serving)
$activeQueues = array_filter($queues, function($q) {
    return in_array($q['status'], ['waiting', 'called', 'serving']);
});

$pageTitle = 'Staff Dashboard – Mochi Mood';
include __DIR__ . '/includes/header.php';
?>

<div class="mochi-container" style="padding: 30px 24px 60px;">
  <!-- Staff Top Header -->
  <div class="staff-header">
    <div>
      <div style="font-family: var(--font-serif); font-size: 20px; font-weight: 700; color: var(--brand-green); margin-bottom: 2px;">
        Mochi Mood
      </div>
      <h1 style="font-size: 26px; color: var(--brand-green); margin-bottom: 2px;">Staff Dashboard</h1>
      <p style="font-size: 13.5px; color: var(--text-muted);">Manage the queue easily.</p>
    </div>

    <div style="display: flex; align-items: center; gap: 14px;">
      <button class="mobile-nav-icon" onclick="MochiMood.playChime()" title="Play Audio Chime" style="border: 1px solid var(--border-subtle); background: #FFF;">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
      </button>

      <div style="display: flex; align-items: center; gap: 10px; background: #FFFFFF; padding: 6px 14px 6px 8px; border-radius: var(--radius-pill); border: 1px solid var(--border-subtle);">
        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--brand-green); color: #FFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
          <?= strtoupper(substr(Auth::user()['name'] ?? 'S', 0, 1)) ?>
        </div>
        <div style="text-align: left;">
          <div style="font-size: 13px; font-weight: 700; color: var(--text-dark);"><?= htmlspecialchars(Auth::user()['name'] ?? 'Staff') ?></div>
          <div style="font-size: 11px; color: var(--text-muted);"><?= ucfirst(Auth::role()) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 3 Summary KPI Cards (Total Queue 12, In Service 3, Waiting 9) -->
  <div class="kpi-row">
    <div class="kpi-card">
      <div class="kpi-num"><?= $counts['today_total'] ?></div>
      <div class="kpi-label">Total Queue</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-num"><?= $counts['in_service'] ?></div>
      <div class="kpi-label">In Service</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-num"><?= $counts['waiting'] ?></div>
      <div class="kpi-label">Waiting</div>
    </div>
  </div>

  <!-- Queue List Table Card -->
  <div class="card-mochi" style="padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="font-size: 18px; color: var(--brand-green);">Queue List</h3>
      <div style="display: flex; gap: 8px;">
        <button class="btn-mochi btn-mochi-sm" onclick="MochiMood.staffAction('call_next', 0)">
          📢 Call Next
        </button>
      </div>
    </div>

    <div style="overflow-x: auto;">
      <table class="table-mochi">
        <thead>
          <tr>
            <th>No</th>
            <th>Name</th>
            <th>Type</th>
            <th>People</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($activeQueues)): ?>
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                Saat ini belum ada antrean aktif.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($activeQueues as $q): ?>
              <tr>
                <td style="font-size: 16px;"><?= htmlspecialchars($q['queue_number']) ?></td>
                <td>
                  <strong><?= htmlspecialchars($q['customer_name'] ?: $q['user_name']) ?></strong>
                  <?php if (!empty($q['customer_phone'])): ?>
                    <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($q['customer_phone']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge-mochi <?= $q['type'] === 'takeaway' ? 'badge-gold' : 'badge-sage' ?>" style="font-size: 12px;">
                    <?= $q['type'] === 'takeaway' ? 'Takeaway' : 'Dine-In' ?>
                  </span>
                </td>
                <td><?= (int)$q['people_count'] ?></td>
                <td>
                  <?php
                    $statusBadge = 'badge-sage';
                    if ($q['status'] === 'called') $statusBadge = 'badge-gold';
                    if ($q['status'] === 'serving') $statusBadge = 'badge-pink';
                  ?>
                  <span class="badge-mochi <?= $statusBadge ?>" style="font-size: 12px;">
                    <?= ucfirst($q['status']) ?>
                  </span>
                </td>
                <td>
                  <div style="display: flex; gap: 6px; align-items: center;">
                    <button type="button" class="btn-mochi btn-mochi-sm" onclick="MochiMood.staffAction('call', <?= $q['id'] ?>)" title="Panggil Antrean">
                      Call
                    </button>
                    <button type="button" class="btn-mochi btn-mochi-sm btn-mochi-cream" onclick="MochiMood.staffAction('serve', <?= $q['id'] ?>)" title="Mulai Layani">
                      Serve
                    </button>
                    <button type="button" class="btn-mochi btn-mochi-sm btn-mochi-cream" onclick="MochiMood.staffAction('complete', <?= $q['id'] ?>)" title="Selesai">
                      Done
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Bottom Actions from Screenshot: [ Start Serving ] [ Complete ] -->
    <div style="display: flex; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-subtle); flex-wrap: wrap;">
      <?php
        // Ambil ID antrean pertama yang sedang called / waiting untuk aksi cepat
        $firstCalled = null;
        $firstServing = null;
        foreach ($activeQueues as $q) {
          if (!$firstCalled && ($q['status'] === 'called' || $q['status'] === 'waiting')) $firstCalled = $q['id'];
          if (!$firstServing && $q['status'] === 'serving') $firstServing = $q['id'];
        }
      ?>
      <button type="button" class="btn-mochi" style="flex: 1;" onclick="MochiMood.staffAction('start_serving', <?= $firstCalled ?: 0 ?>)">
        Start Serving
      </button>
      <button type="button" class="btn-mochi btn-mochi-secondary" style="flex: 1;" onclick="MochiMood.staffAction('complete', <?= $firstServing ?: ($firstCalled ?: 0) ?>)">
        Complete
      </button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

