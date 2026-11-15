<?php

namespace App\Http\Controllers;

use App\Models\PlanRequest;
use App\Models\InvestmentPlan;
use App\Models\PlanSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'plan_name' => 'required|string',
            'amount'    => 'required|numeric|min:1',
            'notes'     => 'nullable|string|max:500',
        ]);

        $plan = PlanSetting::config(true)[$data['plan_name']] ?? null;
        if (!$plan) return back()->with('error', 'Invalid plan selected.');

        $amount = (float)$data['amount'];
        if ($amount < $plan['min'] || ($plan['max'] !== null && $amount > $plan['max'])) {
            $range = $plan['max'] !== null
                ? "between \$" . number_format($plan['min']) . " and \$" . number_format($plan['max'])
                : "at least \$" . number_format($plan['min']);
            return back()->with('error', "Amount must be {$range} for {$plan['name']}.");
        }

        // Check main balance is sufficient
        $user = \App\Models\User::find(Auth::id());
        if ($user->balance < $amount) {
            $shortfall = number_format($amount - $user->balance, 2);
            return back()->with('error', "Insufficient balance. You need \${$shortfall} more. Please contact support to top up your balance.");
        }

        // Block if there's already an ACTIVE cycle for this plan
        $hasActive = InvestmentPlan::where('user_id', Auth::id())
            ->where('plan_name', $data['plan_name'])
            ->where('status', 'active')
            ->exists();
        if ($hasActive) {
            return back()->with('error', "You already have an active {$data['plan_name']} cycle running. Please wait for it to complete.");
        }

        // Check no pending request already exists for this plan
        $existing = PlanRequest::where('user_id', Auth::id())
            ->where('plan_name', $data['plan_name'])
            ->where('status', 'pending')
            ->exists();
        if ($existing) {
            return back()->with('error', "You already have a pending request for {$data['plan_name']}. Please wait for admin approval.");
        }

        // Cycle number = actual investment cycles (active+completed) + 1
        $cyclesUsed  = InvestmentPlan::cyclesUsed(Auth::id(), $data['plan_name']);
        $cycleNumber = $cyclesUsed + 1;

        PlanRequest::create([
            'user_id'      => Auth::id(),
            'plan_name'    => $data['plan_name'],
            'plan_level'   => $plan['level'],
            'amount'       => $amount,
            'profit_rate'  => $plan['rate_min'],
            'duration_days'=> $plan['days'],
            'cycle_number' => $cycleNumber,
            'max_cycles'   => $plan['max_cycles'],
            'notes'        => $data['notes'] ?? null,
            'status'       => 'pending',
            'seen_by_admin'=> false,
        ]);

        return back()->with('success', "Your request for {$data['plan_name']} has been submitted. Admin will review and activate it shortly.");
    }
}
