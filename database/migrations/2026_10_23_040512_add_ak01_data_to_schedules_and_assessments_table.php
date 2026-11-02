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
            if (! Schema::hasColumn('t_schedules', 'ak01_data')) {
                $table->json('ak01_data')->nullable()->after('selected_instruments');
            }
        });

        Schema::table('t_assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('t_assessments', 'ak01_data')) {
                $table->json('ak01_data')->nullable()->after('ak01_agreed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('t_schedules', 'ak01_data')) {
                $table->dropColumn('ak01_data');
            }
        });

        Schema::table('t_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('t_assessments', 'ak01_data')) {
                $table->dropColumn('ak01_data');
            }
        });
    }
};
