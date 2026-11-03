<?php
/**
 * View: Halaman Login
 * File: app/views/auth/login.php
 * Variabel dari Controller: $error (string|null), $success (string|null)
 */
$title = 'Masuk – Mochi Mood';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container" style="padding: 50px 24px 80px;">
  <div style="max-width: 420px; margin: 0 auto;">
    <div class="card" style="padding: 36px 30px; text-align: center;">
      <div style="font-size: 32px; margin-bottom: 12px;">🍩</div>
      <h1 style="font-size: 26px; margin-bottom: 4px;">Selamat Datang</h1>
      <p style="font-size: 13.5px; color: var(--dark-olive); margin-bottom: 24px;">Masuk untuk memantau nomor antrean Mochi Mood Anda.</p>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
          <span>⚠️</span>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success">
          <span>✅</span>
          <span><?= htmlspecialchars($success) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="index.php?url=auth/login">
        <?= Csrf::field() ?>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? 'budi@gmail.com') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Kata Sandi</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required value="user123">
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
          Masuk
        </button>
      </form>

      <p style="font-size: 13.5px; color: var(--dark-olive); margin-top: 20px;">
        Belum punya akun? <a href="index.php?url=auth/register" style="color: var(--deep-forest-green); font-weight: 700; text-decoration: underline;">Daftar di sini</a>
      </p>

      <!-- Akses Cepat Uji Coba Demo -->
      <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color); font-size: 12px; color: var(--dark-olive);">
        <div style="font-weight: 600; margin-bottom: 8px;">Akses Cepat Pengujian:</div>
        <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
          <a href="index.php?url=auth/quick-login&role=customer" class="badge badge-sage">Pelanggan</a>
          <a href="index.php?url=auth/quick-login&role=staff" class="badge badge-olive">Petugas</a>
          <a href="index.php?url=auth/quick-login&role=admin" class="badge badge-called">Admin</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
