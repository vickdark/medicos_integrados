<?php

use App\Actions\Verification\DocumentVerifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Documents already issued get their code now; the
     * prescription code of a consultation is created with its first official PDF.
     */
    public function up(): void
    {
        Schema::table('clinical_documents', function (Blueprint $table) {
            $table->string('verification_code', 20)->nullable()->unique()->after('type');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->string('prescription_verification_code', 20)->nullable()->unique()->after('diagnosis_type');
        });

        DB::table('clinical_documents')->whereNull('verification_code')->orderBy('id')->each(function (object $document): void {
            DB::table('clinical_documents')
                ->where('id', $document->id)
                ->update(['verification_code' => DocumentVerifier::newCode(DocumentVerifier::DOCUMENT_PREFIX)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropUnique(['prescription_verification_code']);
            $table->dropColumn('prescription_verification_code');
        });

        Schema::table('clinical_documents', function (Blueprint $table) {
            $table->dropUnique(['verification_code']);
            $table->dropColumn('verification_code');
        });
    }
};
