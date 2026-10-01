<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Files stored before the encryption keep the flag off
     * until `attachments:encrypt` encrypts them.
     */
    public function up(): void
    {
        Schema::table('consultation_attachments', function (Blueprint $table) {
            $table->boolean('is_encrypted')->default(false)->after('size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_attachments', function (Blueprint $table) {
            $table->dropColumn('is_encrypted');
        });
    }
};
