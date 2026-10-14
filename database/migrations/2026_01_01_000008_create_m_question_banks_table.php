<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('m_schemes')->cascadeOnDelete();
            $table->foreignId('competency_unit_id')->nullable()->constrained('m_competency_units')->nullOnDelete();
            $table->text('question_text');
            $table->string('question_image', 255)->nullable();
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->enum('correct_answer', ['A', 'B', 'C', 'D']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_question_banks');
    }
};
