<?php
/**
 * ============================================================================
 * MOCHI MOOD BAKERY – Registration Screen
 * File: register.php
 * Replicates Screen 3 from the UI collection
 * ============================================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/helpers/Auth.php';

Auth::start();

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        try {
            $userModel = new User();
            $newId = $userModel->create([
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'password' => $password,
                'role'     => 'customer'
            ]);

            $newUser = $userModel->getById($newId);
            Auth::login($newUser);
            header('Location: queue.php');
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Create Your Account – Mochi Mood';
include __DIR__ . '/includes/header.php';
?>

<div style="padding: 40px 16px 60px; min-height: calc(100vh - 50px); display: flex; align-items: center; justify-content: center; flex-direction: column;">
  <div class="mobile-device-shell" style="height: 740px;">
    <div class="mobile-notch"><div class="mobile-speaker"></div></div>
    
    <div class="mobile-screen-content">
      <!-- Organic Top Wave Blob -->
      <div class="mobile-top-blob" style="width: 150px; height: 150px;"></div>

      <!-- Header Bar -->
      <div class="mobile-header-bar">
        <a href="index.php" class="mobile-brand-title">Mochi<br>Mood</a>
      </div>

      <div class="mobile-body">
        <div style="margin: 14px 0 20px;">
          <h2 class="font-serif" style="font-size: 24px; color: var(--brand-green); margin-bottom: 2px;">Create Your Account</h2>
          <p style="font-size: 13px; color: var(--text-muted);">Join the waiting list with ease!</p>
        </div>

        <?php if ($error): ?>
          <div style="background: #FDE8E8; color: #9B1C1C; padding: 8px 12px; border-radius: 12px; font-size: 12.5px; margin-bottom: 14px;">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
          <!-- Full Name -->
          <div class="form-group" style="margin-bottom: 12px;">
            <div class="input-icon-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              </span>
              <input type="text" name="name" class="form-input" placeholder="Full Name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
          </div>

          <!-- Email Address -->
          <div class="form-group" style="margin-bottom: 12px;">
            <div class="input-icon-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
              </span>
              <input type="email" name="email" class="form-input" placeholder="Email Address" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
          </div>

          <!-- Phone Number -->
          <div class="form-group" style="margin-bottom: 12px;">
            <div class="input-icon-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
              </span>
              <input type="tel" name="phone" class="form-input" placeholder="Phone Number" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
          </div>

          <!-- Password -->
          <div class="form-group" style="margin-bottom: 12px;">
            <div class="input-icon-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              </span>
              <input type="password" id="reg_pass" name="password" class="form-input" placeholder="Password" required>
              <button type="button" class="input-toggle" onclick="MochiMood.togglePassword('reg_pass', this)">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="form-group" style="margin-bottom: 16px;">
            <div class="input-icon-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              </span>
              <input type="password" id="reg_confirm" name="confirm_password" class="form-input" placeholder="Confirm Password" required>
            </div>
          </div>

          <!-- Register Button -->
          <button type="submit" class="btn-mochi btn-mochi-block">
            Register
          </button>
        </form>

        <p style="text-align: center; font-size: 13px; color: var(--text-muted); margin-top: 14px;">
          Already have an account? <a href="login.php" style="color: var(--brand-green); font-weight: 700;">Login here</a>
        </p>

        <!-- Bottom decorative donut -->
        <div class="mobile-bottom-decor" style="margin-top: 14px;">
          <img src="https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=300&q=80" alt="Mochi Donut">
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
