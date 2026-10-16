<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE t_assessments MODIFY COLUMN final_result ENUM('KOMPETEN', 'BELUM_KOMPETEN', 'BELUM_SELESAI', 'TIDAK_HADIR') DEFAULT 'BELUM_SELESAI'");
        } else {
            Schema::table('t_assessments', function (Blueprint $table) {
                $table->string('final_result', 50)->default('BELUM_SELESAI')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE t_assessments MODIFY COLUMN final_result ENUM('KOMPETEN', 'BELUM_KOMPETEN', 'BELUM_SELESAI') DEFAULT 'BELUM_SELESAI'");
        }
    }
};
