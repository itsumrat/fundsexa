{{-- Chat runtime: Pusher (only when configured) + shared chat renderer --}}
@php
  $pusherKey = config('broadcasting.default') === 'pusher' ? config('broadcasting.connections.pusher.key') : null;
  $pusherConfig = $pusherKey ? [
    'key'     => $pusherKey,
    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
    'authUrl' => url('broadcasting/auth'),
    'csrf'    => csrf_token(),
  ] : null;
@endphp
@if($pusherKey)
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
@endif
<script>
  window.FX_PUSHER = @json($pusherConfig);
</script>
<script src="{{ asset('js/chat.js') }}"></script>
