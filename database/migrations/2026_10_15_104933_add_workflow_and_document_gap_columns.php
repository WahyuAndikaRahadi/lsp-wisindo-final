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
        Schema::table('t_assessments', function (Blueprint $table) {
            $table->enum('admin_status', ['PENDING', 'APPROVED', 'REVISION', 'REJECTED'])->default('PENDING')->after('apl_status');
            $table->text('admin_notes')->nullable()->after('admin_status');
            $table->timestamp('admin_approved_at')->nullable()->after('admin_notes');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete()->after('admin_approved_at');
            $table->enum('final_result', ['KOMPETEN', 'BELUM_KOMPETEN', 'BELUM_SELESAI'])->default('BELUM_SELESAI')->after('status');
            $table->text('final_notes')->nullable()->after('final_result');
            $table->text('follow_up_action')->nullable()->after('final_notes');
            $table->timestamp('ak01_agreed_at')->nullable()->after('follow_up_action');
            $table->enum('pleno_decision', ['KOMPETEN', 'BELUM_KOMPETEN'])->nullable()->after('ak01_agreed_at');
            $table->text('pleno_notes')->nullable()->after('pleno_decision');
        });

        Schema::table('t_schedules', function (Blueprint $table) {
            $table->string('pic_name', 255)->nullable()->after('tuk_name');
            $table->string('pic_position', 100)->nullable()->after('pic_name');
            $table->longText('pic_signature')->nullable()->after('pic_position');
            $table->json('ak05_report')->nullable()->after('notes');
            $table->json('ak06_review')->nullable()->after('ak05_report');
            $table->boolean('is_closed')->default(false)->after('ak06_review');
            $table->timestamp('closed_at')->nullable()->after('is_closed');
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

            $table->unique(['assessment_id']);
        });

        Schema::create('t_assessment_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->date('appeal_date');
            $table->text('appeal_reasons');
            $table->json('contested_units')->nullable();
            $table->enum('status', ['SUBMITTED', 'UNDER_REVIEW', 'ACCEPTED', 'REJECTED'])->default('SUBMITTED');
            $table->text('decision_notes')->nullable();
            $table->date('decision_date')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('assessor_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_assessment_appeals');
        Schema::dropIfExists('t_assessment_ak03_items');
        Schema::dropIfExists('t_assessment_ak02_items');

        Schema::table('t_schedules', function (Blueprint $table) {
            $table->dropColumn(['pic_name', 'pic_position', 'pic_signature', 'ak05_report', 'ak06_review', 'is_closed', 'closed_at']);
        });

        Schema::table('t_assessments', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropColumn([
                'admin_status',
                'admin_notes',
                'admin_approved_at',
                'admin_id',
                'final_result',
                'final_notes',
                'follow_up_action',
                'ak01_agreed_at',
                'pleno_decision',
                'pleno_notes',
            ]);
        });
    }
};
