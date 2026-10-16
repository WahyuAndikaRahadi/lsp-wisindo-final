<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('t_schedules', 'tuk_id')) {
                $table->foreignId('tuk_id')->nullable()->after('tuk_name')->constrained('m_tuks')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('t_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('t_schedules', 'tuk_id')) {
                $table->dropForeign(['tuk_id']);
                $table->dropColumn('tuk_id');
            }
        });
    }
};
