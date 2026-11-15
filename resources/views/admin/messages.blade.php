@extends('layouts.admin')
@section('title', $user ? 'Chat · ' . $user->name : 'Messages')
@section('page-title', 'Messages')
@section('chat-user', $user?->id)

@section('content')
@if(session('success'))
<div class="alert-success-bar mb-3"><span>✅ {{ session('success') }}</span><button class="alert-close" onclick="dismissAlert(this)">&times;</button></div>
@endif

<div class="inbox {{ $user ? 'show-chat' : '' }}" id="inbox">

  {{-- ── LEFT: CUSTOMER LIST ── --}}
  <aside class="inbox-side">
    <div class="inbox-search">
      <span>🔍</span>
      <input type="search" id="inboxSearch" placeholder="Search customers…" autocomplete="off">
    </div>
    <div class="inbox-list" id="inboxList">
      @forelse($customers as $c)
        @php
          $preview = $c->last_body
            ? \Illuminate\Support\Str::limit($c->last_body, 60)
            : ($c->last_attachment
                ? (preg_match('/\.(jpe?g|png|gif|webp)"/i', $c->last_attachment) ? '📷 Image' : '📎 File')
                : 'No messages yet');
        @endphp
        <a class="inbox-item {{ $user && $user->id === $c->id ? 'active' : '' }}"
           href="{{ route('admin.messages', $c->id) }}"
           data-user-id="{{ $c->id }}" data-name="{{ $c->name }}" data-email="{{ $c->email }}"
           data-search="{{ strtolower($c->name . ' ' . $c->email) }}">
          <div class="avatar">{{ $c->initials() }}</div>
          <div class="inbox-body">
            <div class="inbox-top">
              <span class="inbox-name">{{ $c->name }}</span>
              <span class="inbox-time" @if($c->last_at) data-utc="{{ \Carbon\Carbon::parse($c->last_at)->utc()->toISOString() }}" @endif></span>
            </div>
            <div class="inbox-bottom">
              <span class="inbox-preview">@if($c->last_sender === 'admin')You: @endif{{ $preview }}</span>
              <span class="inbox-unread" @if(!$c->unread) hidden @endif>{{ $c->unread }}</span>
            </div>
          </div>
        </a>
      @empty
        <div class="inbox-none">No customers yet.</div>
      @endforelse
      <div class="inbox-none" id="inboxNoMatch" hidden>No customers match your search.</div>
    </div>
  </aside>

  {{-- ── RIGHT: CONVERSATION ── --}}
  <section class="inbox-main">
    <div class="inbox-placeholder" id="inboxPlaceholder" @if($user) hidden @endif>
      <div style="font-size:42px;margin-bottom:10px;">💬</div>
      <div style="font-weight:700;margin-bottom:4px;">Select a customer</div>
      <div>Choose someone from the list to view or start a conversation.</div>
    </div>

    <div class="chat-box inbox-chat" id="chatBox" @unless($user) hidden @endunless>
      <div class="chat-head">
        <span style="display:flex;align-items:center;gap:10px;min-width:0;">
          <button type="button" class="inbox-back" id="inboxBack" title="All conversations">←</button>
          <span style="min-width:0;">
            <span id="chatName" style="display:block;">{{ $user->name ?? '' }}</span>
            <span id="chatEmail" style="display:block;font-size:12px;font-weight:500;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $user->email ?? '' }}</span>
          </span>
        </span>
        <span style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
          <span class="chat-status" id="chatStatus">Refresh for new messages</span>
          <a class="btn btn-ghost btn-xs" id="chatProfile" href="{{ $user ? route('admin.customers.show', $user) : '#' }}">👤 Profile</a>
        </span>
      </div>
      <div class="chat-thread" id="chatThread">
        <div class="chat-empty" id="chatEmpty">
          <div style="font-size:32px;margin-bottom:8px;">💬</div>
          No messages yet. Start the conversation below.
        </div>
      </div>
      <form class="chat-composer" id="chatForm">
        <div class="chat-error" hidden></div>
        <div class="chat-previews" hidden></div>
        <div class="chat-composer-row">
          <label class="chat-attach" title="Attach images or files">
            📎<input type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt" multiple hidden>
          </label>
          <textarea rows="1" maxlength="2000" placeholder="Reply to {{ $user->name ?? 'customer' }}…"></textarea>
          <button type="submit" class="chat-send" title="Send">➤</button>
        </div>
        <div class="chat-hint">Enter to send · Shift+Enter for a new line · up to 3 files (images, PDF, Word, Excel, CSV, TXT), 10 MB each · drag &amp; drop works too</div>
      </form>
    </div>
  </section>
</div>
@endsection

@push('modals')
{{-- Delete Message Modal --}}
<div class="modal fade" id="deleteMessageModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="color:#dc2626;">🗑 Delete Message</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="font-size:14px;">This message and its images will be removed for both you and the customer.</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteMessageForm" method="POST">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endpush

