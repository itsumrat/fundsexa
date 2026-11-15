<?php

namespace App\Http\Controllers\Admin;

use App\Events\ChatMessageSent;
use App\Events\ChatMessagesSeen;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    // Chat page: customer list on the left, selected conversation on the right
    public function index(?User $user = null)
    {
        $last = fn ($col) => Message::select($col)->whereColumn('user_id', 'users.id')->latest('id')->limit(1);

        $customers = User::where('is_admin', false)
            ->select('id', 'name', 'email')
            ->addSelect([
                'last_at'         => $last('created_at'),
                'last_body'       => $last('body'),
                'last_sender'     => $last('sender'),
                'last_attachment' => $last('attachment'),
                'unread'          => Message::selectRaw('COUNT(*)')->whereColumn('user_id', 'users.id')
                                        ->where('sender', 'customer')->where('seen_by_admin', false),
            ])
            ->orderByRaw('last_at IS NULL, last_at DESC')
            ->orderBy('name')
            ->get();

        // No customer picked → open the most recent conversation, if there is one
        if (!$user && $customers->first()?->last_at) {
            return redirect()->route('admin.messages', $customers->first()->id);
        }

        $messages = collect();
        if ($user) {
            abort_if($user->is_admin, 404);
            $messages = Message::where('user_id', $user->id)->latest()->take(200)->get()->reverse()->values();
            $this->markReadByAdmin($user->id);
            $customers->firstWhere('id', $user->id)?->setAttribute('unread', 0);
        }

        return view('admin.messages', compact('customers', 'user', 'messages'));
    }

    // JSON thread for switching conversations without a page reload
    public function thread(User $user)
    {
        abort_if($user->is_admin, 404);
        $this->markReadByAdmin($user->id);

        return response()->json([
            'success'      => true,
            'user'         => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'messages'     => Message::where('user_id', $user->id)->latest()->take(200)->get()->reverse()->values()->map->toChatArray(),
            'unread_total' => Message::unseenByAdminCount(),
        ]);
    }

    public function store(Request $request, User $user)
    {
        $request->validate(Message::rules(), Message::validationMessages());

        $message = Message::create([
            'user_id'       => $user->id,
            'sender'        => 'admin',
            'body'          => $request->body,
            'seen'          => false,
            'seen_by_admin' => true,
        ] + Message::storeFiles($request->file('files')));

        ChatMessageSent::dispatchSafely($message);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message->toChatArray()]);
        }
        return back()->with('success', "Message sent to {$user->name}.");
    }

    // Called when the admin views a conversation (page load or new message arriving)
    public function markSeen(User $user)
    {
        $this->markReadByAdmin($user->id);

        return response()->json(['success' => true, 'unread_total' => Message::unseenByAdminCount()]);
    }

    public function destroy(Message $message)
    {
        foreach (array_keys($message->attachmentFiles()) as $path) {
            Storage::disk('public')->delete($path);
        }
        $message->delete();
        return back()->with('success', 'Message deleted.');
    }

    // Mark the customer's messages read by admin and tell their open chat window (✓ → ✓✓)
    private function markReadByAdmin(int $userId): void
    {
        $read = Message::where('user_id', $userId)->where('sender', 'customer')
            ->where('seen_by_admin', false)->update(['seen_by_admin' => true]);
        if ($read) ChatMessagesSeen::dispatchSafely($userId, 'admin');
    }
}
