<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('plan_settings')->where('plan_key', 'Diamond')->exists()) {
            return;
        }

        $now = now();
        DB::table('plan_settings')->insert([
            'plan_key'      => 'Diamond',
            'level'         => 5,
            'name'          => 'Diamond',
            'icon'          => '💠',
            'badge'         => 'Elite',
            'description'   => null,
            'min_amount'    => 100000,
            'max_amount'    => null, // no upper limit
            'rate_min'      => 9.0,
            'rate_max'      => 9.0,
            'duration_days' => 28,
            'max_cycles'    => 5,
            'color'         => '#0891b2',
            'is_active'     => true,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('plan_settings')->where('plan_key', 'Diamond')->delete();
    }
};
