<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fundsexa — Crypto Investment Platform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="{{ asset('css/style.css') }}" rel="stylesheet" />
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="{{ route('home') }}">FUND<span>SEXA</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item"><a class="nav-link active" href="{{ route('home') }}">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('about') }}">About</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('plans') }}">Plans</a></li>
        @auth
          @if(auth()->user()->is_admin)
            <li class="nav-item ms-lg-2"><a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.dashboard') }}">Admin Panel</a></li>
          @else
            <li class="nav-item ms-lg-2"><a class="btn btn-outline-secondary btn-sm" href="{{ route('dashboard') }}">Dashboard</a></li>
          @endif
          <li class="nav-item ms-1">
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
              @csrf
              <button type="submit" class="btn btn-primary btn-sm">Sign Out</button>
            </form>
          </li>
        @else
          <li class="nav-item ms-lg-2"><a class="btn btn-outline-secondary btn-sm" href="{{ route('login') }}">Login</a></li>
          <li class="nav-item ms-1"><a class="btn btn-primary btn-sm" href="{{ route('register') }}">Get Started</a></li>
        @endauth
      </ul>
    </div>
  </div>
</nav>

@auth
@php
  $u = auth()->user();
  $activeCount = \App\Models\InvestmentPlan::where('user_id', $u->id)->where('status', 'active')->count();
@endphp

