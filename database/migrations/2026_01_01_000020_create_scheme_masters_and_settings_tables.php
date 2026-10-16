<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('scheme_type', ['OKUPASI', 'KLASTER', 'KKNI']);
            $table->string('version', 20)->default('1.0');
            $table->text('description')->nullable();
            $table->decimal('passing_grade_theory', 5, 2)->default(75.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('m_competency_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('m_schemes')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('title');
            $table->string('standard_reference', 100)->nullable();
            $table->integer('sequence')->default(1);
            $table->timestamps();
            $table->unique(['scheme_id', 'code']);
        });

        Schema::create('m_unit_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_unit_id')->constrained('m_competency_units')->cascadeOnDelete();
            $table->text('title');
            $table->integer('sequence')->default(1);
            $table->timestamps();
        });

        Schema::create('m_kuks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_element_id')->constrained('m_unit_elements')->cascadeOnDelete();
            $table->string('code_number', 20);
            $table->text('statement');
            $table->integer('sequence')->default(1);
            $table->timestamps();
        });

        Schema::create('m_question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('m_schemes')->cascadeOnDelete();
            $table->foreignId('competency_unit_id')->nullable()->constrained('m_competency_units')->nullOnDelete();
            $table->text('question_text');
            $table->string('question_image')->nullable();
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->enum('correct_answer', ['A', 'B', 'C', 'D']);
            $table->timestamps();
        });

        Schema::create('m_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_settings');
        Schema::dropIfExists('m_question_banks');
        Schema::dropIfExists('m_kuks');
        Schema::dropIfExists('m_unit_elements');
        Schema::dropIfExists('m_competency_units');
        Schema::dropIfExists('m_schemes');
    }
};
