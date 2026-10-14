<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nik', 20)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('kebangsaan', 50)->default('Indonesia');
            $table->text('alamat_rumah')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('no_telepon_rumah', 30)->nullable();
            $table->string('no_telepon_kantor', 30)->nullable();
            $table->string('pendidikan_terakhir', 50)->nullable();
            $table->string('nama_institusi_perusahaan', 255)->nullable();
            $table->string('jabatan', 100)->nullable();
            $table->text('alamat_kantor')->nullable();
            $table->longText('signature_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_user_profiles');
    }
};
