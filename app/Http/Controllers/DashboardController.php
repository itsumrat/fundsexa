<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\{DepositInfo, WithdrawalInfo, InvestmentPlan};
use App\Models\Message;
use App\Events\ChatMessagesSeen;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $depositInfo = DepositInfo::where('user_id', $user->id)->first()
            ?? DepositInfo::whereNull('user_id')->first();

        $withdrawalInfo = WithdrawalInfo::where('user_id', $user->id)->first()
            ?? WithdrawalInfo::whereNull('user_id')->first();

        $transactions = $user->transactions()
            ->orderBy('transaction_date', 'desc')
            ->paginate(10);

        $activePlans = InvestmentPlan::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        // Chat messages load in the pop-up chat window (ChatController@thread), not here

        return view('dashboard', compact(
            'user', 'depositInfo', 'withdrawalInfo', 'transactions', 'activePlans'
        ));
    }

    // Called via AJAX when customer opens Messages tab
    public function markMessagesSeen()
    {
        $user = Auth::user();
        $read = Message::where('user_id', $user->id)->where('sender', 'admin')->where('seen', false)->update(['seen' => true]);
        if ($read) ChatMessagesSeen::dispatchSafely($user->id, 'customer'); // admin's ticks → ✓✓
        return response()->json(['ok' => true]);
    }
}
