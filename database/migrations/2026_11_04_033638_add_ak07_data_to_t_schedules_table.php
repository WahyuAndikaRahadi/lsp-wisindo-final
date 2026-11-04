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
            if (! Schema::hasColumn('t_schedules', 'ak07_data')) {
                $table->json('ak07_data')->nullable()->after('ak01_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('t_schedules', 'ak07_data')) {
                $table->dropColumn('ak07_data');
            }
        });
    }
};
