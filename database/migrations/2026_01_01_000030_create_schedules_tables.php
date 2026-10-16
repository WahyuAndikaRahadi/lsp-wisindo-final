<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name')->nullable();
            $table->foreignId('scheme_id')->constrained('m_schemes');
            $table->foreignId('tuk_id')->nullable()->constrained('m_tuks')->nullOnDelete();
            $table->string('tuk_name');
            $table->string('pic_name')->nullable();
            $table->string('pic_position', 100)->nullable();
            $table->longText('pic_signature')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes')->nullable();
            $table->json('selected_instruments');
            $table->text('notes')->nullable();
            $table->json('ak05_report')->nullable();
            $table->json('ak06_review')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['DRAFT', 'ACTIVE', 'FINISHED'])->default('ACTIVE');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('t_schedule_assessors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('t_schedules')->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['schedule_id', 'assessor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_schedule_assessors');
        Schema::dropIfExists('t_schedules');
    }
};
