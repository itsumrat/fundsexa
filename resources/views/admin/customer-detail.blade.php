@extends('layouts.admin')
@section('title', $user->name)
@section('page-title', 'Customer Detail')

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
  <a href="{{ route('admin.customers') }}" class="btn btn-ghost btn-sm">← Back</a>
  <h1 style="font-size:20px;font-weight:800;margin:0;">{{ $user->name }}</h1>
  <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-gray' }}">
    {{ $user->is_active ? 'Active' : 'Inactive' }}
  </span>
  <a href="{{ route('admin.messages', $user->id) }}" class="btn btn-primary btn-sm ms-auto">💬 Chat with {{ $user->name }}</a>
</div>

@if(session('success'))
<div class="alert-success-bar mb-4"><span>✅ {{ session('success') }}</span><button class="alert-close" onclick="dismissAlert(this)">&times;</button></div>
@endif

<div class="row g-4 align-items-start">
  <div class="col-lg-5 col-xl-4">
    {{-- Profile --}}
    <div class="card">
      <div class="card-header">Profile</div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:4px;">
          <div class="avatar" style="width:54px;height:54px;font-size:20px;border-radius:12px;">{{ $user->initials() }}</div>
          <div style="min-width:0;">
            <div style="font-size:17px;font-weight:800;">{{ $user->name }}</div>
            <div style="font-size:13px;color:var(--muted);font-family:'JetBrains Mono',monospace;overflow-wrap:anywhere;">{{ $user->email }}</div>
            <div style="font-size:12px;color:var(--muted);">Registered {{ $user->created_at->format('d M Y') }}</div>
          </div>
        </div>
        <div class="row g-2">
          <div class="col-6"><div class="bank-row"><span class="bank-key">Balance</span><span class="bank-val" style="color:var(--accent);">${{ number_format($user->balance,2) }}</span></div></div>
          <div class="col-6"><div class="bank-row"><span class="bank-key">Daily</span><span class="bank-val" style="color:var(--green);">+${{ number_format($user->daily_profit,2) }}</span></div></div>
          <div class="col-6"><div class="bank-row"><span class="bank-key">Invested</span><span class="bank-val" style="color:var(--green);">${{ number_format($user->total_deposited,2) }}</span></div></div>
          <div class="col-6"><div class="bank-row"><span class="bank-key">Withdrawn</span><span class="bank-val" style="color:var(--red);">${{ number_format($user->total_withdrawn,2) }}</span></div></div>
        </div>

        <div class="row g-2 mt-1">
          <div class="col-6">
            <form method="POST" action="{{ route('admin.customers.toggle', $user) }}">
              @csrf @method('PATCH')
              <button type="submit" class="btn btn-sm w-100 {{ $user->is_active ? 'btn-warning' : 'btn-success' }}">
                {{ $user->is_active ? '⏸ Disable Account' : '▶ Enable Account' }}
              </button>
            </form>
          </div>
          <div class="col-6">
            <button type="button" class="btn btn-sm w-100" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#dc2626;"
              onclick="openDeleteModal('{{ addslashes($user->name) }}')">🗑 Delete Customer</button>
          </div>
          <div class="col-6"><a href="{{ route('admin.plans') }}?txn_customer={{ $user->id }}" class="btn btn-ghost btn-sm w-100">💳 Transactions</a></div>
          <div class="col-6"><a href="{{ route('admin.payment-info') }}" class="btn btn-ghost btn-sm w-100">⚙️ Payment Info</a></div>
        </div>
      </div>
    </div>

  </div>
  <div class="col-lg-7 col-xl-8">
    {{-- Transactions --}}
    <div class="card">
      <div class="card-header">Transaction History <span class="badge badge-blue ms-2">{{ $transactions->total() }}</span></div>
      <div style="overflow-x:auto;">
        <table class="admin-table">
          <thead>
            <tr><th>Type</th><th>Amount</th><th>Date</th><th>Status</th></tr>
          </thead>
          <tbody>
            @forelse($transactions as $txn)
            <tr>
              <td>
                <span class="badge {{ $txn->isDeposit() ? 'badge-green' : 'badge-red' }}">{{ ucfirst($txn->type) }}</span>
                @if($txn->reference)<div style="font-size:11px;color:var(--muted);margin-top:3px;">{{ $txn->reference }}</div>@endif
              </td>
              <td style="font-family:'JetBrains Mono',monospace;font-weight:700;white-space:nowrap;color:{{ $txn->isDeposit() ? 'var(--green)' : 'var(--red)' }};">{{ $txn->signedAmount() }}</td>
              <td style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--muted);white-space:nowrap;">{{ $txn->transaction_date->format('d M Y H:i') }}</td>
              <td><span class="badge {{ $txn->statusBadgeClass() }}">{{ ucfirst($txn->status) }}</span></td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;padding:28px;color:var(--muted);">No transactions yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($transactions->hasPages())
      <div style="padding:14px 20px;border-top:1px solid var(--border);">{{ $transactions->links() }}</div>
      @endif
    </div>

  </div>
</div>

@push('modals')
{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteCustomerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="border-bottom-color:rgba(239,68,68,0.25);">
        <h5 class="modal-title" style="color:#dc2626;">🗑 Delete Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div style="background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:16px;margin-bottom:16px;text-align:center;">
          <div style="font-size:36px;margin-bottom:8px;">⚠️</div>
          <div style="font-size:15px;font-weight:700;color:#1a1a1a;" id="deleteModalName"></div>
        </div>
        <div style="font-size:13px;color:#555;text-align:center;line-height:1.7;">
          This will permanently delete this customer account.<br>
          <strong style="color:#1a1a1a;">Their transaction history will be preserved</strong> in the database.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteCustomerForm" method="POST" action="{{ route('admin.customers.destroy', $user) }}">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-sm" style="background:#dc2626;border-color:#dc2626;color:#fff;padding:7px 20px;">
            Yes, Delete Customer
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

@endpush

@push('styles')
<style>
.bank-row{display:flex;justify-content:space-between;align-items:center;background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:9px 13px;}
.bank-key{font-size:12px;color:var(--muted);font-family:'JetBrains Mono',monospace;}
.bank-val{font-size:13px;font-weight:600;font-family:'JetBrains Mono',monospace;}
</style>
@endpush

@push('scripts')
<script>
function openDeleteModal(name) {
  document.getElementById('deleteModalName').textContent = name;
  new bootstrap.Modal(document.getElementById('deleteCustomerModal')).show();
}

function dismissAlert(el) {
  const alert = el.closest('[class*="alert"]');
  if (alert) { alert.style.transition='opacity 0.3s'; alert.style.opacity='0'; setTimeout(()=>alert.remove(),300); }
}
</script>
@endpush

@endsection
