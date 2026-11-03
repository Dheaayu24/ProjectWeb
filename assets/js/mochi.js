/**
 * ============================================================================
 * MOCHI MOOD BAKERY – Interactive Frontend Engine
 * Real-time queue polling, Web Audio chime synthesizer, tab toggles & actions
 * ============================================================================
 */

const MochiMood = {
  // Web Audio API Chime Synthesizer (Crystal Bakery Bell)
  playChime: function() {
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      const ctx = new AudioContext();
      
      const notes = [523.25, 659.25, 783.99, 1046.50]; // C5, E5, G5, C6 (Sweet Major Chime)
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
      console.log('Audio chime error:', e);
    }
  },

  // Toggle Password Visibility
  togglePassword: function(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
      input.type = 'text';
      btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
    } else {
      input.type = 'password';
      btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    }
  },

  // Type Selector Toggle (Dine-in vs Takeaway)
  setType: function(type) {
    const input = document.getElementById('queue_type_input');
    if (input) input.value = type;

    const btnDine = document.getElementById('btn_type_dine');
    const btnTake = document.getElementById('btn_type_takeaway');

    if (btnDine && btnTake) {
      if (type === 'dine_in') {
        btnDine.classList.add('active');
        btnTake.classList.remove('active');
      } else {
        btnTake.classList.add('active');
        btnDine.classList.remove('active');
      }
    }
  },

  // Quick Staff Action Handler (Call / Start Serving / Complete)
  staffAction: async function(action, queueId) {
    try {
      const res = await fetch('api/queue_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, queue_id: queueId })
      });
      const data = await res.json();
      if (data.success) {
        if (action === 'call') {
          MochiMood.playChime();
        }
        location.reload();
      } else {
        alert(data.message || 'Action failed');
      }
    } catch (e) {
      console.error(e);
      alert('Network error while performing action.');
    }
  },

  // Real-time Live Queue Tracker Polling
  initTrackerPolling: function(queueNumber) {
    if (!queueNumber) return;
    let lastStatus = null;

    setInterval(async () => {
      try {
        const res = await fetch('api/queue_status.php?number=' + encodeURIComponent(queueNumber));
        if (!res.ok) return;
        const data = await res.json();

        if (data && data.success && data.queue) {
          const q = data.queue;
          
          // Check if status changed
          if (lastStatus && lastStatus !== q.status) {
            MochiMood.playChime();
            if (q.status === 'called') {
              alert('🔔 Nomor antrean Anda ' + q.queue_number + ' sedang DIPANGGIL! Silakan menuju konter.');
            }
          }
          lastStatus = q.status;

          // Update position text
          const posEl = document.getElementById('radar_position_text');
          if (posEl) {
            if (q.status === 'waiting') {
              posEl.innerText = '#' + data.position + ' in Queue';
            } else if (q.status === 'called') {
              posEl.innerText = 'Now Calling!';
            } else if (q.status === 'serving') {
              posEl.innerText = 'In Service';
            } else {
              posEl.innerText = q.status.toUpperCase();
            }
          }

          // Update estimated time
          const timeEl = document.getElementById('radar_est_time');
          if (timeEl) {
            timeEl.innerText = '≈ ' + (data.est_minutes || 5) + ' min';
          }

          // Update alert banner
          const bannerEl = document.getElementById('radar_banner');
          if (bannerEl) {
            if (data.position <= 3 || q.status === 'called') {
              bannerEl.innerHTML = '🔔 Almost Your Turn!';
              bannerEl.style.display = 'inline-flex';
            }
          }
        }
      } catch (err) {
        console.log('Polling ping error', err);
      }
    }, 4000);
  }
};

