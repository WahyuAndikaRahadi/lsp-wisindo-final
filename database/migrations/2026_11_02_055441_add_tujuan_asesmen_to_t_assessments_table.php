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
        if (! Schema::hasColumn('t_assessments', 'tujuan_asesmen')) {
            Schema::table('t_assessments', function (Blueprint $table) {
                $table->string('tujuan_asesmen', 100)->default('Sertifikasi')->after('apl_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('t_assessments', 'tujuan_asesmen')) {
            Schema::table('t_assessments', function (Blueprint $table) {
                $table->dropColumn('tujuan_asesmen');
            });
        }
    }
};
