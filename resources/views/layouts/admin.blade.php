<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'Admin') — Fundsexa Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="{{ asset('css/admin-style.css') }}" rel="stylesheet" />
  @stack('styles')
</head>
<body data-chat-user="@yield('chat-user')">
<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo"><div>FUND<span>SEXA</span><small>Admin Panel</small></div></div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
        <span class="s-icon">🏠</span> Dashboard
      </a>
      <a class="sidebar-link {{ request()->routeIs('admin.customers*') ? 'active' : '' }}" href="{{ route('admin.customers') }}">
        <span class="s-icon">👥</span> Customers
      </a>
      <a class="sidebar-link {{ request()->routeIs('admin.messages') ? 'active' : '' }}" href="{{ route('admin.messages') }}" style="position:relative;">
        <span class="s-icon">💬</span> Messages
        @php $msgBadge = \App\Models\Message::unseenByAdminCount(); @endphp
        <span id="msgBadge" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:#dc2626;color:#fff;border-radius:50px;min-width:18px;height:18px;padding:0 5px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;line-height:1;" @if($msgBadge === 0) hidden @endif>{{ $msgBadge }}</span>
      </a>
      <div class="nav-section-label">Investments & Transactions</div>
      <a class="sidebar-link {{ request()->routeIs('admin.plans*') ? 'active' : '' }}" href="{{ route('admin.plans') }}">
        <span class="s-icon">📈</span> Investments
      </a>
      <a class="sidebar-link {{ request()->routeIs('admin.plan-requests*') ? 'active' : '' }}" href="{{ route('admin.plan-requests') }}" style="position:relative;">
        <span class="s-icon">📋</span> Plan Requests
        @php $badge = \App\Models\PlanRequest::unseenCount(); @endphp
        @if($badge > 0)
          <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:#dc2626;color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;line-height:1;">{{ $badge }}</span>
        @endif
      </a>

      <div class="nav-section-label">Settings</div>
      <a class="sidebar-link {{ request()->routeIs('admin.plan-settings*') ? 'active' : '' }}" href="{{ route('admin.plan-settings') }}">
        <span class="s-icon">🧩</span> Plan Settings
      </a>
      <a class="sidebar-link {{ request()->routeIs('admin.payment-info*') ? 'active' : '' }}" href="{{ route('admin.payment-info') }}">
        <span class="s-icon">⚙️</span> Payment Info
      </a>
      <div class="nav-section-label">Account</div>
      <a class="sidebar-link {{ request()->routeIs('admin.password*') ? 'active' : '' }}" href="{{ route('admin.password.change') }}"><span class="s-icon">🔑</span> Change Password</a>
      <a class="sidebar-link" href="{{ route('dashboard') }}"><span class="s-icon">🌐</span> Customer Site</a>
    </nav>
    <div class="sidebar-footer">
      <div style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:8px;">
        <div style="width:8px;height:8px;border-radius:50%;background:var(--green);flex-shrink:0;"></div>
        {{ auth()->user()->name }}
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">@yield('page-title', 'Admin')</div>
      <div class="topbar-right">
        <button type="button" class="topbar-notify" id="notifyBtn" hidden>🔔 Enable notifications</button>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="topbar-logout" title="Sign Out">🚪 Logout</button>
        </form>
      </div>
    </div>

    <div class="page-body fade-up">

      @yield('content')
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>

  function showToast(msg) {
    const t=document.getElementById('toast');
    t.textContent=msg; t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),2600);
  }
</script>
@include('partials.chat-scripts')
<script src="{{ asset('js/chat-alerts.js') }}"></script>
<script>
  // ── Live chat: one admin channel for every page ──
  window.fxSetMessageBadge = function (n) {
    const b = document.getElementById('msgBadge');
    b.textContent = n; b.hidden = !(n > 0);
  };
  (function () {
    const pusher = FxChat.connect(window.FX_PUSHER);
    if (!pusher) return;
    pusher.connection.bind('state_change', s =>
      window.dispatchEvent(new CustomEvent('fx:pusher-state', { detail: s.current })));
    const adminChannel = pusher.subscribe('private-admin.chat');
    // Read receipts (customer opened their chat) — the Messages page flips ticks to ✓✓
    adminChannel.bind('messages.seen', d =>
      window.dispatchEvent(new CustomEvent('fx:chat-seen', { detail: d })));
    adminChannel.bind('message.sent', m => {
      // Pages (customer chat, inbox) react to this event
      window.dispatchEvent(new CustomEvent('fx:chat-message', { detail: m }));
      if (m.sender !== 'customer') return;
      const viewingThread = document.body.dataset.chatUser == m.user_id;
      if (!viewingThread) {
        const b = document.getElementById('msgBadge');
        window.fxSetMessageBadge((parseInt(b.textContent) || 0) + 1);
        showToast('💬 New message from ' + m.customer_name);
      }
      // Sound + flashing tab title + desktop notification
      FxAlerts.alertNewMessage(m, viewingThread, '{{ url("admin/messages") }}/' + m.user_id);
    });
  })();
</script>
@stack('modals')
@stack('scripts')
<script>
function dismissAlert(el) {
  const alert = el.closest('[class*="alert"]');
  if (alert) { alert.style.transition='opacity 0.3s'; alert.style.opacity='0'; setTimeout(()=>alert.remove(),300); }
}
setTimeout(() => {
  document.querySelectorAll('[class*="alert-success"],[class*="alert-error"],[class*="alert-warning"]').forEach(el => {
    el.style.transition='opacity 0.5s'; el.style.opacity='0'; setTimeout(()=>el.remove(),500);
  });
}, 6000);
</script>
</body>
</html>