<!-- ═══ LOGGED-IN: PERSONAL WELCOME ═══ -->
<section class="welcome-hero">
  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-6 fade-up">
        <div class="welcome-date" id="welcomeDate">&nbsp;</div>
        <h1 class="welcome-title"><span id="welcomeGreeting">Welcome back</span>,<br><span class="highlight">{{ $u->name }}</span></h1>
        <p class="text-muted mb-4" style="font-size:16px; max-width:440px; line-height:1.7;">
          Here's a quick look at your account. Head to your dashboard for full details, deposits and withdrawals.
        </p>
        <div class="d-flex gap-3 flex-wrap">
          @if($u->is_admin)
            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-lg">Open Admin Panel →</a>
          @else
            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">Go to Dashboard →</a>
          @endif
          <a href="{{ route('plans') }}" class="btn btn-outline-secondary btn-lg">Browse Plans</a>
        </div>
      </div>

      <div class="col-lg-6 fade-up" style="animation-delay:0.1s">
        <div class="balance-card mb-3">
          <div class="balance-card-top">
            <div>
              <div class="balance-label">Available Balance</div>
              <div class="balance-amount">${{ number_format($u->balance, 2) }} <span class="balance-currency">USD</span></div>
            </div>
            <span class="balance-badge">● {{ $activeCount }} active {{ \Illuminate\Support\Str::plural('plan', $activeCount) }}</span>
          </div>
          <div class="welcome-mini-stats">
            <div>
              <div class="balance-label">Daily Profit</div>
              <div class="welcome-mini-val">+${{ number_format($u->daily_profit, 2) }}</div>
            </div>
            <div>
              <div class="balance-label">Total Invested</div>
              <div class="welcome-mini-val">${{ number_format($u->total_deposited, 2) }}</div>
            </div>
            <div>
              <div class="balance-label">Withdrawn</div>
              <div class="welcome-mini-val">${{ number_format($u->total_withdrawn, 2) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick links -->
    <div class="row g-3 mt-2">
      <div class="col-6 col-lg-3">
        <a class="quick-link" href="{{ $u->is_admin ? route('admin.dashboard') : route('dashboard') }}">
          <span class="quick-link-icon">📊</span>
          <span><span class="quick-link-title">Dashboard</span><span class="quick-link-sub">Balance &amp; history</span></span>
        </a>
      </div>
      <div class="col-6 col-lg-3">
        <a class="quick-link" href="{{ route('plans') }}">
          <span class="quick-link-icon">📈</span>
          <span><span class="quick-link-title">Plans</span><span class="quick-link-sub">Compare &amp; request</span></span>
        </a>
      </div>
      <div class="col-6 col-lg-3">
        <a class="quick-link" href="{{ route('profile.edit') }}">
          <span class="quick-link-icon">👤</span>
          <span><span class="quick-link-title">Profile</span><span class="quick-link-sub">Name &amp; photo</span></span>
        </a>
      </div>
      <div class="col-6 col-lg-3">
        <a class="quick-link" href="{{ $u->is_admin ? route('admin.password.change') : route('password.change') }}">
          <span class="quick-link-icon">🔑</span>
          <span><span class="quick-link-title">Security</span><span class="quick-link-sub">Change password</span></span>
        </a>
      </div>
    </div>
  </div>
</section>

@else

<!-- ═══ GUEST: CENTERED HERO + PRODUCT PREVIEW ═══ -->
<section class="hero-center">
  <div class="container text-center fade-up">
    <h1 class="hero-title mx-auto mb-4" style="max-width:760px;">
      Earn Daily <span class="highlight">Crypto Yields</span> Effortlessly
    </h1>
    <p class="section-lead mb-5">
      Professional DeFi investment platform with consistent daily returns.
    </p>
    <div class="d-flex gap-3 flex-wrap justify-content-center">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Create Free Account</a>
      <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">Sign In</a>
    </div>
    <div class="deposit-methods">
      <span class="text-muted">Deposit with</span>
      <span class="method-chip">Binance Pay</span>
      <span class="method-chip">Trust Wallet</span>
      <span class="method-chip">Bank Transfer</span>
    </div>

    <!-- Illustrative dashboard preview (no real data) -->
    <div class="preview-window fade-up" style="animation-delay:0.15s">
      <div class="preview-bar"><span></span><span></span><span></span></div>
      <div class="preview-body">
        <div class="preview-side">
          <div class="sk sk-logo"></div>
          <div class="sk sk-line"></div><div class="sk sk-line"></div><div class="sk sk-line short"></div>
        </div>
        <div class="preview-main">
          <div class="preview-hero-card"><div class="sk sk-light w-40"></div><div class="sk sk-light big w-60"></div></div>
          <div class="preview-grid">
            <div class="preview-tile"><div class="sk w-50"></div><div class="sk big w-70"></div></div>
            <div class="preview-tile"><div class="sk w-50"></div><div class="sk big w-70"></div></div>
            <div class="preview-tile"><div class="sk w-50"></div><div class="sk big w-70"></div></div>
          </div>
          <div class="preview-chart">
            <span style="height:35%"></span><span style="height:50%"></span><span style="height:42%"></span>
            <span style="height:62%"></span><span style="height:55%"></span><span style="height:74%"></span>
            <span style="height:68%"></span><span style="height:86%"></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@endauth

<!-- FEATURES (split layout) -->
<section class="home-section alt">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5">
        <span class="section-eyebrow">Why Fundsexa</span>
        <h2 class="section-heading">Built for steady, simple growth</h2>
        <p class="text-muted" style="font-size:16px; line-height:1.7;">Everything you need to put your crypto to work — without the complexity.</p>
        <a href="{{ route('about') }}" class="btn btn-outline-primary mt-2">Learn more about us</a>
      </div>
      <div class="col-lg-7">
        <div class="feature-list">
          <div class="feature-row">
            <div class="feat-icon">🔒</div>
            <div><div class="feature-row-title">Secure Wallets</div><p class="text-muted mb-0">Multi-sig cold storage protection</p></div>
          </div>
          <div class="feature-row">
            <div class="feat-icon">⚡</div>
            <div><div class="feature-row-title">Instant Deposits</div><p class="text-muted mb-0">Fund via Binance Pay in minutes</p></div>
          </div>
          <div class="feature-row">
            <div class="feat-icon">💰</div>
            <div><div class="feature-row-title">Daily Payouts</div><p class="text-muted mb-0">Profits credited every 24 hours</p></div>
          </div>
          <div class="feature-row">
            <div class="feat-icon">🌐</div>
            <div><div class="feature-row-title">24/7 Support</div><p class="text-muted mb-0">Expert team always available</p></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@guest
<!-- HOW IT WORKS (timeline) -->
<section class="home-section">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-eyebrow">How it works</span>
      <h2 class="section-heading">Start in three steps</h2>
    </div>
    <div class="timeline">
      <div class="timeline-step">
        <div class="step-num">1</div>
        <div class="step-title">Create your account</div>
        <p class="step-text">Sign up for free in under a minute — all you need is an email address.</p>
      </div>
      <div class="timeline-step">
        <div class="step-num">2</div>
        <div class="step-title">Fund your balance</div>
        <p class="step-text">Deposit via Binance Pay, Trust Wallet or bank transfer from your dashboard.</p>
      </div>
      <div class="timeline-step">
        <div class="step-num">3</div>
        <div class="step-title">Choose a plan</div>
        <p class="step-text">Pick the plan that fits you and track every cycle from your dashboard.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section>
  <div class="container">
    <div class="cta-banner">
      <h2 class="mb-3">Ready to get started?</h2>
      <p>Create your free account in under a minute.</p>
      <div>
        <a href="{{ route('register') }}" class="btn btn-light-solid btn-lg px-5">Create Free Account</a>
      </div>
    </div>
  </div>
</section>
@endguest

<!-- FOOTER -->
<footer class="home-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5">
        <div class="navbar-brand">FUND<span>SEXA</span></div>
        <p class="text-muted" style="max-width:320px; line-height:1.7;">Crypto investment platform with a simple, transparent dashboard.</p>
      </div>
      <div class="col-6 col-lg-3">
        <h6>Platform</h6>
        <a href="{{ route('plans') }}">Investment Plans</a>
        <a href="{{ route('about') }}">About Us</a>
      </div>
      <div class="col-6 col-lg-4">
        <h6>Account</h6>
        @auth
          <a href="{{ route('dashboard') }}">Dashboard</a>
          <a href="{{ route('profile.edit') }}">Profile</a>
        @else
          <a href="{{ route('login') }}">Login</a>
          <a href="{{ route('register') }}">Create Account</a>
        @endauth
      </div>
    </div>
    <div class="home-footer-bottom">© {{ date('Y') }} Fundsexa Investments Ltd. All rights reserved.</div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@auth
<script>
  (function () {
    const now = new Date(), h = now.getHours();
    document.getElementById('welcomeGreeting').textContent =
      h < 12 ? 'Good morning' : (h < 18 ? 'Good afternoon' : 'Good evening');
    document.getElementById('welcomeDate').textContent =
      now.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  })();
</script>
@endauth
@include('partials.customer-chat-widget')
</body>
</html>
