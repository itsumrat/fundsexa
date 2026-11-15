<?php

namespace App\Console\Commands;

use App\Models\{User, Transaction, PlanSetting};
use Illuminate\Console\Command;

class CalculateDailyProfit extends Command
{
    protected $signature   = 'profit:calculate';
    protected $description = 'Calculate and credit daily profit for all active customers';

    public function handle(): void
    {
        $users = User::where('is_admin', false)
                     ->where('is_active', true)
                     ->where('balance', '>', 0)
                     ->get();

        $count = 0;

        foreach ($users as $user) {
            $rate = $this->getRate($user->balance);
            if ($rate === null) continue;

            $profit = round($user->balance * $rate, 2);

            // Credit to balance
            $user->increment('balance', $profit);
            $user->update(['daily_profit' => $profit]);

            // Record as a transaction
            Transaction::create([
                'user_id'          => $user->id,
                'type'             => 'deposit',
                'amount'           => $profit,
                'reference'        => 'Daily Profit (' . ($rate * 100) . '%)',
                'status'           => 'completed',
                'transaction_date' => now(),
            ]);

            $count++;
        }

        $this->info("✓ Daily profit calculated and credited for {$count} customer(s).");
    }

    /**
     * Daily rate (as a fraction) for a balance, taken from Admin → Plan Settings.
     * Uses each plan's minimum rate (the same rate plan requests use).
     * Plans are checked from the lowest level up, so a balance on a boundary
     * (e.g. exactly $50,000) gets the lower plan's rate.
     */
    private function getRate(float $balance): ?float
    {
        foreach (PlanSetting::config() as $plan) {
            if ($balance >= $plan['min'] && ($plan['max'] === null || $balance <= $plan['max'])) {
                return $plan['rate_min'] / 100;
            }
        }
        return null;
    }
}
