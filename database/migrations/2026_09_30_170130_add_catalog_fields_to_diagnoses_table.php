<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Codes withdrawn from the official table are disabled
     * instead of deleted, because past consultations may reference them.
     */
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
            $table->string('chapter')->nullable()->after('category');
            $table->boolean('is_active')->default(true)->after('chapter')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['category', 'chapter', 'is_active']);
        });
    }
};
