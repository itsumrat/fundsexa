<?php

namespace App\Events;

trait BroadcastsSafely
{
    /**
     * Send the event, but never let a Pusher outage (or missing keys)
     * break the request — the other side sees the change on refresh.
     */
    public static function dispatchSafely(...$args): void
    {
        if (config('broadcasting.default') === 'pusher' && blank(config('broadcasting.connections.pusher.key'))) {
            return;
        }
        try {
            broadcast(new static(...$args));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