@push('scripts')
<script>
(function () {
  const CSRF    = '{{ csrf_token() }}';
  const urls = {
    page:   id => '{{ url("admin/messages") }}/' + id,
    thread: id => '{{ url("admin/messages") }}/' + id + '/thread',
    send:   id => '{{ url("admin/customers") }}/' + id + '/messages',
    seen:   id => '{{ url("admin/customers") }}/' + id + '/messages/seen',
    profile:id => '{{ url("admin/customers") }}/' + id,
    del:    id => '{{ url("admin/messages") }}/' + id,
  };
  const inbox = document.getElementById('inbox');
  const list  = document.getElementById('inboxList');
  let currentId = {{ $user->id ?? 'null' }};

  // ── List helpers ──
  const itemFor = id => list.querySelector('.inbox-item[data-user-id="' + id + '"]');
  function fmtTime(iso) {
    const d = new Date(iso), now = new Date();
    return d.toDateString() === now.toDateString()
      ? d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
      : d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
  }
  list.querySelectorAll('.inbox-time[data-utc]').forEach(t => t.textContent = fmtTime(t.dataset.utc));

  function updateItem(m, bumpUnread) {
    const item = itemFor(m.user_id);
    if (!item) return;
    const text = m.body ? (m.body.length > 60 ? m.body.slice(0, 60) + '…' : m.body)
      : (m.attachments || []).some(a => !a.is_image) ? '📎 File' : '📷 Image';
    item.querySelector('.inbox-preview').textContent = (m.sender === 'admin' ? 'You: ' : '') + text;
    item.querySelector('.inbox-time').textContent = fmtTime(m.created_at);
    if (bumpUnread) {
      const u = item.querySelector('.inbox-unread');
      u.textContent = (parseInt(u.textContent) || 0) + 1; u.hidden = false;
    }
    list.prepend(item); // most recent conversation on top
  }

  // ── Chat panel ──
  const chat = FxChat.create({
    viewer:    'admin',
    otherName: @json($user->name ?? ''),
    thread:    document.getElementById('chatThread'),
    emptyEl:   document.getElementById('chatEmpty'),
    form:      document.getElementById('chatForm'),
    sendUrl:   currentId ? urls.send(currentId) : '',
    csrf:      CSRF,
    messages:  @json($messages->map->toChatArray()),
    onSent:    m => updateItem(m, false),
    onDelete:  id => {
      document.getElementById('deleteMessageForm').action = urls.del(id);
      new bootstrap.Modal(document.getElementById('deleteMessageModal')).show();
    },
  });

  function openThread(id, push) {
    fetch(urls.thread(id), { headers: { 'Accept': 'application/json' } })
      .then(r => r.json())
      .then(j => {
        currentId = j.user.id;
        document.body.dataset.chatUser = currentId;
        document.getElementById('chatName').textContent  = j.user.name;
        document.getElementById('chatEmail').textContent = j.user.email;
        document.getElementById('chatProfile').href      = urls.profile(currentId);
        document.getElementById('inboxPlaceholder').hidden = true;
        document.getElementById('chatBox').hidden = false;
        document.title = 'Chat · ' + j.user.name + ' — Fundsexa Admin';
        chat.load({ otherName: j.user.name, sendUrl: urls.send(currentId), messages: j.messages,
                    placeholder: 'Reply to ' + j.user.name + '…' });
        list.querySelectorAll('.inbox-item').forEach(i => i.classList.toggle('active', i.dataset.userId == currentId));
        const u = itemFor(currentId)?.querySelector('.inbox-unread');
        if (u) { u.textContent = '0'; u.hidden = true; }
        window.fxSetMessageBadge && window.fxSetMessageBadge(j.unread_total);
        inbox.classList.add('show-chat');
        if (push) history.pushState({ id: currentId }, '', urls.page(currentId));
        chat.input.focus();
      });
  }

  list.addEventListener('click', e => {
    const item = e.target.closest('.inbox-item');
    if (!item || e.ctrlKey || e.metaKey) return; // allow open-in-new-tab
    e.preventDefault();
    openThread(item.dataset.userId, true);
  });
  window.addEventListener('popstate', e => { if (e.state && e.state.id) openThread(e.state.id, false); });
  if (currentId) history.replaceState({ id: currentId }, '', urls.page(currentId));

  // Mobile: back to the list
  document.getElementById('inboxBack').addEventListener('click', () => inbox.classList.remove('show-chat'));

  // ── Search ──
  document.getElementById('inboxSearch').addEventListener('input', e => {
    const q = e.target.value.trim().toLowerCase();
    let shown = 0;
    list.querySelectorAll('.inbox-item').forEach(i => {
      const match = !q || i.dataset.search.includes(q);
      i.hidden = !match; if (match) shown++;
    });
    document.getElementById('inboxNoMatch').hidden = shown > 0;
  });

  // ── Live updates (Pusher channel opened in the layout) ──
  window.addEventListener('fx:chat-message', e => {
    const m = e.detail;
    const isOpen = m.user_id == currentId;
    if (isOpen) {
      if (!chat.add(m)) return;
      if (m.sender === 'customer') {
        fetch(urls.seen(currentId), { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
          .then(r => r.json()).then(j => window.fxSetMessageBadge && window.fxSetMessageBadge(j.unread_total));
      }
    }
    updateItem(m, !isOpen && m.sender === 'customer');
  });
  // Customer read the conversation → my replies become ✓✓ Seen
  window.addEventListener('fx:chat-seen', e => {
    if (e.detail.reader === 'customer' && e.detail.user_id == currentId) chat.markAllSeen();
  });
  window.addEventListener('fx:pusher-state', e => {
    const s = document.getElementById('chatStatus');
    s.textContent = e.detail === 'connected' ? 'Live' : 'Connecting…';
    s.classList.toggle('live', e.detail === 'connected');
  });
})();
</script>
@endpush
