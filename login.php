<?php
/**
 * ============================================================================
 * MOCHI MOOD BAKERY – Login Screen
 * File: login.php
 * Replicates Screen 2 from the UI collection
 * ============================================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/helpers/Auth.php';

Auth::start();

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Akun berhasil dibuat! Silakan login.';
}

// Quick Login Demo Trigger
if (isset($_GET['quick'])) {
    $userModel = new User();
    $targetRole = $_GET['quick']; // 'admin', 'staff', 'customer'
    $users = $userModel->getAll($targetRole);
    if (!empty($users)) {
        Auth::login($users[0]);
        if ($targetRole === 'admin') header('Location: admin.php');
        elseif ($targetRole === 'staff') header('Location: staff.php');
        else header('Location: queue.php');
        exit;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $userModel = new User();
        $user = $userModel->getByEmail($email);

        if ($user && $userModel->verifyPassword($password, $user['password'])) {
            Auth::login($user);
            if ($user['role'] === 'admin') {
                header('Location: admin.php');
            } elseif ($user['role'] === 'staff') {
                header('Location: staff.php');
            } else {
                header('Location: queue.php');
            }
            exit;
        } else {
            $error = 'Email atau password tidak sesuai.';
        }
    }
}

$pageTitle = 'Login – Mochi Mood';
include __DIR__ . '/includes/header.php';
?>

<div style="padding: 40px 16px 60px; min-height: calc(100vh - 50px); display: flex; align-items: center; justify-content: center; flex-direction: column;">
  <div class="mobile-device-shell">
    <div class="mobile-notch"><div class="mobile-speaker"></div></div>
    
    <div class="mobile-screen-content">
      <!-- Organic Top Wave Blob -->
      <div class="mobile-top-blob"></div>

      <!-- Header Bar -->
      <div class="mobile-header-bar">
        <a href="index.php" class="mobile-brand-title">Mochi<br>Mood</a>
      </div>

      <div class="mobile-body">
        <div style="margin: 20px 0 24px;">
          <h2 class="font-serif" style="font-size: 26px; color: var(--brand-green); margin-bottom: 4px;">Welcome back!</h2>
          <p style="font-size: 13.5px; color: var(--text-muted);">Login to continue your queue</p>
        </div>

        <?php if ($error): ?>
          <div style="background: #FDE8E8; color: #9B1C1C; padding: 10px 14px; border-radius: 12px; font-size: 13px; margin-bottom: 16px;">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div style="background: #DEF7EC; color: #03543F; padding: 10px 14px; border-radius: 12px; font-size: 13px; margin-bottom: 16px;">
            <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
          <!-- Email Address -->
          <div class="form-group">
            <div class="input-icon-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
              </span>
              <input type="email" name="email" class="form-input" placeholder="Email Address" required value="<?= htmlspecialchars($_POST['email'] ?? 'budi@gmail.com') ?>">
            </div>
          </div>

          <!-- Password -->
          <div class="form-group">
            <div class="input-icon-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              </span>
              <input type="password" id="login_pass" name="password" class="form-input" placeholder="Password" required value="user123">
              <button type="button" class="input-toggle" onclick="MochiMood.togglePassword('login_pass', this)">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn-mochi btn-mochi-block" style="margin-top: 10px;">
            Login
          </button>
        </form>

        <p style="text-align: center; font-size: 13px; color: var(--text-muted); margin-top: 18px;">
          Don't have an account? <a href="register.php" style="color: var(--brand-green); font-weight: 700;">Register here</a>
        </p>

        <!-- Fast Demo Login Pills -->
        <div style="margin-top: 20px; padding: 10px; background: rgba(34, 56, 38, 0.05); border-radius: 14px; text-align: center;">
          <div style="font-size: 11px; font-weight: 700; color: var(--brand-green); margin-bottom: 6px; text-transform: uppercase;">
            ⚡ Quick 1-Click Access
          </div>
          <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
            <a href="login.php?quick=customer" class="badge-mochi badge-sage" style="font-size: 11px;">Customer</a>
            <a href="login.php?quick=staff" class="badge-mochi badge-gold" style="font-size: 11px;">Staff</a>
            <a href="login.php?quick=admin" class="badge-mochi badge-pink" style="font-size: 11px;">Admin</a>
          </div>
        </div>

        <!-- Bottom decorative donut -->
        <div class="mobile-bottom-decor" style="margin-top: 18px;">
          <img src="images/donuts/choco-bliss.jpg" alt="Mochi Donut">
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
