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
            $table->enum('apl_status', ['DRAFT', 'SUBMITTED', 'REVISION_REQUESTED', 'APPROVED', 'REJECTED'])->default('DRAFT');
            $table->text('apl_revision_notes')->nullable();
            $table->string('last_active_step', 50)->default('apl');
            $table->enum('status', ['IN_PROGRESS', 'COMPLETED'])->default('IN_PROGRESS');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'asesi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_assessments');
    }
};
