/* Fundsexa two-way chat — shared by the customer dashboard and admin customer page.
   Renders the thread, sends text + attachments via fetch, and connects to Pusher for live updates. */
(function () {
  'use strict';

  // Keep in sync with Message::IMAGE_TYPES / FILE_TYPES / MAX_FILES / MAX_KB
  const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
  const FILE_EXT  = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];
  const MAX_FILES = 3;
  const MAX_SIZE  = 10 * 1024 * 1024;
  const FILE_ICON = { pdf: '📕', doc: '📘', docx: '📘', xls: '📗', xlsx: '📗', csv: '📊', txt: '📄' };

  const extOf = name => (String(name).split('.').pop() || '').toLowerCase();
  const isImage = a => a.is_image !== undefined ? a.is_image : IMAGE_EXT.includes(extOf(a.name || a.url));
  function fmtSize(bytes) {
    if (!bytes && bytes !== 0) return '';
    return bytes < 1024 * 1024 ? Math.max(1, Math.round(bytes / 1024)) + ' KB' : (bytes / 1048576).toFixed(1) + ' MB';
  }

  function el(tag, cls, text) {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text !== undefined && text !== null) e.textContent = text;
    return e;
  }

  function dayLabel(d) {
    const today = new Date(); const y = new Date(); y.setDate(today.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return 'Today';
    if (d.toDateString() === y.toDateString()) return 'Yesterday';
    return d.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
  }

  function Chat(opts) {
    this.o = opts;
    this.ids = new Set();
    this.lastDay = null;
    this.files = [];
    this.thread = opts.thread;
    this.empty = opts.emptyEl || null;
    (opts.messages || []).forEach(m => this.add(m, false));
    this.scrollBottom();
    if (opts.form) this.bindForm();
  }

  Chat.prototype.isMine = function (m) { return m.sender === this.o.viewer; };

  Chat.prototype.add = function (m, scroll = true) {
    if (this.ids.has(m.id)) return false;
    this.ids.add(m.id);
    if (this.empty) this.empty.hidden = true;

    const d = new Date(m.created_at);
    if (d.toDateString() !== this.lastDay) {
      this.lastDay = d.toDateString();
      const sep = el('div', 'chat-day'); sep.appendChild(el('span', null, dayLabel(d)));
      this.thread.appendChild(sep);
    }

    const mine = this.isMine(m);
    const row = el('div', 'chat-row ' + (mine ? 'mine' : 'theirs'));
    row.dataset.id = m.id;
    const bubble = el('div', 'chat-bubble');
    if (!mine) bubble.appendChild(el('div', 'chat-name', this.o.otherName));
    if (m.body) bubble.appendChild(el('div', 'chat-text', m.body));
    const atts = m.attachments || [];
    const images = atts.filter(isImage), docs = atts.filter(a => !isImage(a));
    if (images.length) {
      const imgs = el('div', 'chat-images');
      images.forEach(a => {
        const link = el('a'); link.href = a.url; link.target = '_blank'; link.rel = 'noopener'; link.title = a.name;
        const img = el('img'); img.src = a.url; img.alt = a.name; img.loading = 'lazy';
        link.appendChild(img); imgs.appendChild(link);
      });
      bubble.appendChild(imgs);
    }
    if (docs.length) {
      const list = el('div', 'chat-files');
      docs.forEach(a => {
        const ext = a.ext || extOf(a.name || a.url);
        const card = el('a', 'chat-file');
        card.href = a.url; card.target = '_blank'; card.rel = 'noopener';
        card.download = a.name || ''; card.title = 'Download ' + (a.name || 'file');
        card.appendChild(el('span', 'chat-file-icon', FILE_ICON[ext] || '📎'));
        const info = el('span', 'chat-file-info');
        info.appendChild(el('span', 'chat-file-name', a.name || 'File'));
        info.appendChild(el('span', 'chat-file-meta', [ext.toUpperCase(), fmtSize(a.size)].filter(Boolean).join(' · ')));
        card.appendChild(info);
        card.appendChild(el('span', 'chat-file-dl', '⬇'));
        list.appendChild(card);
      });
      bubble.appendChild(list);
    }
    row.appendChild(bubble);

    const meta = el('div', 'chat-meta',
      d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }));
    if (mine) {
      const seen = this.o.viewer === 'admin' ? m.seen : m.seen_by_admin;
      meta.appendChild(el('span', 'chat-seen' + (seen ? ' is-seen' : ''), seen ? ' · ✓✓ Seen' : ' · ✓ Sent'));
    }
    if (this.o.onDelete && mine) {
      const del = el('button', 'chat-del', '🗑'); del.type = 'button'; del.title = 'Delete message';
      del.onclick = () => this.o.onDelete(m.id);
      meta.appendChild(del);
    }
    row.appendChild(meta);
    this.thread.appendChild(row);
    if (scroll) this.scrollBottom();
    return true;
  };

  // Swap to another conversation (admin chat page) without reloading
  Chat.prototype.load = function (opts) {
    Object.assign(this.o, opts);
    this.ids.clear();
    this.lastDay = null;
    this.thread.innerHTML = '';
    if (this.empty) { this.thread.appendChild(this.empty); this.empty.hidden = false; }
    (opts.messages || []).forEach(m => this.add(m, false));
    if (this.input) {
      this.input.value = '';
      this.input.placeholder = opts.placeholder || this.input.placeholder;
      this.files = []; this.renderPreviews(); this.showError('');
    }
    this.scrollBottom();
  };

  Chat.prototype.markAllSeen = function () {
    this.thread.querySelectorAll('.chat-row.mine .chat-seen').forEach(s => {
      s.textContent = ' · ✓✓ Seen'; s.classList.add('is-seen');
    });
  };

  Chat.prototype.scrollBottom = function () {
    this.thread.scrollTop = this.thread.scrollHeight;
  };

  // ── Composer ──
  Chat.prototype.bindForm = function () {
    const f = this.o.form;
    this.input   = f.querySelector('textarea');
    this.picker  = f.querySelector('input[type=file]');
    this.preview = f.querySelector('.chat-previews');
    this.error   = f.querySelector('.chat-error');
    this.button  = f.querySelector('button[type=submit]');

    this.picker.addEventListener('change', () => { this.addFiles(this.picker.files); this.picker.value = ''; });
    this.input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); f.requestSubmit(); }
    });
    this.input.addEventListener('paste', e => {
      const pasted = Array.from(e.clipboardData?.files || []);
      if (pasted.length) this.addFiles(pasted);
    });
    [this.thread, f].forEach(zone => {
      zone.addEventListener('dragover', e => { e.preventDefault(); f.classList.add('is-dragover'); });
      zone.addEventListener('dragleave', () => f.classList.remove('is-dragover'));
      zone.addEventListener('drop', e => {
        e.preventDefault(); f.classList.remove('is-dragover');
        if (e.dataTransfer?.files?.length) this.addFiles(e.dataTransfer.files);
      });
    });
    f.addEventListener('submit', e => { e.preventDefault(); this.send(); });
  };

  Chat.prototype.showError = function (msg) {
    this.error.textContent = msg || '';
    this.error.hidden = !msg;
  };

  Chat.prototype.addFiles = function (list) {
    this.showError('');
    for (const file of Array.from(list)) {
      const ext = extOf(file.name);
      if (!IMAGE_EXT.includes(ext) && !FILE_EXT.includes(ext)) {
        this.showError(file.name + ': allowed files are images, PDF, Word, Excel, CSV and TXT.'); continue;
      }
      if (file.size > MAX_SIZE) { this.showError(file.name + ' is larger than 10 MB.'); continue; }
      if (this.files.length >= MAX_FILES) { this.showError('You can attach up to ' + MAX_FILES + ' files per message.'); break; }
      this.files.push(file);
    }
    this.renderPreviews();
  };

  Chat.prototype.renderPreviews = function () {
    this.preview.innerHTML = '';
    this.preview.hidden = this.files.length === 0;
    this.files.forEach((file, i) => {
      const ext = extOf(file.name);
      const item = el('div', 'chat-preview' + (IMAGE_EXT.includes(ext) ? '' : ' is-file'));
      if (IMAGE_EXT.includes(ext)) {
        const img = el('img'); img.src = URL.createObjectURL(file); img.alt = file.name;
        item.appendChild(img);
      } else {
        item.appendChild(el('span', 'chat-file-icon', FILE_ICON[ext] || '📎'));
        const info = el('span', 'chat-file-info');
        info.appendChild(el('span', 'chat-file-name', file.name));
        info.appendChild(el('span', 'chat-file-meta', fmtSize(file.size)));
        item.appendChild(info);
      }
      const x = el('button', null, '×'); x.type = 'button'; x.title = 'Remove';
      x.onclick = () => { this.files.splice(i, 1); this.renderPreviews(); };
      item.appendChild(x);
      this.preview.appendChild(item);
    });
  };

  Chat.prototype.send = function () {
    const body = this.input.value.trim();
    if (!body && !this.files.length) return;

    const data = new FormData();
    if (body) data.append('body', body);
    this.files.forEach(f => data.append('files[]', f));

    this.button.disabled = true;
    this.showError('');
    fetch(this.o.sendUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': this.o.csrf, 'Accept': 'application/json' },
      body: data,
    })
      .then(r => r.json().then(j => ({ ok: r.ok, j })))
      .then(({ ok, j }) => {
        if (!ok || !j.success) {
          const errs = j.errors ? Object.values(j.errors).flat() : [];
          throw new Error(errs[0] || j.message || 'Message could not be sent.');
        }
        this.add(j.message);
        if (this.o.onSent) this.o.onSent(j.message);
        this.input.value = '';
        this.files = []; this.renderPreviews();
        this.input.focus();
      })
      .catch(err => this.showError(err.message || 'Message could not be sent. Please try again.'))
      .finally(() => { this.button.disabled = false; });
  };

  // ── Pusher connection (returns null when keys are not configured) ──
  function connect(cfg) {
    if (!cfg || !cfg.key || typeof window.Pusher === 'undefined') return null;
    return new window.Pusher(cfg.key, {
      cluster: cfg.cluster,
      forceTLS: true,
      channelAuthorization: {
        endpoint: cfg.authUrl,
        transport: 'ajax',
        headers: { 'X-CSRF-TOKEN': cfg.csrf },
      },
    });
  }

  window.FxChat = { create: o => new Chat(o), connect };
})();
