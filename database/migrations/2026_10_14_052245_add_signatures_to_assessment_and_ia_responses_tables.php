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
        Schema::table('t_assessment_ia_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('t_assessment_ia_responses', 'asesi_signature')) {
                $table->longText('asesi_signature')->nullable()->after('asesi_signed_at');
            }
            if (! Schema::hasColumn('t_assessment_ia_responses', 'assessor_signature')) {
                $table->longText('assessor_signature')->nullable()->after('assessor_signed_at');
            }
        });

        Schema::table('t_assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('t_assessments', 'assessor_signature')) {
                $table->longText('assessor_signature')->nullable()->after('assessor_id');
            }
            if (! Schema::hasColumn('t_assessments', 'assessor_signed_at')) {
                $table->timestamp('assessor_signed_at')->nullable()->after('assessor_signature');
            }
            if (! Schema::hasColumn('t_assessments', 'asesi_signature')) {
                $table->longText('asesi_signature')->nullable()->after('assessor_signed_at');
            }
            if (! Schema::hasColumn('t_assessments', 'asesi_signed_at')) {
                $table->timestamp('asesi_signed_at')->nullable()->after('asesi_signature');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_assessment_ia_responses', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('t_assessment_ia_responses', 'asesi_signature')) {
                $cols[] = 'asesi_signature';
            }
            if (Schema::hasColumn('t_assessment_ia_responses', 'assessor_signature')) {
                $cols[] = 'assessor_signature';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('t_assessments', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('t_assessments', 'assessor_signature')) {
                $cols[] = 'assessor_signature';
            }
            if (Schema::hasColumn('t_assessments', 'assessor_signed_at')) {
                $cols[] = 'assessor_signed_at';
            }
            if (Schema::hasColumn('t_assessments', 'asesi_signature')) {
                $cols[] = 'asesi_signature';
            }
            if (Schema::hasColumn('t_assessments', 'asesi_signed_at')) {
                $cols[] = 'asesi_signed_at';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
