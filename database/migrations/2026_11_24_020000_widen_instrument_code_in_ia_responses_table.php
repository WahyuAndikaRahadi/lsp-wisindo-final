<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_assessment_ia_responses', function (Blueprint $table) {
            $table->string('instrument_code', 20)->change();
        });
    }

    public function down(): void
    {
        // Revert is optional or kept as string to avoid data truncation if IA08/IA09 exist
        Schema::table('t_assessment_ia_responses', function (Blueprint $table) {
            $table->string('instrument_code', 20)->change();
        });
    }
};
