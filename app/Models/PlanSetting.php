<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanSetting extends Model
{
    protected $fillable = [
        'name','icon','badge','description','min_amount','max_amount',
        'rate_min','rate_max','duration_days','max_cycles','color','is_active',
    ];

    protected $casts = [
        'min_amount' => 'float',
        'max_amount' => 'float',
        'rate_min'   => 'float',
        'rate_max'   => 'float',
        'is_active'  => 'boolean',
    ];

    private static ?array $cache = null;

    /**
     * All plans keyed by plan_key, in the array shape the rest of the app uses.
     * Pass $activeOnly = true for customer-facing pages.
     */
    public static function config(bool $activeOnly = false): array
    {
        if (self::$cache === null) {
            self::$cache = static::orderBy('level')->get()
                ->mapWithKeys(fn (self $p) => [$p->plan_key => $p->toConfig()])
                ->all();
        }
        return $activeOnly
            ? array_filter(self::$cache, fn ($c) => $c['is_active'])
            : self::$cache;
    }

    public static function flushCache(): void
    {
        self::$cache = null;
    }

    public function toConfig(): array
    {
        return [
            'key'          => $this->plan_key,
            'level'        => $this->level,
            'name'         => $this->name,
            'icon'         => $this->icon,
            'badge'        => $this->badge,
            'description'  => $this->description,
            'min'          => $this->min_amount,
            'max'          => $this->max_amount, // null = no limit
            'rate_min'     => $this->rate_min,
            'rate_max'     => $this->rate_max,
            'days'         => $this->duration_days,
            'max_cycles'   => $this->max_cycles,
            'color'        => $this->color,
            'is_active'    => $this->is_active,
            // Aliases used by the admin assign-plan JS
            'default_rate' => $this->rate_min,
            'max_days'     => $this->duration_days,
            'min_amount'   => $this->min_amount,
            'max_amount'   => $this->max_amount,
        ];
    }

    public function rateLabel(): string
    {
        $fmt = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
        return $this->rate_min == $this->rate_max
            ? $fmt($this->rate_min) . '%'
            : $fmt($this->rate_min) . ' – ' . $fmt($this->rate_max) . '%';
    }

    public function rangeLabel(): string
    {
        return '$' . number_format($this->min_amount) .
            ($this->max_amount === null ? '+' : ' – $' . number_format($this->max_amount));
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
    }
}
