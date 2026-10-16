<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('t_schedules')->cascadeOnDelete();
            $table->foreignId('asesi_id')->constrained('users');
            $table->foreignId('assessor_id')->nullable()->constrained('users');
            $table->longText('assessor_signature')->nullable();
            $table->timestamp('assessor_signed_at')->nullable();
            $table->longText('asesi_signature')->nullable();
            $table->timestamp('asesi_signed_at')->nullable();
            $table->enum('apl_status', ['DRAFT', 'SUBMITTED', 'REVISION_REQUESTED', 'APPROVED', 'REJECTED'])->default('DRAFT');
            $table->enum('admin_status', ['PENDING', 'APPROVED', 'REVISION', 'REJECTED'])->default('PENDING');
            $table->text('admin_notes')->nullable();
            $table->timestamp('admin_approved_at')->nullable();
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('admin_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('apl_revision_notes')->nullable();
            $table->string('last_active_step', 50)->default('apl');
            $table->string('status', 50)->default('IN_PROGRESS');
            $table->string('final_result', 50)->default('BELUM_SELESAI');
            $table->timestamp('final_result_at')->nullable();
            $table->text('final_notes')->nullable();
            $table->text('follow_up_action')->nullable();
            $table->timestamp('ak01_agreed_at')->nullable();
            $table->enum('pleno_decision', ['KOMPETEN', 'BELUM_KOMPETEN'])->nullable();
            $table->text('pleno_notes')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['schedule_id', 'asesi_id']);
        });

        Schema::create('t_assessment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->enum('proof_type', ['IJAZAH_SMK', 'SURAT_SISWA_AKTIF', 'IJAZAH_SMA_SERTIFIKAT', 'PENGALAMAN_KERJA', 'KTP', 'PAS_FOTO']);
            $table->enum('proof_category', ['PERSYARATAN_DASAR', 'ADMINISTRATIF']);
            $table->string('file_path');
            $table->string('original_filename');
            $table->timestamps();
            $table->unique(['assessment_id', 'proof_type'], 't_assessment_proofs_assessment_proof_type_unique');
        });

        Schema::create('t_assessment_apl02_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->foreignId('kuk_id')->constrained('m_kuks')->cascadeOnDelete();
            $table->enum('assessment_value', ['K', 'BK']);
            $table->string('selected_proof_type', 50)->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'kuk_id'], 't_assessment_apl02_items_assessment_kuk_unique');
        });

        Schema::create('t_assessment_ak07_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->string('adjustment_category', 100);
            $table->text('adjustment_detail')->nullable();
            $table->foreignId('assessor_id')->constrained('users');
            $table->timestamps();
            $table->unique(['assessment_id', 'adjustment_category'], 't_assessment_ak07_items_assessment_category_unique');
        });

        Schema::create('t_assessment_ia_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->enum('instrument_code', ['IA01', 'IA01B', 'IA02', 'IA03', 'IA05']);
            $table->json('data_json')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('assessor_notes')->nullable();
            $table->longText('assessor_signature')->nullable();
            $table->text('asesi_feedback')->nullable();
            $table->longText('asesi_signature')->nullable();
            $table->timestamp('assessor_signed_at')->nullable();
            $table->timestamp('asesi_signed_at')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'instrument_code'], 't_assessment_ia_responses_assessment_instrument_unique');
        });

        Schema::create('t_assessment_ak02_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->foreignId('competency_unit_id')->constrained('m_competency_units')->cascadeOnDelete();
            $table->json('methods_json')->nullable();
            $table->boolean('is_competent')->default(true);
            $table->timestamps();
            $table->unique(['assessment_id', 'competency_unit_id']);
        });

        Schema::create('t_assessment_ak03_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->json('ratings_json')->nullable();
            $table->text('general_notes')->nullable();
            $table->timestamps();
            $table->unique('assessment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_assessment_ak03_items');
        Schema::dropIfExists('t_assessment_ak02_items');
        Schema::dropIfExists('t_assessment_ia_responses');
        Schema::dropIfExists('t_assessment_ak07_items');
        Schema::dropIfExists('t_assessment_apl02_items');
        Schema::dropIfExists('t_assessment_proofs');
        Schema::dropIfExists('t_assessments');
    }
};
