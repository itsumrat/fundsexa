<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_settings', function (Blueprint $table) {
            $table->id();
            // Fixed internal key — stored in investment_plans.plan_name / plan_requests.plan_name. Never edited.
            $table->string('plan_key', 50)->unique();
            $table->unsignedTinyInteger('level');
            $table->string('name', 60);
            $table->string('icon', 16)->nullable();
            $table->string('badge', 40)->nullable();
            $table->string('description', 255)->nullable();
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2)->nullable(); // null = no upper limit
            $table->decimal('rate_min', 5, 2);
            $table->decimal('rate_max', 5, 2);
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedSmallInteger('max_cycles');
            $table->string('color', 7)->default('#7c3aed');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed with the values that were previously hard-coded
        $now = now();
        DB::table('plan_settings')->insert([
            ['plan_key'=>'Plan 1','level'=>1,'name'=>'Plan 1','icon'=>'📅','badge'=>null,     'description'=>null,'min_amount'=>500,  'max_amount'=>1000, 'rate_min'=>1.5,'rate_max'=>1.5,'duration_days'=>7, 'max_cycles'=>2,'color'=>'#d97706','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['plan_key'=>'Plan 2','level'=>2,'name'=>'Plan 2','icon'=>'📅','badge'=>null,     'description'=>null,'min_amount'=>10000,'max_amount'=>15000,'rate_min'=>3.0,'rate_max'=>3.5,'duration_days'=>15,'max_cycles'=>3,'color'=>'#2563eb','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['plan_key'=>'VIP 1', 'level'=>3,'name'=>'VIP 1', 'icon'=>'👑','badge'=>'Popular','description'=>null,'min_amount'=>25000,'max_amount'=>50000,'rate_min'=>5.0,'rate_max'=>5.0,'duration_days'=>28,'max_cycles'=>6,'color'=>'#16a34a','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['plan_key'=>'VIP 2', 'level'=>4,'name'=>'VIP 2', 'icon'=>'💎','badge'=>null,     'description'=>null,'min_amount'=>50000,'max_amount'=>null, 'rate_min'=>7.0,'rate_max'=>7.0,'duration_days'=>28,'max_cycles'=>5,'color'=>'#9333ea','is_active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_settings');
    }
};
