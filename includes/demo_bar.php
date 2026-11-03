<?php
/**
 * Shared Demo Top Navigation Switcher
 * Allows 1-click access to all 8 screens and the Panorama Collection
 */
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<div class="demo-top-bar" id="mochiDemoBar">
  <div class="demo-bar-inner">
    <div class="demo-brand-tag">
      <span>🍩</span>
      <strong>Mochi Mood Showcase Navigator</strong>
    </div>
    <div class="demo-nav-links">
      <a href="showcase.php" class="demo-link-pill <?= $currentPage === 'showcase.php' ? 'active' : '' ?>">✨ UI Collection (All 8 Screens)</a>
      <a href="index.php" class="demo-link-pill <?= $currentPage === 'index.php' ? 'active' : '' ?>">🏠 Landing</a>
      <a href="login.php" class="demo-link-pill <?= $currentPage === 'login.php' ? 'active' : '' ?>">🔑 Login</a>
      <a href="register.php" class="demo-link-pill <?= $currentPage === 'register.php' ? 'active' : '' ?>">📝 Register</a>
      <a href="queue.php" class="demo-link-pill <?= $currentPage === 'queue.php' ? 'active' : '' ?>">🎫 Join Queue</a>
      <a href="ticket.php?number=A023" class="demo-link-pill <?= $currentPage === 'ticket.php' ? 'active' : '' ?>">🏷️ Ticket (A023)</a>
      <a href="track.php?number=A023" class="demo-link-pill <?= $currentPage === 'track.php' ? 'active' : '' ?>">⏱️ Live Radar (A023)</a>
      <a href="staff.php" class="demo-link-pill <?= $currentPage === 'staff.php' ? 'active' : '' ?>">🧑‍🍳 Staff</a>
      <a href="admin.php" class="demo-link-pill <?= $currentPage === 'admin.php' ? 'active' : '' ?>">👑 Admin</a>
    </div>
  </div>
</div>

