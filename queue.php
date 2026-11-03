<?php
/**
 * ============================================================================
 * MOCHI MOOD BAKERY – Join Waiting List Screen
 * File: queue.php
 * Replicates Screen 4 from the UI collection
 * ============================================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/Queue.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/helpers/Auth.php';

Auth::start();

$user = Auth::user();
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name   = trim($_POST['customer_name'] ?? ($user['name'] ?? ''));
    $phone  = trim($_POST['customer_phone'] ?? ($user['phone'] ?? ''));
    $people = max(1, (int)($_POST['people_count'] ?? 1));
    $type   = $_POST['type'] ?? 'dine_in';

    if (empty($name)) {
        $error = 'Nama pemesan wajib diisi.';
    } else {
        try {
            $queueModel = new Queue();
            $userId = Auth::id() ?: 3; // Fallback ke customer ID default jika guest

            // Tambahkan antrean baru
            $queueId = $queueModel->create([
                'user_id'        => $userId,
                'restaurant_id'  => 1,
                'customer_name'  => $name,
                'customer_phone' => $phone,
                'people_count'   => $people,
                'type'           => $type,
                'status'         => 'waiting'
            ]);

            $created = $queueModel->getById($queueId);
            $qNumber = $created['queue_number'] ?? 'A023';

            // Redirect ke Ticket Screen
            header('Location: ticket.php?number=' . urlencode($qNumber));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Join Waiting List – Mochi Mood';
include __DIR__ . '/includes/header.php';
?>

<div style="padding: 40px 16px 60px; min-height: calc(100vh - 50px); display: flex; align-items: center; justify-content: center; flex-direction: column;">
  <div class="mobile-device-shell" style="height: 720px;">
    <div class="mobile-notch"><div class="mobile-speaker"></div></div>
    
    <div class="mobile-screen-content">
      <!-- Header Bar -->
      <div class="mobile-header-bar">
        <a href="index.php" class="mobile-brand-title">Mochi<br>Mood</a>
        <a href="index.php" class="mobile-nav-icon" title="Menu">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </a>
      </div>

      <div class="mobile-body">
        <div style="margin: 10px 0 20px; text-align: center;">
          <h2 class="font-serif" style="font-size: 26px; color: var(--brand-green); margin-bottom: 4px;">Join Waiting List</h2>
          <p style="font-size: 13.5px; color: var(--text-muted);">Get your queue number now!</p>
        </div>

        <?php if ($error): ?>
          <div style="background: #FDE8E8; color: #9B1C1C; padding: 10px 14px; border-radius: 12px; font-size: 13px; margin-bottom: 16px;">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- Dine-In / Takeaway Switcher Tabs -->
        <div class="queue-type-toggle">
          <button type="button" class="queue-type-btn active" id="btn_type_dine" onclick="MochiMood.setType('dine_in')">
            Dine-In
          </button>
          <button type="button" class="queue-type-btn" id="btn_type_takeaway" onclick="MochiMood.setType('takeaway')">
            Takeaway
          </button>
        </div>

        <form method="POST" action="queue.php">
          <input type="hidden" name="type" id="queue_type_input" value="dine_in">

          <!-- Full Name -->
          <div class="form-group">
            <div class="input-icon-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              </span>
              <input type="text" name="customer_name" class="form-input" placeholder="Full Name" required value="<?= htmlspecialchars($user['name'] ?? 'Khansa') ?>">
            </div>
          </div>

          <!-- Phone Number -->
          <div class="form-group">
            <div class="input-icon-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
              </span>
              <input type="tel" name="customer_phone" class="form-input" placeholder="Phone Number" value="<?= htmlspecialchars($user['phone'] ?? '081233445566') ?>">
            </div>
          </div>

          <!-- Number of People -->
          <div class="form-group">
            <div class="input-icon-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
              </span>
              <select name="people_count" class="form-input form-select">
                <option value="1">1 Person</option>
                <option value="2" selected>2 People</option>
                <option value="3">3 People</option>
                <option value="4">4 People</option>
                <option value="5">5 People</option>
                <option value="6">6+ People</option>
              </select>
            </div>
          </div>

          <!-- Join Queue Button -->
          <button type="submit" class="btn-mochi btn-mochi-block" style="margin-top: 14px;">
            Join Queue
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </button>
        </form>

        <!-- Bottom visual donuts -->
        <div class="mobile-bottom-decor" style="margin-top: 24px;">
          <img src="images/donuts/hero-platter.jpg" alt="Mochi Donuts">
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
