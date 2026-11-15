<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // VIP 2 had no upper limit, so it overlapped Diamond ($100,000+).
    // Cap it at $100,000, the same way VIP 1 ends at VIP 2's minimum.
    // Only touches VIP 2 if it still has no limit (i.e. admin hasn't set one).
    public function up(): void
    {
        DB::table('plan_settings')
            ->where('plan_key', 'VIP 2')
            ->whereNull('max_amount')
            ->update(['max_amount' => 100000, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('plan_settings')
            ->where('plan_key', 'VIP 2')
            ->where('max_amount', 100000)
            ->update(['max_amount' => null, 'updated_at' => now()]);
    }
};
