<?php
/**
 * Layout Navbar
 * File: app/views/layouts/navbar.php
 */
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::start();

$user = Auth::user();
$currentUrl = $_GET['url'] ?? 'home';
?>
<nav class="navbar">
  <div class="container navbar-inner">
    <!-- Brand Logo Mochi Mood -->
    <a href="index.php?url=home" class="brand">
      <div class="brand-badge">🍩</div>
      <div>
        <div class="brand-name">Mochi Mood</div>
        <div class="brand-tagline">A Little Mochi, A Lot of Happiness ♡</div>
      </div>
    </a>

    <!-- Nav Menu -->
    <ul class="nav-menu">
      <li>
        <a href="index.php?url=home" class="nav-link <?= ($currentUrl === 'home' || $currentUrl === '') ? 'active' : '' ?>">
          Beranda
        </a>
      </li>
      <li>
        <a href="index.php?url=restaurants/detail" class="nav-link <?= $currentUrl === 'restaurants/detail' ? 'active' : '' ?>">
          Cafe & Menu
        </a>
      </li>
      <li>
        <a href="index.php?url=queues/create" class="nav-link <?= $currentUrl === 'queues/create' ? 'active' : '' ?>">
          Ambil Antrean
        </a>
      </li>
      <?php if ($user && in_array($user['role'], ['staff', 'admin'])): ?>
        <li>
          <a href="index.php?url=dashboard/staff" class="nav-link <?= $currentUrl === 'dashboard/staff' ? 'active' : '' ?>">
            Petugas
          </a>
        </li>
      <?php endif; ?>
      <?php if ($user && $user['role'] === 'admin'): ?>
        <li>
          <a href="index.php?url=dashboard/admin" class="nav-link <?= $currentUrl === 'dashboard/admin' ? 'active' : '' ?>">
            Admin
          </a>
        </li>
      <?php endif; ?>
    </ul>

    <!-- Auth Actions -->
    <div style="display: flex; align-items: center; gap: 12px;">
      <?php if ($user): ?>
        <span class="badge badge-sage">
          👤 <?= htmlspecialchars($user['name']) ?>
        </span>
        <a href="index.php?url=auth/logout" class="btn btn-secondary btn-sm" title="Keluar">
          Keluar
        </a>
      <?php else: ?>
        <a href="index.php?url=auth/login" class="btn btn-secondary btn-sm">
          Masuk
        </a>
        <a href="index.php?url=queues/create" class="btn btn-primary btn-sm">
          Mulai Antre
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>
