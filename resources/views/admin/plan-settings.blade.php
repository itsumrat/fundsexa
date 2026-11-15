@extends('layouts.admin')
@section('title', 'Plan Settings')
@section('page-title', 'Plan Settings')

@section('content')
<div class="page-header">
  <h1>Plan Settings</h1>
  <p>Edit the investment plans shown on the Plans page. Changes apply to new requests and assignments — running cycles keep their original terms.</p>
</div>

@if(session('success'))
  <div class="alert-success-bar mb-4"><span>✓ {{ session('success') }}</span><button class="alert-close" onclick="dismissAlert(this)" title="Dismiss">&times;</button></div>
@endif
@if($errors->any())
  <div class="alert-error-bar mb-4"><span>⚠ {{ $errors->first() }}</span><button class="alert-close" onclick="dismissAlert(this)" title="Dismiss">&times;</button></div>
@endif

<div class="row g-4">
  @foreach($plans as $p)
  @php
    // Only re-fill old() input into the form that was actually submitted
    $mine = old('editing') == $p->id;
    $v = fn($field) => $mine ? old($field) : $p->$field;
  @endphp
  <div class="col-xl-6">
    <div class="card" style="border-top:3px solid {{ $p->color }};">
      <div class="card-header">
        <span style="display:flex;align-items:center;gap:10px;">
          <span style="font-size:20px;">{{ $p->icon }}</span>
          <span>
            {{ $p->name }}
            <span style="display:block;font-size:11px;font-weight:500;color:var(--muted);font-family:'JetBrains Mono',monospace;">Level {{ $p->level }} · key: {{ $p->plan_key }}</span>
          </span>
        </span>
        <span class="badge {{ $p->is_active ? 'badge-green' : 'badge-gray' }}">{{ $p->is_active ? 'Visible' : 'Hidden' }}</span>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.plan-settings.update', $p) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="editing" value="{{ $p->id }}">

          <div class="form-section-label">Display</div>
          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label">Plan Name</label>
              <input type="text" name="name" class="form-control" value="{{ $v('name') }}" maxlength="60" required>
            </div>
            <div class="col-3 col-sm-2">
              <label class="form-label">Icon</label>
              <input type="text" name="icon" class="form-control" value="{{ $v('icon') }}" maxlength="16" style="text-align:center;">
            </div>
            <div class="col-9 col-sm-4">
              <label class="form-label">Badge</label>
              <input type="text" name="badge" class="form-control" value="{{ $v('badge') }}" maxlength="40" placeholder="e.g. Popular">
            </div>
            <div class="col-sm-9">
              <label class="form-label">Short Description</label>
              <input type="text" name="description" class="form-control" value="{{ $v('description') }}" maxlength="255" placeholder="Optional — shown on the plan card">
            </div>
            <div class="col-sm-3">
              <label class="form-label">Color</label>
              <input type="color" name="color" class="form-control" value="{{ $v('color') }}" style="height:42px;padding:4px;">
            </div>
          </div>

          <div class="form-section-label">Terms</div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label">Min Amount ($)</label>
              <input type="number" name="min_amount" class="form-control" value="{{ $v('min_amount') }}" step="0.01" min="1" required>
            </div>
            <div class="col-6">
              <label class="form-label">Max Amount ($)</label>
              <input type="number" name="max_amount" class="form-control" value="{{ $v('max_amount') }}" step="0.01" placeholder="Empty = no limit">
            </div>
            <div class="col-6 col-sm-3">
              <label class="form-label">Rate Min (%/day)</label>
              <input type="number" name="rate_min" class="form-control" value="{{ $v('rate_min') }}" step="0.01" min="0.01" max="100" required>
            </div>
            <div class="col-6 col-sm-3">
              <label class="form-label">Rate Max (%/day)</label>
              <input type="number" name="rate_max" class="form-control" value="{{ $v('rate_max') }}" step="0.01" min="0.01" max="100" required>
            </div>
            <div class="col-6 col-sm-3">
              <label class="form-label">Duration (days)</label>
              <input type="number" name="duration_days" class="form-control" value="{{ $v('duration_days') }}" min="1" required>
            </div>
            <div class="col-6 col-sm-3">
              <label class="form-label">Max Cycles</label>
              <input type="number" name="max_cycles" class="form-control" value="{{ $v('max_cycles') }}" min="1" required>
            </div>
          </div>
          <div style="font-size:12px;color:var(--muted);margin-bottom:16px;">
            Customer requests use the <strong>Rate Min</strong>. Admin can still override the rate when assigning a plan.
          </div>

          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;cursor:pointer;">
              <input type="checkbox" name="is_active" value="1" class="form-check-input" style="margin:0;width:18px;height:18px;"
                {{ ($mine ? old('is_active') : $p->is_active) ? 'checked' : '' }}>
              Show on Plans page
            </label>
            <button type="submit" class="btn btn-primary">💾 Save {{ $p->name }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endforeach
</div>
@endsection
