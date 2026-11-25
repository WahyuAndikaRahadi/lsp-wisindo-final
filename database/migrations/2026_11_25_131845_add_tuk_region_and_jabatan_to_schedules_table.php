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
        if (Schema::hasTable('t_schedules')) {
            Schema::table('t_schedules', function (Blueprint $table) {
                if (! Schema::hasColumn('t_schedules', 'kode_provinsi')) {
                    $table->string('kode_provinsi', 10)->nullable()->after('tuk_address');
                }
                if (! Schema::hasColumn('t_schedules', 'nama_provinsi')) {
                    $table->string('nama_provinsi', 100)->nullable()->after('kode_provinsi');
                }
                if (! Schema::hasColumn('t_schedules', 'kode_kota')) {
                    $table->string('kode_kota', 10)->nullable()->after('nama_provinsi');
                }
                if (! Schema::hasColumn('t_schedules', 'nama_kota')) {
                    $table->string('nama_kota', 100)->nullable()->after('kode_kota');
                }
                if (! Schema::hasColumn('t_schedules', 'tuk_city')) {
                    $table->string('tuk_city', 100)->nullable()->after('nama_kota');
                }
                if (! Schema::hasColumn('t_schedules', 'kode_pekerjaan')) {
                    $table->string('kode_pekerjaan', 10)->nullable()->after('tuk_city');
                }
                if (! Schema::hasColumn('t_schedules', 'jabatan')) {
                    $table->string('jabatan', 100)->nullable()->after('kode_pekerjaan');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('t_schedules')) {
            Schema::table('t_schedules', function (Blueprint $table) {
                $columns = ['kode_provinsi', 'nama_provinsi', 'kode_kota', 'nama_kota', 'tuk_city', 'kode_pekerjaan', 'jabatan'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('t_schedules', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
