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
        Schema::table('t_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('t_schedules', 'cbt_question_ids')) {
                $table->json('cbt_question_ids')->nullable()->after('selected_instruments');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('t_schedules', 'cbt_question_ids')) {
                $table->dropColumn('cbt_question_ids');
            }
        });
    }
};
