<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_kuks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_element_id')->constrained('m_unit_elements')->cascadeOnDelete();
            $table->string('code_number', 20);
            $table->text('statement');
            $table->integer('sequence')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_kuks');
    }
};
