<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_competency_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('m_schemes')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('title', 255);
            $table->string('standard_reference', 100)->nullable();
            $table->integer('sequence')->default(1);
            $table->timestamps();

            $table->unique(['scheme_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_competency_units');
    }
};
