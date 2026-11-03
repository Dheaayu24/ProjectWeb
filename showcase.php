<?php
/**
 * ============================================================================
 * MOCHI MOOD BAKERY – Master UI Collection Showcase
 * File: showcase.php
 * Replicates the exact layout of "Mochi Mood Bakery UI Collection.png"
 * ============================================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/Queue.php';
require_once __DIR__ . '/app/helpers/Auth.php';

Auth::start();

$queueModel = new Queue();
$counts = $queueModel->getDashboardCounts(1);
$queues = $queueModel->getByRestaurant(1);
$waitingQueues = array_slice(array_filter($queues, fn($q) => $q['status'] === 'waiting'), 0, 3);

$pageTitle = 'Mochi Mood Bakery UI Collection – All-In-One Showcase';
include __DIR__ . '/includes/header.php';
?>

<div class="showcase-board">
  <!-- Top Showcase Header -->
  <div class="showcase-header">
    <div class="badge-mochi badge-gold" style="margin-bottom: 10px;">
      ✨ Full Interactive UI Collection Preview
    </div>
    <h1 class="showcase-title">Mochi Mood Bakery UI Collection</h1>
    <p style="color: var(--text-muted); font-size: 15px; max-width: 600px; margin: 6px auto 0;">
      Koleksi antarmuka lengkap Mochi Mood: Landing page, Mobile Auth, Sistem Antrean Real-Time, Staff & Admin Dashboard.
    </p>
  </div>

  <!-- Master Grid: Matches uploaded collection layout -->
  <div class="showcase-grid">

    <!-- ===================================================================
         COLUMN 1: LANDING PAGE (DESKTOP UI)
         =================================================================== -->
    <div style="background: #FFFFFF; border: 2px solid var(--border-subtle); border-radius: 28px; padding: 20px; box-shadow: var(--shadow-md); overflow: hidden;">
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px; margin-bottom: 16px;">
        <span class="badge-mochi badge-sage" style="font-size: 11.5px;">🖥️ 1. Landing Page (Desktop)</span>
        <a href="index.php" target="_blank" class="btn-mochi btn-mochi-sm" style="font-size: 11px; padding: 4px 10px;">Open Full Page ↗</a>
      </div>

      <!-- Mini Desktop Shell Preview -->
      <div style="background: var(--bg-cream); border-radius: 18px; padding: 18px; border: 1px solid var(--border-subtle);">
        <!-- Mini Navbar -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <div class="font-serif" style="font-weight: 700; color: var(--brand-green); font-size: 16px; line-height: 1;">
            Mochi<br>Mood
          </div>
          <div style="font-size: 11px; color: var(--text-muted); display: flex; gap: 8px;">
            <span>Home</span><span>Menu</span><span>About</span>
          </div>
          <a href="queue.php" class="btn-mochi btn-mochi-sm" style="font-size: 10px; padding: 4px 10px;">Join Waiting List</a>
        </div>

        <!-- Mini Hero -->
        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 20px;">
          <div style="flex: 1.2;">
            <h3 class="font-serif" style="font-size: 18px; color: var(--brand-green); line-height: 1.2;">
              A Little Mochi,<br>A Lot of Happiness ♡
            </h3>
            <p style="font-size: 10.5px; color: var(--text-muted); margin: 6px 0 10px;">
              Premium mochi donuts, always fresh, now with our smart queue system!
            </p>
            <a href="queue.php" class="btn-mochi btn-mochi-sm" style="font-size: 10px; padding: 5px 12px;">Join Waiting List →</a>
          </div>
          <div style="flex: 0.8; height: 110px; border-radius: 50%; overflow: hidden; background: #EAE3D4;">
            <img src="images/donuts/hero-platter.jpg" style="width: 100%; height: 100%; object-fit: cover;">
          </div>
        </div>

        <!-- Mini How It Works -->
        <div style="background: #FFF; border-radius: 14px; padding: 12px; margin-bottom: 16px; border: 1px solid var(--border-subtle); text-align: center;">
          <div style="font-size: 12px; font-weight: 700; color: var(--brand-green); margin-bottom: 8px;">How It Works?</div>
          <div style="display: flex; justify-content: space-around; font-size: 9.5px; color: var(--text-dark);">
            <div>💻<br>Daftar<br>Waiting List</div>
            <div style="color: var(--text-muted);">→</div>
            <div>⌛<br>Dapatkan<br>Nomor Antrean</div>
            <div style="color: var(--text-muted);">→</div>
            <div>🔔<br>Pantau Antrean<br>Real-Time</div>
          </div>
        </div>

        <!-- Mini Menu -->
        <div style="margin-bottom: 16px;">
          <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; color: var(--brand-green); margin-bottom: 8px;">
            <span>Our Favorite Menu</span>
            <span style="font-size: 10px; color: var(--text-muted);">Lihat Semua →</span>
          </div>
          <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; text-align: center;">
            <div style="background: #FFF; padding: 6px; border-radius: 10px; font-size: 9px; font-weight: 600;">
              <img src="images/donuts/matcha-crunch.jpg" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-bottom: 4px;">
              <div>Matcha</div>
            </div>
            <div style="background: #FFF; padding: 6px; border-radius: 10px; font-size: 9px; font-weight: 600;">
              <img src="images/donuts/choco-bliss.jpg" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-bottom: 4px;">
              <div>Choco</div>
            </div>
            <div style="background: #FFF; padding: 6px; border-radius: 10px; font-size: 9px; font-weight: 600;">
              <img src="images/donuts/strawberry-cloud.jpg" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-bottom: 4px;">
              <div>Berry</div>
            </div>
            <div style="background: #FFF; padding: 6px; border-radius: 10px; font-size: 9px; font-weight: 600;">
              <img src="images/donuts/lotus-dream.jpg" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-bottom: 4px;">
              <div>Lotus</div>
            </div>
          </div>
        </div>

        <!-- Mini Why Mochi Mood -->
        <div style="background: var(--brand-green); color: #FFF; border-radius: 14px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
          <div style="font-size: 10px; line-height: 1.5;">
            <div style="font-weight: 700; font-size: 11px; margin-bottom: 4px;">Why Mochi Mood?</div>
            <div>💎 Premium Quality Donut</div>
            <div>⚡ Fast & Easy Queue</div>
            <div>🧁 Freshly Made Daily</div>
          </div>
          <img src="images/donuts/choco-bliss.jpg" style="width: 60px; height: 60px; border-radius: 10px; object-fit: cover;">
        </div>
      </div>
    </div>

    <!-- ===================================================================
         COLUMN 2: MOBILE SCREENS 1 & 4 (LOGIN & TICKET)
         =================================================================== -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

      <!-- Screen 2: Welcome Back! (Login) -->
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span class="badge-mochi badge-sage" style="font-size: 11px;">📱 2. Welcome back! (Login)</span>
          <a href="login.php" target="_blank" style="font-size: 11px; font-weight: 600; color: var(--brand-green);">Open ↗</a>
        </div>
        
        <div class="mobile-device-shell" style="height: 480px; width: 100%; max-width: 310px;">
          <div class="mobile-notch" style="height: 12px; width: 90px;"><div class="mobile-speaker"></div></div>
          <div class="mobile-screen-content" style="padding-top: 14px;">
            <div class="mobile-top-blob" style="width: 90px; height: 90px;"></div>
            <div class="mobile-header-bar" style="padding: 10px 14px 4px;">
              <span class="mobile-brand-title" style="font-size: 15px;">Mochi<br>Mood</span>
            </div>
            <div class="mobile-body" style="padding: 8px 16px;">
              <h3 class="font-serif" style="font-size: 18px; color: var(--brand-green); margin-bottom: 2px;">Welcome back!</h3>
              <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 12px;">Login to continue your queue</p>
              
              <div class="form-group" style="margin-bottom: 8px;">
                <input type="text" class="form-input" placeholder="Email Address" value="budi@gmail.com" readonly style="padding: 8px 12px 8px 32px; font-size: 12px;">
              </div>
              <div class="form-group" style="margin-bottom: 10px;">
                <input type="password" class="form-input" placeholder="Password" value="••••••••" readonly style="padding: 8px 12px 8px 32px; font-size: 12px;">
              </div>
              <a href="login.php" class="btn-mochi btn-mochi-block" style="padding: 8px; font-size: 12px;">Login</a>
              <div style="font-size: 10.5px; text-align: center; color: var(--text-muted); margin-top: 8px;">
                Don't have an account? <span style="font-weight: 700; color: var(--brand-green);">Register here</span>
              </div>
              <div class="mobile-bottom-decor" style="margin-top: 10px;">
                <img src="images/donuts/choco-bliss.jpg" style="width: 75px; height: 75px;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Screen 5: Your Queue Number (Ticket) -->
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span class="badge-mochi badge-sage" style="font-size: 11px;">🏷️ 5. Your Queue Number (Ticket)</span>
          <a href="ticket.php?number=A023" target="_blank" style="font-size: 11px; font-weight: 600; color: var(--brand-green);">Open ↗</a>
        </div>

        <div class="mobile-device-shell" style="height: 480px; width: 100%; max-width: 310px;">
          <div class="mobile-notch" style="height: 12px; width: 90px;"><div class="mobile-speaker"></div></div>
          <div class="mobile-screen-content" style="padding-top: 14px;">
            <div class="mobile-header-bar" style="padding: 10px 14px 4px;">
              <span class="mobile-brand-title" style="font-size: 15px;">Mochi<br>Mood</span>
              <span style="font-size: 14px;">🏠</span>
            </div>
            <div class="mobile-body" style="padding: 8px 16px;">
              <div class="ticket-card" style="padding: 14px 10px; margin-bottom: 8px;">
                <div style="font-size: 11px; color: var(--text-muted);">Your Queue Number</div>
                <div class="ticket-number" style="font-size: 38px; margin: 6px 0;">A023</div>
                <div class="badge-mochi badge-sage" style="font-size: 10px; padding: 3px 8px; margin-bottom: 8px;">Dine-In • 2 People</div>
                
                <div class="ticket-stats-grid" style="gap: 4px; margin: 10px 0;">
                  <div class="ticket-stat-box" style="padding: 6px 2px;">
                    <div class="ticket-stat-label" style="font-size: 8.5px;">Position</div>
                    <div class="ticket-stat-val" style="font-size: 12px;">#5</div>
                  </div>
                  <div class="ticket-stat-box" style="padding: 6px 2px;">
                    <div class="ticket-stat-label" style="font-size: 8.5px;">Estimated Wait</div>
                    <div class="ticket-stat-val" style="font-size: 12px;">≈ 10 min</div>
                  </div>
                  <div class="ticket-stat-box" style="padding: 6px 2px;">
                    <div class="ticket-stat-label" style="font-size: 8.5px;">Status</div>
                    <div class="ticket-stat-val" style="font-size: 12px; color: #16A34A;">Waiting</div>
                  </div>
                </div>

                <a href="track.php?number=A023" class="btn-mochi btn-mochi-block" style="padding: 7px; font-size: 11.5px;">Track My Queue</a>
                <div style="font-size: 10px; color: var(--text-muted); margin-top: 6px;">Back to Home</div>
              </div>
              <div class="mobile-bottom-decor" style="margin-top: 6px;">
                <img src="images/donuts/lotus-dream.jpg" style="width: 75px; height: 75px;">
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ===================================================================
         COLUMN 3: MOBILE SCREENS 2 & 5 (REGISTER & TRACK MY QUEUE)
         =================================================================== -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

      <!-- Screen 3: Create Your Account (Register) -->
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span class="badge-mochi badge-sage" style="font-size: 11px;">📝 3. Create Your Account</span>
          <a href="register.php" target="_blank" style="font-size: 11px; font-weight: 600; color: var(--brand-green);">Open ↗</a>
        </div>

        <div class="mobile-device-shell" style="height: 480px; width: 100%; max-width: 310px;">
          <div class="mobile-notch" style="height: 12px; width: 90px;"><div class="mobile-speaker"></div></div>
          <div class="mobile-screen-content" style="padding-top: 14px;">
            <div class="mobile-top-blob" style="width: 90px; height: 90px;"></div>
            <div class="mobile-header-bar" style="padding: 10px 14px 4px;">
              <span class="mobile-brand-title" style="font-size: 15px;">Mochi<br>Mood</span>
            </div>
            <div class="mobile-body" style="padding: 8px 16px;">
              <h3 class="font-serif" style="font-size: 17px; color: var(--brand-green); margin-bottom: 2px;">Create Your Account</h3>
              <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 10px;">Join the waiting list with ease!</p>
              
              <div class="form-group" style="margin-bottom: 6px;">
                <input type="text" class="form-input" placeholder="Full Name" value="Khansa" readonly style="padding: 6px 10px 6px 30px; font-size: 11px;">
              </div>
              <div class="form-group" style="margin-bottom: 6px;">
                <input type="email" class="form-input" placeholder="Email Address" value="khansa@gmail.com" readonly style="padding: 6px 10px 6px 30px; font-size: 11px;">
              </div>
              <div class="form-group" style="margin-bottom: 6px;">
                <input type="tel" class="form-input" placeholder="Phone Number" value="081233445566" readonly style="padding: 6px 10px 6px 30px; font-size: 11px;">
              </div>
              <div class="form-group" style="margin-bottom: 10px;">
                <input type="password" class="form-input" placeholder="Password" value="••••••••" readonly style="padding: 6px 10px 6px 30px; font-size: 11px;">
              </div>
              <a href="register.php" class="btn-mochi btn-mochi-block" style="padding: 7px; font-size: 11.5px;">Register</a>
              <div style="font-size: 10px; text-align: center; color: var(--text-muted); margin-top: 6px;">
                Already have an account? <span style="font-weight: 700; color: var(--brand-green);">Login here</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Screen 6: Track My Queue (Dark Radar Live) -->
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span class="badge-mochi badge-gold" style="font-size: 11px;">⏱️ 6. Track My Queue (Radar)</span>
          <a href="track.php?number=A023" target="_blank" style="font-size: 11px; font-weight: 600; color: var(--brand-green);">Open ↗</a>
        </div>

        <div class="mobile-device-shell radar-screen" style="height: 480px; width: 100%; max-width: 310px; border-color: #122115;">
          <div class="mobile-notch" style="height: 12px; width: 90px; background: #122115;"><div class="mobile-speaker"></div></div>
          <div class="mobile-screen-content radar-screen" style="padding-top: 14px;">
            <div class="mobile-header-bar" style="padding: 10px 14px 4px;">
              <span class="mobile-brand-title" style="font-size: 15px; color: #FFF;">Mochi<br>Mood</span>
              <span style="font-size: 14px; color: #FFF;">🔄</span>
            </div>
            <div class="mobile-body radar-screen" style="padding: 8px 16px; text-align: center;">
              <!-- Radar Circle -->
              <div class="radar-gauge-wrap" style="width: 140px; height: 140px; margin: 10px auto;">
                <div class="radar-outer-ring"></div>
                <div class="radar-inner-ring" style="width: 120px; height: 120px;">
                  <span class="radar-queue-num" style="font-size: 28px;">A023</span>
                  <span class="radar-queue-pos" style="font-size: 10.5px;">#3 in Queue</span>
                </div>
              </div>

              <!-- Almost Your Turn Banner -->
              <div class="radar-pill-alert" style="font-size: 11px; padding: 6px 12px; margin: 6px auto;">
                🔔 Almost Your Turn!
              </div>

              <div style="margin: 6px 0;">
                <div style="font-size: 10px; color: rgba(255,255,255,0.7);">Estimated Time</div>
                <div style="font-size: 16px; font-weight: 700; color: #FFF;">≈ 5 min</div>
              </div>

              <div style="font-size: 10.5px; color: rgba(255,255,255,0.85); background: rgba(255,255,255,0.1); border-radius: 9999px; padding: 6px 12px; margin-top: 6px;">
                Please prepare to come soon ♡
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ===================================================================
         COLUMN 4: SCREEN 4 (JOIN QUEUE), SCREEN 7 & 8 (STAFF & ADMIN)
         =================================================================== -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

      <!-- Screen 4: Join Waiting List (Mobile Booking) -->
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span class="badge-mochi badge-sage" style="font-size: 11px;">🎫 4. Join Waiting List</span>
          <a href="queue.php" target="_blank" style="font-size: 11px; font-weight: 600; color: var(--brand-green);">Open ↗</a>
        </div>

        <div class="mobile-device-shell" style="height: 480px; width: 100%; max-width: 310px;">
          <div class="mobile-notch" style="height: 12px; width: 90px;"><div class="mobile-speaker"></div></div>
          <div class="mobile-screen-content" style="padding-top: 14px;">
            <div class="mobile-header-bar" style="padding: 10px 14px 4px;">
              <span class="mobile-brand-title" style="font-size: 15px;">Mochi<br>Mood</span>
              <span style="font-size: 14px;">☰</span>
            </div>
            <div class="mobile-body" style="padding: 8px 16px;">
              <h3 class="font-serif" style="font-size: 18px; color: var(--brand-green); margin-bottom: 2px; text-align: center;">Join Waiting List</h3>
              <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 10px; text-align: center;">Get your queue number now!</p>

              <div class="queue-type-toggle" style="margin-bottom: 10px; padding: 3px;">
                <div class="queue-type-btn active" style="padding: 6px; font-size: 11px;">Dine-In</div>
                <div class="queue-type-btn" style="padding: 6px; font-size: 11px;">Takeaway</div>
              </div>

              <div class="form-group" style="margin-bottom: 8px;">
                <input type="text" class="form-input" placeholder="Full Name" value="Khansa" readonly style="padding: 7px 10px 7px 30px; font-size: 11.5px;">
              </div>
              <div class="form-group" style="margin-bottom: 8px;">
                <input type="tel" class="form-input" placeholder="Phone Number" value="081233445566" readonly style="padding: 7px 10px 7px 30px; font-size: 11.5px;">
              </div>
              <div class="form-group" style="margin-bottom: 12px;">
                <select class="form-input form-select" style="padding: 7px 10px 7px 30px; font-size: 11.5px;">
                  <option selected>2 People</option>
                </select>
              </div>
              <a href="queue.php" class="btn-mochi btn-mochi-block" style="padding: 8px; font-size: 12px;">Join Queue →</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Screen 7: Staff Dashboard Mini Preview -->
      <div style="background: #FFFFFF; border: 1.5px solid var(--border-subtle); border-radius: 20px; padding: 16px; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <span class="badge-mochi badge-sage" style="font-size: 10.5px;">🧑‍🍳 7. Staff Dashboard</span>
          <a href="staff.php" target="_blank" style="font-size: 11px; font-weight: 600; color: var(--brand-green);">Open ↗</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-bottom: 10px;">
          <div class="kpi-card" style="padding: 8px 4px;">
            <div class="kpi-num" style="font-size: 18px;">12</div>
            <div class="kpi-label" style="font-size: 9.5px;">Total</div>
          </div>
          <div class="kpi-card" style="padding: 8px 4px;">
            <div class="kpi-num" style="font-size: 18px;">3</div>
            <div class="kpi-label" style="font-size: 9.5px;">In Service</div>
          </div>
          <div class="kpi-card" style="padding: 8px 4px;">
            <div class="kpi-num" style="font-size: 18px;">9</div>
            <div class="kpi-label" style="font-size: 9.5px;">Waiting</div>
          </div>
        </div>

        <!-- Mini Table Preview -->
        <div style="font-size: 10.5px; background: #FAF8F4; border-radius: 10px; padding: 8px; border: 1px solid var(--border-subtle);">
          <div style="display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid #EAE3D4; font-weight: 700;">
            <span>No</span><span>Name</span><span>Type</span><span>Action</span>
          </div>
          <div style="display: flex; justify-content: space-between; padding: 4px 0; align-items: center;">
            <strong style="color: var(--brand-green);">A022</strong>
            <span>Nabil A.</span>
            <span>Dine-In</span>
            <span class="btn-mochi" style="padding: 2px 8px; font-size: 9px;">Call</span>
          </div>
          <div style="display: flex; justify-content: space-between; padding: 4px 0; align-items: center;">
            <strong style="color: var(--brand-green);">A024</strong>
            <span>Budi S.</span>
            <span>Takeaway</span>
            <span class="btn-mochi" style="padding: 2px 8px; font-size: 9px;">Call</span>
          </div>
        </div>

        <div style="display: flex; gap: 6px; margin-top: 10px;">
          <a href="staff.php" class="btn-mochi btn-mochi-sm" style="flex: 1; font-size: 10.5px; padding: 6px;">Start Serving</a>
          <a href="staff.php" class="btn-mochi btn-mochi-sm btn-mochi-secondary" style="flex: 1; font-size: 10.5px; padding: 6px;">Complete</a>
        </div>
      </div>

      <!-- Screen 8: Admin Dashboard Mini Preview -->
      <div style="background: #FFFFFF; border: 1.5px solid var(--border-subtle); border-radius: 20px; padding: 16px; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <span class="badge-mochi badge-pink" style="font-size: 10.5px;">👑 8. Admin Dashboard</span>
          <a href="admin.php" target="_blank" style="font-size: 11px; font-weight: 600; color: var(--brand-green);">Open ↗</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; margin-bottom: 10px; text-align: center;">
          <div style="background: #FAF8F4; padding: 6px 2px; border-radius: 8px; border: 1px solid var(--border-subtle);">
            <div style="font-weight: 700; color: var(--brand-green); font-size: 13px;">125</div>
            <div style="font-size: 8px; color: var(--text-muted);">Total</div>
          </div>
          <div style="background: #FAF8F4; padding: 6px 2px; border-radius: 8px; border: 1px solid var(--border-subtle);">
            <div style="font-weight: 700; color: var(--brand-green); font-size: 13px;">57</div>
            <div style="font-size: 8px; color: var(--text-muted);">Dine</div>
          </div>
          <div style="background: #FAF8F4; padding: 6px 2px; border-radius: 8px; border: 1px solid var(--border-subtle);">
            <div style="font-weight: 700; color: var(--brand-green); font-size: 13px;">67</div>
            <div style="font-size: 8px; color: var(--text-muted);">Take</div>
          </div>
          <div style="background: #FAF8F4; padding: 6px 2px; border-radius: 8px; border: 1px solid var(--border-subtle);">
            <div style="font-weight: 700; color: var(--brand-green); font-size: 13px;">1</div>
            <div style="font-size: 8px; color: var(--text-muted);">Resto</div>
          </div>
        </div>

        <div style="background: #FAF8F4; border-radius: 10px; padding: 8px 12px; border: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
          <div>
            <div style="font-size: 11px; font-weight: 700; color: var(--brand-green);">Mochi Mood Central</div>
            <div style="font-size: 9.5px; color: var(--text-muted);">Jl. Sweet Happiness No. 1</div>
          </div>
          <a href="admin.php?tab=restoran" class="btn-mochi btn-mochi-sm" style="font-size: 9.5px; padding: 3px 8px;">Edit</a>
        </div>
      </div>

    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

