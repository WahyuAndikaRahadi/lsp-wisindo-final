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
        if (Schema::hasTable('m_user_profiles')) {
            Schema::table('m_user_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('m_user_profiles', 'kode_provinsi')) {
                    $table->string('kode_provinsi', 10)->nullable()->after('alamat_rumah');
                }
                if (! Schema::hasColumn('m_user_profiles', 'nama_provinsi')) {
                    $table->string('nama_provinsi', 100)->nullable()->after('kode_provinsi');
                }
                if (! Schema::hasColumn('m_user_profiles', 'kode_kota')) {
                    $table->string('kode_kota', 10)->nullable()->after('nama_provinsi');
                }
                if (! Schema::hasColumn('m_user_profiles', 'nama_kota')) {
                    $table->string('nama_kota', 100)->nullable()->after('kode_kota');
                }
                if (! Schema::hasColumn('m_user_profiles', 'kode_pendidikan')) {
                    $table->string('kode_pendidikan', 10)->nullable()->after('pendidikan_terakhir');
                }
                if (! Schema::hasColumn('m_user_profiles', 'kode_pekerjaan')) {
                    $table->string('kode_pekerjaan', 10)->nullable()->after('jabatan');
                }
            });
        }

        if (Schema::hasTable('t_schedules')) {
            Schema::table('t_schedules', function (Blueprint $table) {
                if (! Schema::hasColumn('t_schedules', 'kode_sumber_anggaran')) {
                    $table->string('kode_sumber_anggaran', 10)->nullable()->after('status');
                }
                if (! Schema::hasColumn('t_schedules', 'kode_kementerian')) {
                    $table->string('kode_kementerian', 10)->nullable()->after('kode_sumber_anggaran');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('m_user_profiles')) {
            Schema::table('m_user_profiles', function (Blueprint $table) {
                $columns = ['kode_provinsi', 'nama_provinsi', 'kode_kota', 'nama_kota', 'kode_pendidikan', 'kode_pekerjaan'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('m_user_profiles', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('t_schedules')) {
            Schema::table('t_schedules', function (Blueprint $table) {
                $columns = ['kode_sumber_anggaran', 'kode_kementerian'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('t_schedules', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
