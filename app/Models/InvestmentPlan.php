<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvestmentPlan extends Model
{
    protected $fillable = [
        'user_id','plan_name','plan_level','max_cycles','cycle_number',
        'amount','profit_rate','duration_days','start_date','end_date',
        'status','notes',
    ];

    protected $casts = [
        'start_date'  => 'datetime',
        'end_date'    => 'datetime',
        'amount'      => 'decimal:2',
        'profit_rate' => 'decimal:2',
    ];

    // Plan settings are editable from Admin → Plan Settings (plan_settings table)
    public static function planConfig(): array
    {
        return PlanSetting::config();
    }

    // Include soft-deleted users
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function dailyProfit(): float
    {
        return round((float)$this->amount * ((float)$this->profit_rate / 100), 2);
    }

    public function totalExpectedProfit(): float
    {
        return round($this->dailyProfit() * $this->duration_days, 2);
    }

    public function progressPercent(): int
    {
        // Carbon 3 returns signed floats; clamp so a future start date shows 0%
        $total   = max(1, (int) $this->start_date->diffInDays($this->end_date));
        $elapsed = max(0, min($total, (int) $this->start_date->diffInDays(now())));
        return (int) round(($elapsed / $total) * 100);
    }

    public function daysRemaining(): int
    {
        return max(0, (int) now()->diffInDays($this->end_date, false));
    }

    public static function cyclesUsed(int $userId, string $planName): int
    {
        return static::where('user_id', $userId)
            ->where('plan_name', $planName)
            ->whereIn('status', ['active', 'completed'])
            ->count();
    }

    public static function canStartCycle(int $userId, string $planName, ?int $maxCycles = null): array
    {
        $maxCycles ??= static::planConfig()[$planName]['max_cycles'] ?? 0;
        $used      = static::cyclesUsed($userId, $planName);
        $hasActive = static::where('user_id', $userId)
            ->where('plan_name', $planName)
            ->where('status', 'active')
            ->exists();

        $info = ['used' => $used, 'max' => $maxCycles];
        if ($hasActive) return $info + ['allowed' => false, 'reason' => 'Active cycle already running'];
        if ($used >= $maxCycles) return $info + ['allowed' => false, 'reason' => "Max {$maxCycles} cycles reached"];
        return $info + ['allowed' => true, 'cycle' => $used + 1];
    }
}
