/* Fundsexa customer chat window — floating 💬 button + pop-up conversation with support.
   Config comes from window.FX_CUSTOMER_CHAT (set in partials/customer-chat-widget). */
(function () {
  'use strict';
  const cfg = window.FX_CUSTOMER_CHAT;
  if (!cfg) return;

  const fab    = document.getElementById('chatFab');
  const win    = document.getElementById('chatWindow');
  const status = document.getElementById('chatWinStatus');
  let chat = null, loading = null, unread = cfg.unread || 0;

  function setBadges(n) {
    unread = n;
    document.querySelectorAll('.js-chat-badge').forEach(b => { b.textContent = n; b.hidden = !(n > 0); });
  }

  const isOpen = () => !win.hidden;

  function markSeen() {
    if (unread === 0) return;
    fetch(cfg.seenUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' } })
      .then(() => setBadges(0));
  }

  // Fetch the conversation the first time the window opens
  function load() {
    if (loading) return loading;
    loading = fetch(cfg.threadUrl, { headers: { 'Accept': 'application/json' } })
      .then(r => r.json())
      .then(j => {
        chat = FxChat.create({
          viewer:    'customer',
          otherName: 'Fundsexa Support',
          thread:    document.getElementById('chatWinThread'),
          emptyEl:   document.getElementById('chatWinEmpty'),
          form:      document.getElementById('chatWinForm'),
          sendUrl:   cfg.sendUrl,
          csrf:      cfg.csrf,
          messages:  j.messages,
        });
        setBadges(j.unread);
      })
      .catch(() => { loading = null; document.getElementById('chatWinEmpty').textContent = 'Could not load messages. Please try again.'; });
    return loading;
  }

  function open() {
    win.hidden = false;
    fab.classList.add('is-open');
    fab.classList.remove('has-new');
    document.body.classList.add('chat-window-open');
    load().then(() => {
      if (!chat) return;
      chat.scrollBottom();
      chat.input.focus();
      markSeen();
    });
  }

  function close() {
    win.hidden = true;
    fab.classList.remove('is-open');
    document.body.classList.remove('chat-window-open');
  }

  fab.addEventListener('click', () => (isOpen() ? close() : open()));
  document.getElementById('chatWinClose').addEventListener('click', close);
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && isOpen()) close(); });
  // Any element with data-open-chat (e.g. the dashboard "Messages" tile) opens the window
  document.addEventListener('click', e => {
    const t = e.target.closest('[data-open-chat]');
    if (t && t !== fab) { e.preventDefault(); open(); }
  });

  // ── Live updates ──
  const pusher = FxChat.connect(window.FX_PUSHER);
  if (pusher) {
    pusher.connection.bind('state_change', s => {
      const live = s.current === 'connected';
      status.textContent = live ? 'Online' : 'Connecting…';
      status.classList.toggle('live', live);
    });
    const channel = pusher.subscribe('private-chat.' + cfg.userId);
    // Read receipt: support opened the conversation → my messages become ✓✓ Seen
    channel.bind('messages.seen', d => { if (d.reader === 'admin' && chat) chat.markAllSeen(); });
    channel.bind('message.sent', m => {
      if (chat) chat.add(m);
      if (m.sender !== 'admin') return;
      if (isOpen() && !document.hidden) {
        markSeen();
      } else {
        setBadges(unread + 1);
        fab.classList.add('has-new');
        window.FxAlerts && window.FxAlerts.ding();
      }
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden && isOpen()) markSeen(); });
  } else {
    status.textContent = 'Replies appear on refresh';
  }

  window.FxCustomerChat = { open, close };
})();
