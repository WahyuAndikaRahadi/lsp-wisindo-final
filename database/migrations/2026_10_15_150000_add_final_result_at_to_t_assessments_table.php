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
        if (Schema::hasTable('t_assessments')) {
            Schema::table('t_assessments', function (Blueprint $table) {
                if (! Schema::hasColumn('t_assessments', 'final_result_at')) {
                    $table->timestamp('final_result_at')->nullable()->after('final_result');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('t_assessments')) {
            Schema::table('t_assessments', function (Blueprint $table) {
                if (Schema::hasColumn('t_assessments', 'final_result_at')) {
                    $table->dropColumn('final_result_at');
                }
            });
        }
    }
};
