<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_assessment_ia_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->enum('instrument_code', ['IA01', 'IA01B', 'IA02', 'IA03', 'IA05']);
            $table->json('data_json')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('assessor_notes')->nullable();
            $table->text('asesi_feedback')->nullable();
            $table->timestamp('assessor_signed_at')->nullable();
            $table->timestamp('asesi_signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_assessment_ia_responses');
    }
};
