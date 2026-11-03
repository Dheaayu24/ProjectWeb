<?php
/**
 * View: Halaman Registrasi
 * File: app/views/auth/register.php
 * Variabel dari Controller: $error (string|null)
 */
$title = 'Daftar Akun – Mochi Mood';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container" style="padding: 50px 24px 80px;">
  <div style="max-width: 440px; margin: 0 auto;">
    <div class="card" style="padding: 36px 30px; text-align: center;">
      <div style="font-size: 32px; margin-bottom: 12px;">✨</div>
      <h1 style="font-size: 26px; margin-bottom: 4px;">Daftar Akun</h1>
      <p style="font-size: 13.5px; color: var(--dark-olive); margin-bottom: 24px;">Buat akun untuk mengambil dan memantau antrean Mochi Mood.</p>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
          <span>⚠️</span>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="index.php?url=auth/register">
        <div class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="name" class="form-control" placeholder="Nama Anda" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Kata Sandi</label>
          <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
        </div>

        <div class="form-group">
          <label class="form-label">Konfirmasi Kata Sandi</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi kata sandi" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
          Daftar
        </button>
      </form>

      <p style="font-size: 13.5px; color: var(--dark-olive); margin-top: 20px;">
        Sudah punya akun? <a href="index.php?url=auth/login" style="color: var(--deep-forest-green); font-weight: 700; text-decoration: underline;">Masuk di sini</a>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
