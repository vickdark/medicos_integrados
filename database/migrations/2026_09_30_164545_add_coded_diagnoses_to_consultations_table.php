<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Consultations recorded before this change keep only
     * their free-text diagnosis, so the coded one is nullable.
     */
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->foreignId('primary_diagnosis_id')->nullable()->after('diagnosis')->constrained('diagnoses')->restrictOnDelete();
            $table->string('diagnosis_type', 1)->nullable()->after('primary_diagnosis_id');
        });

        Schema::create('consultation_diagnosis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnosis_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('position');

            $table->unique(['consultation_id', 'diagnosis_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_diagnosis');

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_diagnosis_id');
            $table->dropColumn('diagnosis_type');
        });
    }
};
