<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Each clinical history PDF that is generated is recorded,
     * with the consultations it included, so it can be verified by its QR code.
     */
    public function up(): void
    {
        Schema::create('clinical_history_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('issued_by_name');
            $table->string('issued_by_role', 30);
            $table->string('verification_code', 20)->unique();
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->string('period_label');
            $table->json('consultation_ids');
            $table->boolean('includes_notes')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_history_exports');
    }
};
