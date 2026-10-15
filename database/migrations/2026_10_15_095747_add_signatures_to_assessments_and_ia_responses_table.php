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
        Schema::table('t_assessments', function (Blueprint $table) {
            $table->longText('assessor_signature')->nullable()->after('assessor_id');
            $table->timestamp('assessor_signed_at')->nullable()->after('assessor_signature');
            $table->longText('asesi_signature')->nullable()->after('assessor_signed_at');
            $table->timestamp('asesi_signed_at')->nullable()->after('asesi_signature');
        });

        Schema::table('t_assessment_ia_responses', function (Blueprint $table) {
            $table->longText('assessor_signature')->nullable()->after('assessor_notes');
            $table->longText('asesi_signature')->nullable()->after('asesi_feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_assessments', function (Blueprint $table) {
            $table->dropColumn(['assessor_signature', 'assessor_signed_at', 'asesi_signature', 'asesi_signed_at']);
        });

        Schema::table('t_assessment_ia_responses', function (Blueprint $table) {
            $table->dropColumn(['assessor_signature', 'asesi_signature']);
        });
    }
};
