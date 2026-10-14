<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_assessment_ak07_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->string('adjustment_category', 100);
            $table->text('adjustment_detail')->nullable();
            $table->foreignId('assessor_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_assessment_ak07_items');
    }
};
