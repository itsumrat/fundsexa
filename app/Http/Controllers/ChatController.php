<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Customer side of the two-way chat
class ChatController extends Controller
{
    // Loaded when the customer opens the chat window
    public function thread()
    {
        $messages = Message::where('user_id', Auth::id())->latest()->take(200)->get()->reverse()->values();

        return response()->json([
            'success'  => true,
            'messages' => $messages->map->toChatArray(),
            'unread'   => Message::unseenCount(Auth::id()),
        ]);
    }

    public function send(Request $request)
    {
        $request->validate(Message::rules(), Message::validationMessages());

        $message = Message::create([
            'user_id'       => Auth::id(),
            'sender'        => 'customer',
            'body'          => $request->body,
            'seen'          => true,   // the customer wrote it
            'seen_by_admin' => false,
        ] + Message::storeFiles($request->file('files')));

        ChatMessageSent::dispatchSafely($message);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message->toChatArray()]);
        }
        return back()->with('success', 'Message sent.');
    }
}
