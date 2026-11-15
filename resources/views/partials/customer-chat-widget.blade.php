{{-- Floating support chat for logged-in customers (include just before </body>) --}}
@auth
@unless(auth()->user()->is_admin)
@php $chatUnread = \App\Models\Message::unseenCount(auth()->id()); @endphp

<button type="button" class="chat-fab" id="chatFab" aria-label="Chat with support" title="Chat with support">
  <span class="chat-fab-icon">💬</span>
  <span class="chat-fab-badge js-chat-badge" @if($chatUnread === 0) hidden @endif>{{ $chatUnread }}</span>
</button>

<div class="chat-window" id="chatWindow" role="dialog" aria-label="Chat with Fundsexa Support" hidden>
  <div class="chat-box">
    <div class="chat-head">
      <span style="display:flex;align-items:center;gap:10px;">
        <span class="chat-window-avatar">F</span>
        <span>
          <span style="display:block;">Fundsexa Support</span>
          <span class="chat-status" id="chatWinStatus">Connecting…</span>
        </span>
      </span>
      <button type="button" class="chat-window-close" id="chatWinClose" aria-label="Close chat">✕</button>
    </div>
    <div class="chat-thread" id="chatWinThread">
      <div class="chat-empty" id="chatWinEmpty">
        <div style="font-size:32px;margin-bottom:8px;">💬</div>
        Hi {{ explode(' ', auth()->user()->name)[0] }}! Send us a message and our team will reply here.
      </div>
    </div>
    <form class="chat-composer" id="chatWinForm">
      <div class="chat-error" hidden></div>
      <div class="chat-previews" hidden></div>
      <div class="chat-composer-row">
        <label class="chat-attach" title="Attach images or files (PDF, Word, Excel, CSV, TXT · up to 3, 10 MB each)">
          📎<input type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt" multiple hidden>
        </label>
        <textarea rows="1" maxlength="2000" placeholder="Type a message…"></textarea>
        <button type="submit" class="chat-send" title="Send">➤</button>
      </div>
    </form>
  </div>
</div>

@include('partials.chat-scripts')
<script src="{{ asset('js/chat-alerts.js') }}"></script>
<script>
  window.FX_CUSTOMER_CHAT = {
    userId:    {{ auth()->id() }},
    unread:    {{ $chatUnread }},
    threadUrl: @json(route('messages.thread')),
    sendUrl:   @json(route('messages.send')),
    seenUrl:   @json(route('messages.seen')),
    csrf:      @json(csrf_token()),
  };
</script>
<script src="{{ asset('js/customer-chat.js') }}"></script>
@endunless
@endauth
