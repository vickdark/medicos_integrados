<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Accounts that existed before sign-ins were counted have already accessed the
     * system, so they should see the dashboard reminders from their next visit.
     */
    public function up(): void
    {
        DB::table('users')->where('login_count', 0)->update(['login_count' => 2]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
