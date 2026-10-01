<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. The attachment gets an explicit state (active, corrected
     * or voided) and the void columns become the generic record of who changed
     * the state, when and why. A corrected file points to the one replacing it.
     */
    public function up(): void
    {
        Schema::table('consultation_attachments', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('description')->index();
            $table->foreignId('replaced_by_id')->nullable()->after('status')->constrained('consultation_attachments')->nullOnDelete();
            $table->foreignId('replaces_id')->nullable()->after('replaced_by_id')->constrained('consultation_attachments')->nullOnDelete();
        });

        Schema::table('consultation_attachments', function (Blueprint $table) {
            $table->renameColumn('voided_at', 'status_changed_at');
            $table->renameColumn('voided_by_name', 'status_changed_by_name');
            $table->renameColumn('void_reason', 'status_reason');
        });

        Schema::table('consultation_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
        });

        Schema::table('consultation_attachments', function (Blueprint $table) {
            $table->foreignId('status_changed_by')->nullable()->after('status_changed_at')->constrained('users')->nullOnDelete();
        });

        DB::table('consultation_attachments')->whereNotNull('status_changed_at')->update(['status' => 'voided']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_changed_by');
            $table->dropConstrainedForeignId('replaces_id');
            $table->dropConstrainedForeignId('replaced_by_id');
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });

        Schema::table('consultation_attachments', function (Blueprint $table) {
            $table->renameColumn('status_changed_at', 'voided_at');
            $table->renameColumn('status_changed_by_name', 'voided_by_name');
            $table->renameColumn('status_reason', 'void_reason');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
        });
    }
};
