<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Attachments are part of the clinical record: instead of
     * being deleted they are voided with a reason, and the file is kept.
     */
    public function up(): void
    {
        Schema::table('consultation_attachments', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('description');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('voided_by_name')->nullable()->after('voided_by');
            $table->text('void_reason')->nullable()->after('voided_by_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'voided_by_name', 'void_reason']);
        });
    }
};
