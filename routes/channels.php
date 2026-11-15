<?php

use Illuminate\Support\Facades\Broadcast;

// A customer's conversation — that customer, or any admin
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return $user->is_admin || (int) $user->id === (int) $userId;
});

// Every new message, for admin inbox and unread badges
Broadcast::channel('admin.chat', function ($user) {
    return (bool) $user->is_admin;
});
