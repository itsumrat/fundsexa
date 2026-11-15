/* Fundsexa chat alerts: sound, flashing tab title, desktop notifications (admin) — ding is also used by the customer chat window. */
(function () {
  'use strict';

  // ── Sound: short two-tone "ding" generated with Web Audio (no file needed) ──
  let audio = null;
  function unlockAudio() {
    // Browsers only allow audio after the user has interacted with the page
    if (!audio) {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      audio = new Ctx();
    }
    if (audio.state === 'suspended') audio.resume();
  }
  ['click', 'keydown', 'touchstart'].forEach(ev => document.addEventListener(ev, unlockAudio, { passive: true }));

  function ding() {
    if (!audio || audio.state !== 'running') return;
    const now = audio.currentTime;
    [[880, 0], [1320, 0.12]].forEach(([freq, delay]) => {
      const osc = audio.createOscillator(), gain = audio.createGain();
      osc.type = 'sine';
      osc.frequency.value = freq;
      gain.gain.setValueAtTime(0.0001, now + delay);
      gain.gain.exponentialRampToValueAtTime(0.25, now + delay + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, now + delay + 0.35);
      osc.connect(gain).connect(audio.destination);
      osc.start(now + delay);
      osc.stop(now + delay + 0.4);
    });
  }

  // ── Tab title: flash "(N) 💬 New message from …" until the admin comes back ──
  let baseTitle = document.title;
  let pending = 0, lastFrom = '', flashTimer = null, flashOn = false;
  function startFlash(from) {
    if (!flashTimer) baseTitle = document.title; // pages may change the title (e.g. switching chats)
    pending++; lastFrom = from;
    if (flashTimer) return;
    flashTimer = setInterval(() => {
      flashOn = !flashOn;
      document.title = flashOn ? `(${pending}) 💬 New message from ${lastFrom}` : baseTitle;
    }, 1000);
  }
  function stopFlash() {
    clearInterval(flashTimer); flashTimer = null;
    pending = 0; document.title = baseTitle;
  }
  document.addEventListener('visibilitychange', () => { if (!document.hidden) stopFlash(); });
  window.addEventListener('focus', stopFlash);

  // ── Desktop notifications ──
  const supported = 'Notification' in window;
  function renderButton() {
    const btn = document.getElementById('notifyBtn');
    if (!btn) return;
    // Alerts arrive through Pusher, so only offer this once Pusher keys are set
    if (!supported || !window.FX_PUSHER) { btn.hidden = true; return; }
    const p = Notification.permission;
    btn.hidden = p === 'granted';
    btn.textContent = p === 'denied' ? '🔕 Notifications blocked' : '🔔 Enable notifications';
    btn.title = p === 'denied'
      ? 'Notifications are blocked for this site. Allow them from the lock icon in the address bar.'
      : 'Show a desktop notification when a customer sends a message';
    btn.disabled = p === 'denied';
  }
  function requestPermission() {
    if (!supported) return;
    Notification.requestPermission().then(renderButton);
  }

  function desktopNotify(m, openUrl) {
    if (!supported || Notification.permission !== 'granted') return;
    const body = m.body ? (m.body.length > 120 ? m.body.slice(0, 120) + '…' : m.body) : ((m.attachments || []).some(a => !a.is_image) ? '📎 Sent a file' : '📷 Sent an image');
    const n = new Notification('💬 ' + m.customer_name, {
      body,
      tag: 'fx-chat-' + m.user_id,   // one notification per customer; newer replaces older
      renotify: true,
    });
    n.onclick = () => { window.focus(); location.href = openUrl; n.close(); };
  }

  /**
   * Called for every new customer message.
   * viewingThread = the admin is looking at this customer's conversation right now.
   */
  function alertNewMessage(m, viewingThread, openUrl) {
    const away = document.hidden || !document.hasFocus();
    if (viewingThread && !away) return; // already on screen — the chat shows it
    ding();
    if (away) {
      startFlash(m.customer_name);
      desktopNotify(m, openUrl);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    renderButton();
    document.getElementById('notifyBtn')?.addEventListener('click', requestPermission);
  });

  window.FxAlerts = { alertNewMessage, ding };
})();
