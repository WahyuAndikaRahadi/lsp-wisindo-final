<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_interview_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('m_schemes')->cascadeOnDelete();
            $table->foreignId('competency_unit_id')->nullable()->constrained('m_competency_units')->nullOnDelete();
            $table->unsignedInteger('order')->default(1);
            $table->string('evidence_context', 255)->nullable();
            $table->text('question_text');
            $table->text('expected_answer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['scheme_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_interview_questions');
    }
};
