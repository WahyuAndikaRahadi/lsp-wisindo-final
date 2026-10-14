<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_assessment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->enum('proof_type', ['IJAZAH_SMK', 'SURAT_SISWA_AKTIF', 'IJAZAH_SMA_SERTIFIKAT', 'PENGALAMAN_KERJA', 'KTP', 'PAS_FOTO']);
            $table->enum('proof_category', ['PERSYARATAN_DASAR', 'ADMINISTRATIF']);
            $table->string('file_path', 255);
            $table->string('original_filename', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_assessment_proofs');
    }
};
