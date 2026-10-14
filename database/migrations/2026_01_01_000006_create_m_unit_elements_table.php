<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_unit_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_unit_id')->constrained('m_competency_units')->cascadeOnDelete();
            $table->text('title');
            $table->integer('sequence')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_unit_elements');
    }
};
