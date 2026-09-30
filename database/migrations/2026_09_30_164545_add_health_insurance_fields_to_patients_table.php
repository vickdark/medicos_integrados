<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('document_type', 2)->nullable()->after('last_name');
            $table->foreignId('insurer_id')->nullable()->after('blood_type')->constrained()->nullOnDelete();
            $table->string('affiliation_type', 2)->nullable()->after('insurer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('insurer_id');
            $table->dropColumn(['document_type', 'affiliation_type']);
        });
    }
};
