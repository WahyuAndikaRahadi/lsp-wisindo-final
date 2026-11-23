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
            $table->json('mapa01_data')->nullable()->after('ak07_data');
            $table->timestamp('ak04_briefed_at')->nullable()->after('mapa01_data');
            $table->timestamp('reopened_at')->nullable()->after('closed_at');
        });

        Schema::table('t_assessments', function (Blueprint $table) {
            $table->json('mapa01_data')->nullable()->after('ak01_data');
            $table->timestamp('ak04_briefed_at')->nullable()->after('mapa01_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_schedules', function (Blueprint $table) {
            $table->dropColumn(['mapa01_data', 'ak04_briefed_at', 'reopened_at']);
        });

        Schema::table('t_assessments', function (Blueprint $table) {
            $table->dropColumn(['mapa01_data', 'ak04_briefed_at']);
        });
    }
};
