/**
 * ============================================================================
 * QueueBites – Mochi Mood Frontend JavaScript Engine
 * File: public/js/app.js
 * Deskripsi: Fetch API polling real-time, Web Audio chime, floating toast,
 *            serta penanganan aksi antrean tanpa reload halaman.
 * ============================================================================
 */

const QueueBites = {
  // Web Audio Chime (Suara bel kristal ceria saat antrean dipanggil)
  playChime: function() {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      const notes = [523.25, 659.25, 783.99, 1046.50]; // C5, E5, G5, C6
      notes.forEach((freq, idx) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, ctx.currentTime + idx * 0.12);
        gain.gain.setValueAtTime(0, ctx.currentTime + idx * 0.12);
        gain.gain.linearRampToValueAtTime(0.2, ctx.currentTime + idx * 0.12 + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.12 + 0.8);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(ctx.currentTime + idx * 0.12);
        osc.stop(ctx.currentTime + idx * 0.12 + 0.85);
      });
    } catch (e) {
      console.log('Audio not supported or blocked:', e);
    }
  },

  // Floating Toast Notification
  showToast: function(message, isError = false) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast-msg';
    toast.innerHTML = `<span>${isError ? '⚠️' : '✨'}</span> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  },

  // Pilih Tipe Kunjungan: Dine-In vs Takeaway
  setType: function(type) {
    const input = document.getElementById('visit_type_input');
    if (input) input.value = type;

    const btnDine = document.getElementById('toggle_dine');
    const btnTake = document.getElementById('toggle_takeaway');
    const peopleWrap = document.getElementById('people_count_wrapper');

    if (type === 'dine_in') {
      if (btnDine) btnDine.classList.add('active');
      if (btnTake) btnTake.classList.remove('active');
      if (peopleWrap) peopleWrap.style.display = 'block';
    } else {
      if (btnTake) btnTake.classList.add('active');
      if (btnDine) btnDine.classList.remove('active');
      if (peopleWrap) peopleWrap.style.display = 'none';
    }
  },

  // Polling Real-Time Status Antrean (Halaman queues/status.php)
  initStatusPolling: function(queueId, intervalMs = 3500) {
    if (!queueId) return;

    let lastStatus = null;
    const badgeEl    = document.getElementById('queue_status_badge');
    const posEl      = document.getElementById('queue_position_val');
    const aheadEl    = document.getElementById('queue_ahead_val');
    const waitEl     = document.getElementById('queue_wait_val');
    const loadEl     = document.getElementById('queue_loading_icon');

    const checkStatus = async () => {
      if (loadEl) loadEl.style.display = 'inline';

      try {
        const res = await fetch(`index.php?url=api/queue/status&id=${encodeURIComponent(queueId)}`);
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();

        if (loadEl) loadEl.style.display = 'none';

        if (data && data.success && data.queue) {
          const q = data.queue;

          // Cek perubahan status
          if (lastStatus !== null && lastStatus !== q.status) {
            QueueBites.playChime();
            if (q.status === 'called') {
              QueueBites.showToast(`🔔 Antrean ${q.queue_number} sedang DIPANGGIL! Segera menuju konter.`);
            } else if (q.status === 'serving') {
              QueueBites.showToast(`Antrean ${q.queue_number} sedang dilayani.`);
            } else if (q.status === 'completed') {
              QueueBites.showToast(`Antrean ${q.queue_number} selesai. Selamat menikmati Mochi Mood ♡`);
            }
          }
          lastStatus = q.status;

          // Update Teks & Badge
          if (posEl) posEl.innerText = data.position > 0 ? `#${data.position}` : '-';
          if (aheadEl) aheadEl.innerText = `${data.ahead_count} Rombongan`;
          if (waitEl) waitEl.innerText = `≈ ${data.est_minutes} Menit`;

          if (badgeEl) {
            badgeEl.className = `badge badge-${q.status}`;
            badgeEl.innerText = q.status.toUpperCase();
          }
        }
      } catch (err) {
        if (loadEl) loadEl.style.display = 'none';
        console.error('Fetch error:', err);
      }
    };

    checkStatus();
    setInterval(checkStatus, intervalMs);
  },

  // Aksi Petugas (Panggil, Layani, Selesaikan) via Fetch API POST
  staffAction: async function(queueId, action) {
    if (!action) return;

    try {
      const res = await fetch('index.php?url=api/queue/action', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ queue_id: queueId, action: action })
      });

      const data = await res.json();

      if (data.success) {
        if (action === 'call') {
          QueueBites.playChime();
          QueueBites.showToast('Nomor antrean berhasil dipanggil!');
        } else if (action === 'serve') {
          QueueBites.showToast('Memulai pelayanan pelanggan.');
        } else if (action === 'complete') {
          QueueBites.showToast('Antrean berhasil diselesaikan.');
        }
        setTimeout(() => location.reload(), 500);
      } else {
        QueueBites.showToast(data.message || 'Gagal mengubah status antrean.', true);
      }
    } catch (err) {
      QueueBites.showToast('Koneksi terganggu saat mengirim permintaan.', true);
    }
  },

  // Batalkan Antrean Pelanggan
  cancelQueue: async function(queueId) {
    if (!confirm('Apakah Anda yakin ingin membatalkan nomor antrean ini?')) return;

    try {
      const res = await fetch('index.php?url=api/queue/cancel', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ queue_id: queueId })
      });

      const data = await res.json();
      if (data.success) {
        QueueBites.showToast('Antrean Anda telah dibatalkan.');
        setTimeout(() => {
          window.location.href = 'index.php?url=restaurants/detail';
        }, 800);
      } else {
        QueueBites.showToast(data.message || 'Gagal membatalkan antrean.', true);
      }
    } catch (e) {
      QueueBites.showToast('Gagal membatalkan antrean.', true);
    }
  }
};
