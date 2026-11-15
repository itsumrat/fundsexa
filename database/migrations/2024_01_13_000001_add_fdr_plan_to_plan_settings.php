<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // FDR (Fixed Deposit) plan. Shown in its own full-width row below the
    // regular plans on the customer plans page. Values are editable in
    // Admin → Plan Settings.
    public function up(): void
    {
        if (DB::table('plan_settings')->where('plan_key', 'FDR')->exists()) {
            return;
        }

        $now = now();
        DB::table('plan_settings')->insert([
            'plan_key'      => 'FDR',
            'level'         => 6,
            'name'          => 'FDR',
            'icon'          => '🏦',
            'badge'         => 'Special Offer',
            'description'   => 'Lock in your deposit for a fixed term and earn a steady daily return.',
            'min_amount'    => 5000,
            'max_amount'    => null, // no upper limit
            'rate_min'      => 2.0,
            'rate_max'      => 2.0,
            'duration_days' => 90,
            'max_cycles'    => 1,
            'color'         => '#0f766e',
            'is_active'     => true,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('plan_settings')->where('plan_key', 'FDR')->delete();
    }
};
