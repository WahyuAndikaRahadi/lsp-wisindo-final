<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_assessment_apl02_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->foreignId('kuk_id')->constrained('m_kuks')->cascadeOnDelete();
            $table->enum('assessment_value', ['K', 'BK']);
            $table->string('selected_proof_type', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_assessment_apl02_items');
    }
};
