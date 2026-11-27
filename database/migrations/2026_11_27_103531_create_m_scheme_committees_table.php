<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('m_scheme_committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('m_schemes')->cascadeOnDelete();
            $table->string('role', 50); // PENYUSUN, VALIDATOR
            $table->string('name');
            $table->string('no_met', 100)->nullable();
            $table->longText('signature')->nullable();
            $table->integer('sequence')->default(1);
            $table->timestamps();

            $table->index(['scheme_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_scheme_committees');
    }
};
