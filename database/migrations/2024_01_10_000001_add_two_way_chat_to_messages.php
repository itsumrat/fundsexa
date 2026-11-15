<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Who wrote the message. Existing rows were all sent by admin.
            $table->enum('sender', ['admin', 'customer'])->default('admin')->after('user_id');
            // `seen` = seen by the customer; this one tracks customer → admin messages
            $table->boolean('seen_by_admin')->default(true)->after('seen');
            $table->index(['user_id', 'created_at']);
        });

        // Image-only messages have no body; JSON path lists can exceed 255 chars
        DB::statement('ALTER TABLE messages MODIFY body TEXT NULL');
        DB::statement('ALTER TABLE messages MODIFY attachment TEXT NULL');
        DB::statement('ALTER TABLE messages MODIFY attachment_name TEXT NULL');
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropColumn(['sender', 'seen_by_admin']);
        });
    }
};
